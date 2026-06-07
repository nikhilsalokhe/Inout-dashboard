<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeavePolicy;
use App\Models\LeaveBalance;
use App\Models\LeaveApplication;
use App\Models\Holiday;
use App\Models\User;
use App\Helpers\AuditLogger;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class LeaveController extends Controller
{
    /**
     * API: Get employee leave balances for the current year.
     */
    public function getBalances(Request $request)
    {
        $user = $request->user();
        $year = Carbon::now()->year;

        // Initialize balances if they don't exist yet
        $this->initializeUserBalances($user, $year);

        $balances = LeaveBalance::with('leavePolicy')
            ->where('employee_id', $user->id)
            ->where('year', $year)
            ->get();

        return response()->json($balances);
    }

    /**
     * API: Get all active leave policies (for mobile dropdown).
     */
    public function getPolicies(Request $request)
    {
        $policies = LeavePolicy::where('status', 'active')
            ->orderBy('leave_name')
            ->get(['id', 'leave_name', 'leave_code', 'leave_type', 'total_yearly_leave', 'requires_approval']);

        return response()->json($policies);
    }

    /**
     * API: Get upcoming holidays for the employee's location.
     */
    public function getHolidays(Request $request)
    {
        $user = $request->user();
        $year = $request->get('year', now()->year);

        $holidays = Holiday::where(function ($q) use ($user) {
                // All-employees holiday (no specific target set)
                $q->where(function($inner) {
                    $inner->whereNull('location_id')
                          ->whereNull('department_id')
                          ->whereNull('employee_id');
                })
                // Location-matched holidays
                ->orWhere(function($inner) use ($user) {
                    $inner->where('location_id', $user->location_id)
                          ->whereNull('department_id')
                          ->whereNull('employee_id');
                })
                // Department-matched holidays
                ->orWhere(function($inner) use ($user) {
                    $inner->where('department_id', $user->department_id)
                          ->whereNull('employee_id');
                })
                // Employee-specific holidays
                ->orWhere('employee_id', $user->id);
            })
            ->whereYear('holiday_date', $year)
            ->orderBy('holiday_date')
            ->get(['id', 'holiday_name', 'holiday_date', 'holiday_type', 'location_id', 'department_id', 'employee_id']);

        return response()->json($holidays);
    }

    /**
     * API: Get employee leave history (applications).
     */
    public function getHistory(Request $request)
    {
        $user = $request->user();
        $applications = LeaveApplication::with(['leavePolicy', 'approver'])
            ->where('employee_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($applications);
    }

    /**
     * API: Submit a leave application.
     */
    public function apply(Request $request)
    {
        $request->validate([
            'leave_policy_id' => 'required|exists:leave_policies,id',
            'from_date'       => 'required|date|after_or_equal:today',
            'to_date'         => 'required|date|after_or_equal:from_date',
            'reason'          => 'required|string|max:500',
            'attachment'      => 'nullable|file|mimes:pdf,jpg,png|max:5120', // Max 5MB
        ]);

        $user = User::findOrFail($request->user()->id);
        $policy = LeavePolicy::findOrFail($request->leave_policy_id);
        
        $fromDate = Carbon::parse($request->from_date);
        $toDate = Carbon::parse($request->to_date);
        $year = $fromDate->year;

        // Calculate leave days excluding weekends and holidays
        $totalDays = $this->calculateWorkingDays($user, $fromDate, $toDate);

        if ($totalDays <= 0) {
            return response()->json([
                'message' => 'Requested leave period consists only of weekends/holidays.'
            ], 400);
        }

        // Initialize and check balance if policy requires validation
        $this->initializeUserBalances($user, $year);
        $balance = LeaveBalance::where('employee_id', $user->id)
            ->where('leave_policy_id', $policy->id)
            ->where('year', $year)
            ->first();

        // If paid leave or requires approval, check balance bounds
        if ($policy->leave_type === 'paid') {
            if (!$balance || $balance->remaining_leave < $totalDays) {
                return response()->json([
                    'message' => "Insufficient leave balance. Requested {$totalDays} days, available: " . ($balance ? $balance->remaining_leave : 0) . " days."
                ], 400);
            }
        }

        // Store attachment
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('leave_attachments', 'public');
        }

        // Create application
        $application = LeaveApplication::create([
            'employee_id'     => $user->id,
            'leave_policy_id' => $policy->id,
            'from_date'       => $fromDate->toDateString(),
            'to_date'         => $toDate->toDateString(),
            'total_days'      => $totalDays,
            'reason'          => $request->reason,
            'attachment'      => $attachmentPath,
            'status'          => 'pending',
        ]);

        AuditLogger::log(
            'leaves',
            'apply_leave',
            null,
            $application->toArray()
        );

        return response()->json([
            'message'     => 'Leave application submitted successfully.',
            'application' => $application
        ]);
    }

    /**
     * API/Web: Get leave applications pending manager approval.
     */
    public function getPendingApprovals(Request $request)
    {
        $manager = $request->user();
        
        $applications = LeaveApplication::with(['employee.department', 'leavePolicy'])
            ->whereHas('employee', function($q) use ($manager) {
                $q->where('reporting_manager_id', $manager->id);
            })
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($applications);
    }

    /**
     * API/Web: Manager Action (Approve / Reject).
     */
    public function action(Request $request, $id)
    {
        $request->validate([
            'action'  => 'required|in:approve,reject',
            'remarks' => 'nullable|string|max:250',
        ]);

        $manager = $request->user();
        $application = LeaveApplication::with(['employee', 'leavePolicy'])->findOrFail($id);

        // Security check: must be reporting manager or admin
        if ($application->employee->reporting_manager_id !== $manager->id && $manager->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized workflow action.'], 403);
        }

        if ($application->status !== 'pending') {
            return response()->json(['message' => 'Leave application has already been processed.'], 400);
        }

        $year = Carbon::parse($application->from_date)->year;

        if ($request->action === 'approve') {
            // Validate balance again during approval
            if ($application->leavePolicy->leave_type === 'paid') {
                $balance = LeaveBalance::where('employee_id', $application->employee_id)
                    ->where('leave_policy_id', $application->leave_policy_id)
                    ->where('year', $year)
                    ->first();

                if (!$balance || $balance->remaining_leave < $application->total_days) {
                    return response()->json([
                        'message' => "Cannot approve leave. Employee has insufficient balance."
                    ], 400);
                }

                // Deduct balance
                $balance->used_leave += $application->total_days;
                $balance->remaining_leave -= $application->total_days;
                $balance->save();
            }

            $application->status = 'approved';
        } else {
            $application->status = 'rejected';
        }

        $application->approved_by = $manager->id;
        $application->approved_at = Carbon::now();
        $application->remarks = $request->remarks;
        $application->save();

        AuditLogger::log(
            'leaves',
            $request->action . '_leave',
            ['id' => $application->id, 'status' => 'pending'],
            $application->toArray()
        );

        return response()->json([
            'message'     => "Leave application successfully " . ($request->action === 'approve' ? 'approved' : 'rejected') . ".",
            'application' => $application
        ]);
    }

    /**
     * Calculate working days excluding Saturdays, Sundays, and regional/global holidays.
     */
    private function calculateWorkingDays(User $user, Carbon $fromDate, Carbon $toDate)
    {
        $period = CarbonPeriod::create($fromDate, $toDate);
        $totalDays = 0;

        // Fetch regional and global holidays for the leave period
        $holidays = Holiday::whereBetween('holiday_date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->where(function($q) use ($user) {
                $q->whereNull('location_id')
                  ->orWhere('location_id', $user->location_id);
            })
            ->pluck('holiday_date')
            ->map(fn($d) => $d->toDateString())
            ->toArray();

        foreach ($period as $date) {
            // Exclude Saturday (6) and Sunday (0)
            if ($date->isWeekend()) {
                continue;
            }

            // Exclude holidays
            if (in_array($date->toDateString(), $holidays)) {
                continue;
            }

            $totalDays++;
        }

        return $totalDays;
    }

    /**
     * Helper to initialize employee leave balances for a year based on active policies.
     */
    private function initializeUserBalances(User $user, $year)
    {
        $policies = LeavePolicy::where('status', 'active')->get();

        foreach ($policies as $policy) {
            LeaveBalance::firstOrCreate(
                [
                    'employee_id'     => $user->id,
                    'leave_policy_id' => $policy->id,
                    'year'            => $year,
                ],
                [
                    'total_leave'     => $policy->total_yearly_leave,
                    'used_leave'      => 0,
                    'remaining_leave' => $policy->total_yearly_leave,
                ]
            );
        }
    }
}
