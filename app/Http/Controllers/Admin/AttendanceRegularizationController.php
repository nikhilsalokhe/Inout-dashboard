<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceRegularization;
use App\Models\ShiftAssignment;
use App\Models\Payroll;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Http\Controllers\Admin\PayrollController;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceRegularizationController extends Controller
{
    /**
     * Show list of regularization requests.
     */
    public function index(Request $request)
    {
        $status     = $request->get('status');
        $employeeId = $request->get('employee_id');

        $employees = User::where('role', 'employee')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $query = AttendanceRegularization::with(['employee.department', 'approver', 'attendance'])
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }
        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        $regularizations = $query->paginate(15);

        return view('admin.regularizations.index', compact(
            'regularizations', 'employees', 'status', 'employeeId'
        ));
    }

    /**
     * Admin manually creates a new regularization entry for an employee.
     */
    public function store(Request $request)
    {
        $request->validate([
            'employee_id'     => 'required|exists:users,id',
            'attendance_date' => 'required|date',
            'check_in'        => 'required|date_format:H:i',
            'check_out'       => 'nullable|date_format:H:i|after:check_in',
            'reason'          => 'required|string|max:500',
        ]);

        // Prevent duplicate pending on the same date for the same employee
        $exists = AttendanceRegularization::where('employee_id', $request->employee_id)
            ->where('attendance_date', $request->attendance_date)
            ->where('status', 'pending')
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->with('error', 'A pending regularization request already exists for this employee on that date.');
        }

        $checkIn  = Carbon::parse($request->attendance_date . ' ' . $request->check_in);
        $checkOut = $request->check_out
            ? Carbon::parse($request->attendance_date . ' ' . $request->check_out)
            : null;

        AttendanceRegularization::create([
            'employee_id'     => $request->employee_id,
            'attendance_date' => $request->attendance_date,
            'check_in'        => $checkIn,
            'check_out'       => $checkOut,
            'reason'          => $request->reason,
            'status'          => 'pending',
        ]);

        return redirect()->back()->with('success', 'Regularization entry created successfully.');
    }

    /**
     * Admin edits the check-in/check-out on a pending regularization request.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'employee_id'     => 'required|exists:users,id',
            'attendance_date' => 'required|date',
            'check_in'        => 'required|date_format:H:i',
            'check_out'       => 'nullable|date_format:H:i|after:check_in',
            'reason'          => 'required|string|max:500',
        ]);

        $reg = AttendanceRegularization::findOrFail($id);

        if ($reg->status !== 'pending') {
            return redirect()->back()->with('error', 'Only pending requests can be edited.');
        }

        // Prevent duplicate pending requests on the same date for the same employee (excluding current record)
        $exists = AttendanceRegularization::where('employee_id', $request->employee_id)
            ->where('attendance_date', $request->attendance_date)
            ->where('status', 'pending')
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->with('error', 'A pending regularization request already exists for this employee on that date.');
        }

        $dateStr       = Carbon::parse($request->attendance_date)->toDateString();
        $reg->employee_id     = $request->employee_id;
        $reg->attendance_date = $request->attendance_date;
        $reg->check_in        = Carbon::parse($dateStr . ' ' . $request->check_in);
        $reg->check_out       = $request->check_out
            ? Carbon::parse($dateStr . ' ' . $request->check_out)
            : null;
        $reg->reason          = $request->reason;

        $reg->save();

        return redirect()->back()->with('success', 'Regularization request updated successfully.');
    }

    /**
     * Approve or Reject a regularization request.
     * Optionally override timings in-line before approving.
     */
    public function action(Request $request, $id)
    {
        $request->validate([
            'action'    => 'required|string|in:approve,reject',
            'remarks'   => 'nullable|string|max:500',
            'check_in'  => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
        ]);

        $reg = AttendanceRegularization::findOrFail($id);

        if ($reg->status !== 'pending') {
            return redirect()->back()->with('error', 'This request has already been processed.');
        }

        // Allow admin to override timings at approval time
        $dateStr = $reg->attendance_date->toDateString();
        if ($request->filled('check_in')) {
            $reg->check_in = Carbon::parse($dateStr . ' ' . $request->check_in);
        }
        if ($request->filled('check_out')) {
            $reg->check_out = Carbon::parse($dateStr . ' ' . $request->check_out);
        }

        $action  = $request->input('action');
        $remarks = $request->input('remarks');
        $user    = auth()->user();

        DB::beginTransaction();

        try {
            if ($action === 'approve') {
                $reg->status      = 'approved';
                $reg->remarks     = $remarks;
                $reg->approved_by = $user->id;
                $reg->approved_at = now();
                $reg->save();

                // Find or create attendance record
                $attendance = Attendance::where('user_id', $reg->employee_id)
                    ->where('attendance_date', $reg->attendance_date)
                    ->first();

                if (!$attendance) {
                    $attendance                  = new Attendance();
                    $attendance->user_id         = $reg->employee_id;
                    $attendance->attendance_date = $reg->attendance_date;
                }

                $attendance->check_in  = $reg->check_in;
                $attendance->check_out = $reg->check_out;

                // Resolve shift for the target date
                $assignment = ShiftAssignment::with('shift')
                    ->where('employee_id', $reg->employee_id)
                    ->where('effective_from', '<=', $reg->attendance_date)
                    ->where(function ($q) use ($reg) {
                        $q->whereNull('effective_to')
                          ->orWhere('effective_to', '>=', $reg->attendance_date);
                    })
                    ->first();

                $shift = $assignment ? $assignment->shift : null;
                if ($shift) {
                    $attendance->shift_id = $shift->id;
                }

                // Recalculate status & working hours
                $calc = Attendance::calculateStatusAndHours(
                    Carbon::parse($reg->check_in),
                    $reg->check_out ? Carbon::parse($reg->check_out) : null,
                    $shift
                );

                $attendance->working_hours = $calc['working_hours'];
                $attendance->status        = $calc['status'];
                $attendance->remarks       = 'Regularized: ' . $calc['remarks']
                    . ($remarks ? ' | Admin Note: ' . $remarks : '');
                $attendance->save();

                $reg->attendance_id = $attendance->id;
                $reg->save();

                // Recalculate draft payroll if it exists for that month
                $month = Carbon::parse($reg->attendance_date)->month;
                $year  = Carbon::parse($reg->attendance_date)->year;

                $payroll = Payroll::where('employee_id', $reg->employee_id)
                    ->where('month', $month)
                    ->where('year', $year)
                    ->where('status', 'Draft')
                    ->first();

                if ($payroll) {
                    $payrollController = new PayrollController();
                    $employee = User::find($reg->employee_id);
                    if ($employee) {
                        $payrollController->calculateAndSavePayroll($employee, $month, $year);
                    }
                }

                Notification::create([
                    'user_id'     => $reg->employee_id,
                    'title'       => 'Regularization Request Approved',
                    'description' => 'Your attendance regularization request for '
                        . Carbon::parse($reg->attendance_date)->format('M d, Y')
                        . ' has been approved.',
                    'type' => 'attendance',
                ]);

            } else {
                $reg->status      = 'rejected';
                $reg->remarks     = $remarks;
                $reg->approved_by = $user->id;
                $reg->approved_at = now();
                $reg->save();

                Notification::create([
                    'user_id'     => $reg->employee_id,
                    'title'       => 'Regularization Request Rejected',
                    'description' => 'Your attendance regularization request for '
                        . Carbon::parse($reg->attendance_date)->format('M d, Y')
                        . ' has been rejected. Remarks: ' . ($remarks ?: 'None'),
                    'type' => 'attendance',
                ]);
            }

            AuditLog::create([
                'user_id'     => $user->id,
                'module'      => 'attendance_regularization',
                'action'      => $action,
                'new_data'    => $reg->toArray(),
                'ip_address'  => $request->ip(),
                'device_info' => $request->userAgent(),
            ]);

            DB::commit();

            return redirect()->back()
                ->with('success', 'Regularization request marked as ' . $reg->status . ' successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to process request: ' . $e->getMessage());
        }
    }
}
