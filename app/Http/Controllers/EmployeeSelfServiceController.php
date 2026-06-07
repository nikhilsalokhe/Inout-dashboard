<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use App\Models\LeaveApplication;
use App\Models\LeavePolicy;
use App\Models\LeaveBalance;
use App\Models\Payroll;
use App\Models\Notification;
use App\Models\AuditLog;
use App\Models\Holiday;
use App\Helpers\AuditLogger;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class EmployeeSelfServiceController extends Controller
{
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

    /**
     * Calculate working days excluding Saturdays, Sundays, and regional/global holidays.
     */
    private function calculateWorkingDays(User $user, Carbon $fromDate, Carbon $toDate)
    {
        $period = CarbonPeriod::create($fromDate, $toDate);
        $totalDays = 0;

        $holidays = Holiday::whereBetween('holiday_date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->where(function($q) use ($user) {
                $q->whereNull('location_id')
                  ->orWhere('location_id', $user->location_id);
            })
            ->pluck('holiday_date')
            ->map(fn($d) => $d->toDateString())
            ->toArray();

        foreach ($period as $date) {
            if ($date->isWeekend()) {
                continue;
            }
            if (in_array($date->toDateString(), $holidays)) {
                continue;
            }
            $totalDays++;
        }

        return $totalDays;
    }

    /**
     * Display Employee Dashboard.
     */
    public function dashboard(Request $request)
    {
        $user = auth()->user();
        $today = Carbon::today()->toDateString();
        
        // 1. Current Month Attendance Summary
        $startOfMonth = Carbon::today()->startOfMonth()->toDateString();
        $endOfMonth = Carbon::today()->endOfMonth()->toDateString();
        
        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('attendance_date', [$startOfMonth, $endOfMonth])
            ->get();
            
        $presentDays = $attendances->where('status', 'present')->count();
        $lateMarks = $attendances->where('status', 'late')->count();
        $halfDays = $attendances->where('status', 'half_day')->count();
        $absents = $attendances->where('status', 'absent')->count();
        
        // 2. Active Salary Package
        $activeSalary = $user->employeeSalary()->where('status', 'active')->first();
        
        // 3. Unread Notifications
        $unreadNotifications = Notification::where('user_id', $user->id)
            ->where('unread', 1)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        // 4. Leave Balances
        $this->initializeUserBalances($user, Carbon::now()->year);
        $leaveBalances = LeaveBalance::with('leavePolicy')
            ->where('employee_id', $user->id)
            ->where('year', Carbon::now()->year)
            ->get();

        return view('employee.dashboard', compact(
            'user', 'presentDays', 'lateMarks', 'halfDays', 'absents', 
            'activeSalary', 'unreadNotifications', 'leaveBalances'
        ));
    }

    /**
     * Display Payslip History.
     */
    public function payslips(Request $request)
    {
        $user = auth()->user();
        $payslips = Payroll::where('employee_id', $user->id)
            ->whereIn('status', ['Approved', 'Paid'])
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->paginate(12);

        return view('employee.payslips', compact('payslips'));
    }

    /**
     * Show a Single Payslip (Print/Preview).
     */
    public function payslip($id)
    {
        $user = auth()->user();
        $payroll = Payroll::with(['employee.department', 'employee.position', 'employee.location'])
            ->where('employee_id', $user->id)
            ->whereIn('status', ['Approved', 'Paid'])
            ->findOrFail($id);
            
        $monthName = date('F Y', mktime(0, 0, 0, $payroll->month, 10, $payroll->year));

        return view('employee.payslip_show', compact('payroll', 'monthName'));
    }

    /**
     * Display Leaves Portal (Submit & History).
     */
    public function leaves(Request $request)
    {
        $user = auth()->user();
        $year = Carbon::now()->year;
        
        $this->initializeUserBalances($user, $year);
        
        $balances = LeaveBalance::with('leavePolicy')
            ->where('employee_id', $user->id)
            ->where('year', $year)
            ->get();
            
        $applications = LeaveApplication::with(['leavePolicy', 'approver'])
            ->where('employee_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        $policies = LeavePolicy::where('status', 'active')->get();

        return view('employee.leaves', compact('balances', 'applications', 'policies'));
    }

    /**
     * Apply for Leave (Web).
     */
    public function applyLeave(Request $request)
    {
        $request->validate([
            'leave_policy_id' => 'required|exists:leave_policies,id',
            'from_date'       => 'required|date|after_or_equal:today',
            'to_date'         => 'required|date|after_or_equal:from_date',
            'reason'          => 'required|string|max:500',
            'attachment'      => 'nullable|file|mimes:pdf,jpg,png|max:5120',
        ]);

        $user = auth()->user();
        $policy = LeavePolicy::findOrFail($request->leave_policy_id);
        
        $fromDate = Carbon::parse($request->from_date);
        $toDate = Carbon::parse($request->to_date);
        $year = $fromDate->year;

        $totalDays = $this->calculateWorkingDays($user, $fromDate, $toDate);

        if ($totalDays <= 0) {
            return redirect()->back()->withErrors(['from_date' => 'Requested leave period consists only of weekends/holidays.']);
        }

        $this->initializeUserBalances($user, $year);
        $balance = LeaveBalance::where('employee_id', $user->id)
            ->where('leave_policy_id', $policy->id)
            ->where('year', $year)
            ->first();

        if ($policy->leave_type === 'paid') {
            if (!$balance || $balance->remaining_leave < $totalDays) {
                return redirect()->back()->withErrors(['leave_policy_id' => "Insufficient leave balance. Requested {$totalDays} days, available: " . ($balance ? $balance->remaining_leave : 0) . " days."]);
            }
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('leave_attachments', 'public');
        }

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

        // Audit Log
        AuditLog::create([
            'user_id' => $user->id,
            'module' => 'leaves',
            'action' => 'apply_leave',
            'new_data' => $application->toArray(),
            'ip_address' => $request->ip(),
            'device_info' => $request->userAgent(),
        ]);

        return redirect()->back()->with('success', 'Leave application submitted successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Mobile API Endpoints (Sanctum Protected)
    |--------------------------------------------------------------------------
    */

    /**
     * API: Get Salary History ( फाइनल Payslips).
     */
    public function apiSalaryHistory(Request $request)
    {
        $user = $request->user();
        $payslips = Payroll::where('employee_id', $user->id)
            ->whereIn('status', ['Approved', 'Paid'])
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $payslips
        ]);
    }

    /**
     * API: Get Specific Payslip Details.
     */
    public function apiPayslipDetails(Request $request, $id)
    {
        $user = $request->user();
        $payroll = Payroll::with(['employee.department', 'employee.position', 'employee.location'])
            ->where('employee_id', $user->id)
            ->whereIn('status', ['Approved', 'Paid'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $payroll
        ]);
    }

    /**
     * API: Get Notifications list.
     */
    public function apiNotifications(Request $request)
    {
        $user = $request->user();
        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $notifications
        ]);
    }

    /**
     * API: Mark Notification as Read.
     */
    public function apiReadNotification(Request $request, $id)
    {
        $user = $request->user();
        $notification = Notification::where('user_id', $user->id)->findOrFail($id);
        $notification->update(['unread' => 0]);

        return response()->json([
            'status' => 'success',
            'message' => 'Notification marked as read.'
        ]);
    }

    /**
     * API: Update Profile (Mobile & Personal info).
     */
    public function apiUpdateProfile(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'phone' => 'nullable|string|max:15',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $oldData = $user->toArray();
        
        if ($request->has('phone')) {
            $user->phone = $request->phone;
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        // Audit Log
        AuditLog::create([
            'user_id' => $user->id,
            'module' => 'profile',
            'action' => 'update_profile',
            'old_data' => $oldData,
            'new_data' => $user->toArray(),
            'ip_address' => $request->ip(),
            'device_info' => $request->userAgent(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully.',
            'data' => $user
        ]);
    }

    /**
     * API: Get Employee Profile with lifecycle details (employee type, status, contract, shift, salary info).
     */
    public function apiProfile(Request $request)
    {
        $user = $request->user();
        
        $profile = User::with([
            'department', 
            'location', 
            'position', 
            'reportingManager',
            'employeeSalary.salaryStructure',
            'contracts' => function($q) {
                $q->where('contract_status', 'active');
            }
        ])->findOrFail($user->id);

        $activeShift = \App\Models\ShiftAssignment::with('shift')
            ->where('employee_id', $user->id)
            ->whereNull('effective_to')
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $profile,
                'active_shift' => $activeShift ? $activeShift->shift : null,
                'active_contract' => $profile->contracts->first(),
            ]
        ]);
    }
}
