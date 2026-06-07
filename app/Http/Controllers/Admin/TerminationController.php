<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\EmployeeTermination;
use App\Models\EmployeeContract;
use App\Models\LeaveBalance;
use App\Models\AuditLog;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TerminationController extends Controller
{
    public function index(Request $request)
    {
        $query = EmployeeTermination::with(['employee.department', 'terminatedBy']);

        if ($request->filled('exit_status')) {
            $query->where('exit_status', $request->exit_status);
        }

        if ($request->filled('termination_type')) {
            $query->where('termination_type', $request->termination_type);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('last_working_date', [$request->start_date, $request->end_date]);
        }

        $terminations = $query->orderBy('last_working_date', 'desc')->paginate(10);

        return view('admin.terminations.index', compact('terminations'));
    }

    public function create($employeeId)
    {
        $employee = User::with(['department', 'employeeSalary'])->findOrFail($employeeId);

        if ($employee->isTerminated()) {
            return redirect()->route('admin.employees.index')->with('error', 'This employee has already been terminated.');
        }

        // Fetch remaining leaves
        $remainingLeaves = LeaveBalance::where('employee_id', $employeeId)
            ->where('year', Carbon::now()->year)
            ->sum('remaining_leave');

        // Estimate recommended leave encashment if salary exists
        $grossSalary = $employee->employeeSalary ? $employee->employeeSalary->gross_salary : 0;
        $dailyRate = $grossSalary > 0 ? ($grossSalary / 30.0) : 0;
        $recommendedEncashment = round($remainingLeaves * $dailyRate, 2);

        return view('admin.terminations.create', compact('employee', 'remainingLeaves', 'grossSalary', 'recommendedEncashment'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:users,id',
            'termination_type' => 'required|string|in:resigned,terminated,absconded,retired,contract_completed',
            'termination_reason' => 'required|string',
            'last_working_date' => 'required|date',
            'notice_period_days' => 'required|integer|min:0',
            'pending_salary' => 'required|numeric|min:0',
            'leave_encashment' => 'required|numeric|min:0',
            'remarks' => 'nullable|string',
        ]);

        $employee = User::findOrFail($request->employee_id);

        if ($employee->isTerminated()) {
            return redirect()->route('admin.employees.index')->with('error', 'This employee is already terminated.');
        }

        $termination = DB::transaction(function () use ($request, $employee) {
            $lastWorkingDate = Carbon::parse($request->last_working_date);
            $isFuture = $lastWorkingDate->isAfter(Carbon::today());

            // 1. Update Employee Status
            // If the last working date is in the future, set status to notice_period. Else terminated.
            if ($isFuture) {
                $employee->employment_status = 'notice_period';
            } else {
                $employee->employment_status = $request->termination_type;
                $employee->status = 'inactive';
                $employee->device_id = null; // Clear face recognition binding
                
                // Revoke Sanctum tokens
                $employee->tokens()->delete();
            }
            $employee->save();

            // 2. Terminate Active Contracts if any
            EmployeeContract::where('employee_id', $employee->id)
                ->where('contract_status', 'active')
                ->update(['contract_status' => 'terminated']);

            // 3. Create Termination Record
            $termination = EmployeeTermination::create([
                'employee_id' => $employee->id,
                'termination_type' => $request->termination_type,
                'termination_reason' => $request->termination_reason,
                'last_working_date' => $request->last_working_date,
                'notice_period_days' => $request->notice_period_days,
                'exit_status' => 'initiated',
                'final_settlement_status' => 'pending',
                'pending_salary' => $request->pending_salary,
                'leave_encashment' => $request->leave_encashment,
                'asset_return_status' => 'pending',
                'exit_interview_status' => 'pending',
                'remarks' => $request->remarks,
                'terminated_by' => auth()->id(),
                'terminated_at' => Carbon::now(),
            ]);

            // 4. Create Audit Log
            AuditLog::create([
                'user_id' => auth()->id(),
                'module' => 'employee_terminations',
                'action' => 'initiate_termination',
                'new_data' => $termination->toArray(),
                'ip_address' => $request->ip(),
                'device_info' => $request->userAgent(),
            ]);

            // 5. Send Notification
            Notification::create([
                'user_id' => $employee->id,
                'title' => 'Exit Process Initiated',
                'description' => 'Your exit process has been initiated with the last working date set as ' . $request->last_working_date,
                'type' => 'announcement',
            ]);

            return $termination;
        });

        return redirect()->route('admin.terminations.index')->with('success', 'Termination and exit process initiated successfully.');
    }

    public function show($id)
    {
        $termination = EmployeeTermination::with(['employee.department', 'employee.position', 'employee.reportingManager', 'terminatedBy'])->findOrFail($id);

        return view('admin.terminations.show', compact('termination'));
    }

    public function update(Request $request, $id)
    {
        $termination = EmployeeTermination::findOrFail($id);

        $request->validate([
            'exit_status' => 'required|string|in:initiated,in_progress,completed',
            'final_settlement_status' => 'required|string|in:pending,processed,paid',
            'asset_return_status' => 'required|string|in:pending,partial,completed',
            'exit_interview_status' => 'required|string|in:pending,scheduled,completed,skipped',
            'exit_interview_notes' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $termination) {
            $oldData = $termination->toArray();

            $termination->update([
                'exit_status' => $request->exit_status,
                'final_settlement_status' => $request->final_settlement_status,
                'asset_return_status' => $request->asset_return_status,
                'exit_interview_status' => $request->exit_interview_status,
                'exit_interview_notes' => $request->exit_interview_notes,
                'remarks' => $request->remarks,
            ]);

            // If exit status is completed, fully deactivate employee
            if ($request->exit_status === 'completed') {
                $employee = $termination->employee;
                $employee->employment_status = $termination->termination_type;
                $employee->status = 'inactive';
                $employee->device_id = null;
                $employee->save();
                
                // Revoke Sanctum tokens
                $employee->tokens()->delete();
            }

            // Create Audit Log
            AuditLog::create([
                'user_id' => auth()->id(),
                'module' => 'employee_terminations',
                'action' => 'update_exit_status',
                'old_data' => $oldData,
                'new_data' => $termination->fresh()->toArray(),
                'ip_address' => $request->ip(),
                'device_info' => $request->userAgent(),
            ]);
        });

        return redirect()->route('admin.terminations.show', $id)->with('success', 'Exit management checklists and settlement status updated.');
    }

    public function reports(Request $request)
    {
        // Monthly terminations count
        $monthlyStats = EmployeeTermination::select(
            DB::raw('count(id) as count'),
            DB::raw("DATE_FORMAT(last_working_date, '%Y-%m') as month")
        )
        ->groupBy('month')
        ->orderBy('month', 'desc')
        ->take(12)
        ->get();

        // Termination types count
        $typeStats = EmployeeTermination::select(
            'termination_type',
            DB::raw('count(id) as count')
        )
        ->groupBy('termination_type')
        ->get();

        // Settled vs pending counts
        $settlementStats = EmployeeTermination::select(
            'final_settlement_status',
            DB::raw('count(id) as count'),
            DB::raw('sum(pending_salary + leave_encashment) as total_amount')
        )
        ->groupBy('final_settlement_status')
        ->get();

        return view('admin.terminations.reports', compact('monthlyStats', 'typeStats', 'settlementStats'));
    }

    public function generateExitSummary($id)
    {
        $termination = EmployeeTermination::with(['employee.department', 'employee.position', 'employee.reportingManager', 'terminatedBy'])->findOrFail($id);

        return view('admin.terminations.print', compact('termination'));
    }
}
