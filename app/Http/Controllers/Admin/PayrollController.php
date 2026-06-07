<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use App\Models\LeaveApplication;
use App\Models\LeavePolicy;
use App\Models\Holiday;
use App\Models\ShiftAssignment;
use App\Models\EmployeeSalary;
use App\Models\Payroll;
use App\Models\Setting;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    /**
     * Display payroll dashboard.
     */
    public function index(Request $request)
    {
        $month = $request->get('month', date('n'));
        $year = $request->get('year', date('Y'));

        $payrolls = Payroll::with(['employee.department'])
            ->where('month', $month)
            ->where('year', $year)
            ->get();

        // Get employees with active salaries to show who needs payroll generation
        $eligibleEmployees = User::where('role', 'employee')
            ->where('status', 'active')
            ->whereHas('employeeSalary')
            ->get();

        $generatedEmployeeIds = $payrolls->pluck('employee_id')->toArray();
        $missingCount = $eligibleEmployees->whereNotIn('id', $generatedEmployeeIds)->count();

        // Settings for calculation
        $latePenalty = Setting::get('late_penalty_per_mark', '100');
        $otRate = Setting::get('overtime_rate_per_hour', '150');

        return view('admin.payroll.index', compact('payrolls', 'month', 'year', 'eligibleEmployees', 'missingCount', 'latePenalty', 'otRate'));
    }

    /**
     * Generate payroll for a single employee or bulk employees.
     */
    public function generate(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2020',
            'employee_id' => 'nullable|exists:users,id',
            'bulk' => 'nullable|boolean',
        ]);

        $month = $request->input('month');
        $year = $request->input('year');
        $employeeId = $request->input('employee_id');
        $isBulk = $request->boolean('bulk', false);

        if ($isBulk) {
            // Get all active employees with active salaries
            $employees = User::where('role', 'employee')
                ->where('status', 'active')
                ->whereHas('employeeSalary')
                ->get();
        } else {
            $employees = User::where('id', $employeeId)->get();
        }

        if ($employees->isEmpty()) {
            return redirect()->back()->with('error', 'No active employees with assigned salaries found.');
        }

        $generatedCount = 0;

        foreach ($employees as $employee) {
            $result = $this->calculateAndSavePayroll($employee, $month, $year);
            if ($result) {
                $generatedCount++;
            }
        }

        // Audit Log
        AuditLog::create([
            'user_id' => auth()->id(),
            'module' => 'payroll',
            'action' => $isBulk ? 'bulk_generate' : 'individual_generate',
            'new_data' => [
                'month' => $month,
                'year' => $year,
                'count' => $generatedCount,
            ],
            'ip_address' => $request->ip(),
            'device_info' => $request->userAgent(),
        ]);

        return redirect()->back()->with('success', "Payroll generated successfully for {$generatedCount} employee(s).");
    }

    /**
     * Update TDS/Loan/Advance/Bonus/Incentives of a Draft payroll.
     */
    public function update(Request $request, $id)
    {
        $payroll = Payroll::findOrFail($id);
        if ($payroll->status !== 'Draft' && $payroll->status !== 'Generated') {
            return redirect()->back()->with('error', 'Payroll is locked and cannot be edited.');
        }

        $validated = $request->validate([
            'bonus' => 'required|numeric|min:0',
            'incentives' => 'required|numeric|min:0',
            'tds' => 'required|numeric|min:0',
            'loan_deduction' => 'required|numeric|min:0',
            'advance_salary' => 'required|numeric|min:0',
        ]);

        $oldData = $payroll->toArray();

        // Recalculate totals
        $payroll->bonus = $validated['bonus'];
        $payroll->incentives = $validated['incentives'];
        $payroll->tds = $validated['tds'];
        $payroll->loan_deduction = $validated['loan_deduction'];
        $payroll->advance_salary = $validated['advance_salary'];

        $payroll->total_earnings = $payroll->gross_salary + $payroll->overtime_amount + $payroll->bonus + $payroll->incentives;
        $payroll->total_deductions = $payroll->pf + $payroll->esic + $payroll->professional_tax + $payroll->tds + $payroll->loan_deduction + $payroll->advance_salary + $payroll->absent_deduction + $payroll->half_day_deduction + $payroll->late_penalty;
        $payroll->net_salary = max(0, $payroll->total_earnings - $payroll->total_deductions);

        $payroll->save();

        // Audit Log
        AuditLog::create([
            'user_id' => auth()->id(),
            'module' => 'payroll',
            'action' => 'edit_payroll_components',
            'old_data' => $oldData,
            'new_data' => $payroll->toArray(),
            'ip_address' => $request->ip(),
            'device_info' => $request->userAgent(),
        ]);

        return redirect()->back()->with('success', 'Payroll details updated successfully.');
    }

    /**
     * Workflow Approvals: Approve or Lock Payroll.
     */
    public function transition(Request $request, $id)
    {
        $payroll = Payroll::findOrFail($id);
        $action = $request->input('action'); // approve, pay, cancel

        $oldStatus = $payroll->status;

        if ($action === 'approve') {
            $payroll->status = 'Approved';
            $payroll->approved_by = auth()->id();
            $payroll->approved_at = now();

            // Create notification for employee
            \App\Models\Notification::create([
                'user_id' => $payroll->employee_id,
                'title' => 'Monthly Payslip Approved',
                'description' => 'Your payroll for ' . date('F Y', mktime(0, 0, 0, $payroll->month, 10, $payroll->year)) . ' has been approved. It will be credited soon.',
                'type' => 'payroll',
            ]);
        } elseif ($action === 'pay') {
            $payroll->status = 'Paid';
            $payroll->paid_at = now();

            // Create notification for employee
            \App\Models\Notification::create([
                'user_id' => $payroll->employee_id,
                'title' => 'Salary Disbursed',
                'description' => 'Your salary for ' . date('F Y', mktime(0, 0, 0, $payroll->month, 10, $payroll->year)) . ' has been credited to your bank account.',
                'type' => 'payroll',
            ]);
        } elseif ($action === 'cancel') {
            $payroll->status = 'Cancelled';
        }

        $payroll->save();

        // Audit Log
        AuditLog::create([
            'user_id' => auth()->id(),
            'module' => 'payroll',
            'action' => 'status_transition',
            'old_data' => ['status' => $oldStatus],
            'new_data' => ['status' => $payroll->status],
            'ip_address' => $request->ip(),
            'device_info' => $request->userAgent(),
        ]);

        return redirect()->back()->with('success', "Payroll status transitioned to {$payroll->status}.");
    }

    /**
     * Bulk Action: Transition all generated payrolls for a month.
     */
    public function bulkTransition(Request $request)
    {
        $request->validate([
            'month' => 'required|integer',
            'year' => 'required|integer',
            'action' => 'required|string|in:approve,pay',
        ]);

        $month = $request->input('month');
        $year = $request->input('year');
        $action = $request->input('action');

        $query = Payroll::where('month', $month)->where('year', $year);

        if ($action === 'approve') {
            $query->where('status', 'Draft')->update([
                'status' => 'Approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
            $msg = "All draft payrolls approved.";
        } elseif ($action === 'pay') {
            $query->where('status', 'Approved')->update([
                'status' => 'Paid',
                'paid_at' => now(),
            ]);
            $msg = "All approved payrolls marked as paid.";
        }

        // Notify employees
        $payrolls = Payroll::where('month', $month)->where('year', $year)->get();
        $monthName = date('F Y', mktime(0, 0, 0, $month, 10, $year));
        foreach ($payrolls as $pr) {
            \App\Models\Notification::create([
                'user_id' => $pr->employee_id,
                'title' => $action === 'approve' ? 'Monthly Payslip Approved' : 'Salary Disbursed',
                'description' => $action === 'approve' 
                    ? "Your payroll for {$monthName} has been approved." 
                    : "Your salary for {$monthName} has been credited.",
                'type' => 'payroll',
            ]);
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Preview single payslip in HTML/Print format.
     */
    public function show($id)
    {
        $payroll = Payroll::with(['employee.department', 'employee.position', 'employee.location'])->findOrFail($id);
        $monthName = date('F Y', mktime(0, 0, 0, $payroll->month, 10, $payroll->year));

        return view('admin.payroll.show', compact('payroll', 'monthName'));
    }

    /**
     * Compute and save payroll entries.
     */
    public function calculateAndSavePayroll($employee, $month, $year)
    {
        $daysInMonth = Carbon::create($year, $month)->daysInMonth;
        $startDate = Carbon::create($year, $month)->startOfMonth()->toDateString();
        $endDate = Carbon::create($year, $month)->endOfMonth()->toDateString();

        // 1. Check active salary
        $activeSalary = EmployeeSalary::where('employee_id', $employee->id)
            ->where('effective_from', '<=', $endDate)
            ->where('status', 'active')
            ->with('salaryStructure')
            ->first();

        if (!$activeSalary) {
            return false;
        }

        $structure = $activeSalary->salaryStructure;

        // 2. Fetch Attendances
        $attendances = Attendance::where('user_id', $employee->id)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->get();

        // 3. Fetch Leaves
        $leaves = LeaveApplication::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('from_date', [$startDate, $endDate])
                      ->orWhereBetween('to_date', [$startDate, $endDate])
                      ->orWhere(function ($q) use ($startDate, $endDate) {
                          $q->where('from_date', '<=', $startDate)
                            ->where('to_date', '>=', $endDate);
                      });
            })
            ->get();

        // 4. Fetch Holidays
        $holidays = Holiday::where(function ($q) use ($employee) {
            $q->whereNull('location_id')
              ->orWhere('location_id', $employee->location_id);
        })->whereBetween('holiday_date', [$startDate, $endDate])
          ->pluck('holiday_date')
          ->toArray();

        // 5. Fetch Shift assignment to identify weekly off days
        $shiftAssignment = ShiftAssignment::where('employee_id', $employee->id)
            ->where('effective_from', '<=', $endDate)
            ->where(function($q) use ($startDate) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $startDate);
            })
            ->with('shift')
            ->first();

        $weeklyOffs = $shiftAssignment && $shiftAssignment->shift 
            ? explode(',', $shiftAssignment->shift->weekly_off_days) 
            : ['Saturday', 'Sunday'];
        $weeklyOffs = array_map('trim', $weeklyOffs);

        // 6. Day by Day analysis
        $presentDaysCount = 0;
        $halfDaysCount = 0;
        $lateMarksCount = 0;
        $paidLeavesCount = 0;
        $unpaidLeavesCount = 0;
        $holidaysCount = 0;
        $weeklyOffsCount = 0;
        $absentDaysCount = 0;
        $overtimeHoursTotal = 0;

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $currentDate = Carbon::create($year, $month, $d);
            $dateString = $currentDate->toDateString();
            $dayName = $currentDate->format('l');

            // Check check-ins
            $att = $attendances->firstWhere('attendance_date', $dateString);
            if ($att) {
                if ($att->status == 'half_day') {
                    $halfDaysCount++;
                } elseif ($att->status == 'late') {
                    $lateMarksCount++;
                    $presentDaysCount++;
                } elseif ($att->status == 'present') {
                    $presentDaysCount++;
                }
                
                // Overtime
                if ($att->working_hours && $att->shift && $att->working_hours > $att->shift->minimum_working_hours) {
                    $overtimeHoursTotal += ($att->working_hours - $att->shift->minimum_working_hours);
                }
                continue;
            }

            // Check leaves
            $approvedLeave = null;
            foreach ($leaves as $lv) {
                if ($dateString >= $lv->from_date && $dateString <= $lv->to_date) {
                    $approvedLeave = $lv;
                    break;
                }
            }

            if ($approvedLeave) {
                $policy = LeavePolicy::find($approvedLeave->leave_policy_id);
                if ($policy && $policy->leave_type == 'paid') {
                    $paidLeavesCount++;
                } else {
                    $unpaidLeavesCount++;
                }
                continue;
            }

            // Check Holidays
            if (in_array($dateString, $holidays)) {
                $holidaysCount++;
                continue;
            }

            // Check Weekly Offs
            if (in_array($dayName, $weeklyOffs)) {
                $weeklyOffsCount++;
                continue;
            }

            // Unpaid absent
            $absentDaysCount++;
        }

        // Calculations
        $payableDays = $daysInMonth;
        $paidDays = $daysInMonth - $unpaidLeavesCount - $absentDaysCount - ($halfDaysCount * 0.5);

        // Earnings
        $gross = $activeSalary->gross_salary;
        $basic = round($gross * $structure->basic_percentage / 100, 2);
        $hra = round($gross * $structure->hra_percentage / 100, 2);
        $da = round($gross * $structure->da_percentage / 100, 2);
        $travel = $structure->travel_allowance;
        $special = max(0, $gross - ($basic + $hra + $da + $travel));

        // Deductions
        $perDaySalary = round($gross / $daysInMonth, 2);
        $absentDeduction = round($perDaySalary * ($absentDaysCount + $unpaidLeavesCount), 2);
        $halfDayDeduction = round($perDaySalary * 0.5 * $halfDaysCount, 2);

        $latePenaltyRate = (float) Setting::get('late_penalty_per_mark', '100');
        $latePenalty = round($lateMarksCount * $latePenaltyRate, 2);

        $otRate = (float) Setting::get('overtime_rate_per_hour', '150');
        $overtimeAmount = round($overtimeHoursTotal * $otRate, 2);

        $pf = $structure->pf_enabled ? round($basic * 0.12, 2) : 0.00;
        $esic = $structure->esic_enabled ? round($gross * 0.0075, 2) : 0.00;
        $pt = $structure->professional_tax;

        $totalEarnings = $gross + $overtimeAmount;
        $totalDeductions = $pf + $esic + $pt + $absentDeduction + $halfDayDeduction + $latePenalty;
        $netSalary = max(0, $totalEarnings - $totalDeductions);

        // Save to Database
        return Payroll::updateOrCreate(
            ['employee_id' => $employee->id, 'month' => $month, 'year' => $year],
            [
                'gross_salary' => $gross,
                'basic_salary' => $basic,
                'hra' => $hra,
                'da' => $da,
                'travel_allowance' => $travel,
                'special_allowance' => $special,
                'overtime_hours' => $overtimeHoursTotal,
                'overtime_amount' => $overtimeAmount,
                'absent_deduction' => $absentDeduction,
                'half_day_deduction' => $halfDayDeduction,
                'late_penalty' => $latePenalty,
                'pf' => $pf,
                'esic' => $esic,
                'professional_tax' => $pt,
                'total_earnings' => $totalEarnings,
                'total_deductions' => $totalDeductions,
                'net_salary' => $netSalary,
                'payable_days' => $payableDays,
                'paid_days' => $paidDays,
                'status' => 'Draft',
                'generated_at' => now(),
            ]
        );
    }

    /**
     * View for Payroll Reports and Analytics summary.
     */
    public function reports(Request $request)
    {
        $month = $request->get('month', date('n'));
        $year = $request->get('year', date('Y'));
        $departmentId = $request->get('department_id');

        $departments = \App\Models\Department::where('status', 'active')->get();

        $query = Payroll::with(['employee.department'])
            ->where('month', $month)
            ->where('year', $year);

        if ($departmentId) {
            $query->whereHas('employee', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        $payrolls = $query->get();

        // Summaries
        $totalNet = $payrolls->sum('net_salary');
        $totalGross = $payrolls->sum('gross_salary');
        $totalDeductions = $payrolls->sum('total_deductions');
        $totalOt = $payrolls->sum('overtime_amount');
        $employeeCount = $payrolls->count();

        // Department breakdown
        $deptSummary = $payrolls->groupBy(function($pr) {
            return $pr->employee->department ? $pr->employee->department->department_name : 'Unassigned';
        })->map(function($rows) {
            return [
                'count' => $rows->count(),
                'gross' => $rows->sum('gross_salary'),
                'deductions' => $rows->sum('total_deductions'),
                'net' => $rows->sum('net_salary'),
            ];
        });

        return view('admin.payroll.reports', compact(
            'payrolls', 'month', 'year', 'departmentId', 'departments',
            'totalNet', 'totalGross', 'totalDeductions', 'totalOt', 'employeeCount', 'deptSummary'
        ));
    }

    /**
     * Export reports in CSV, Excel, or HTML print format.
     */
    public function exportReport(Request $request, $type, $format)
    {
        $month = $request->get('month', date('n'));
        $year = $request->get('year', date('Y'));
        $departmentId = $request->get('department_id');

        $query = Payroll::with(['employee.department', 'employee.position'])
            ->where('month', $month)
            ->where('year', $year);

        if ($departmentId) {
            $query->whereHas('employee', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        $records = $query->get();
        $monthName = date('F_Y', mktime(0, 0, 0, $month, 10, $year));

        // Format is HTML print
        if ($format === 'pdf') {
            $title = ucwords(str_replace('_', ' ', $type)) . " Report (" . $monthName . ")";
            $summary = [];
            if ($type === 'attendance_summary') {
                foreach ($records as $r) {
                    $employee = $r->employee;
                    
                    // Run analysis
                    $daysInMonth = Carbon::create($year, $month)->daysInMonth;
                    $startDate = Carbon::create($year, $month)->startOfMonth()->toDateString();
                    $endDate = Carbon::create($year, $month)->endOfMonth()->toDateString();

                    $attendances = Attendance::where('user_id', $employee->id)
                        ->whereBetween('attendance_date', [$startDate, $endDate])
                        ->get();

                    $leaves = LeaveApplication::where('employee_id', $employee->id)
                        ->where('status', 'approved')
                        ->where(function ($query) use ($startDate, $endDate) {
                            $query->whereBetween('from_date', [$startDate, $endDate])
                                  ->orWhereBetween('to_date', [$startDate, $endDate])
                                  ->orWhere(function ($q) use ($startDate, $endDate) {
                                      $q->where('from_date', '<=', $startDate)
                                        ->where('to_date', '>=', $endDate);
                                  });
                        })
                        ->get();

                    $holidays = Holiday::where(function ($q) use ($employee) {
                        $q->whereNull('location_id')
                          ->orWhere('location_id', $employee->location_id);
                    })->whereBetween('holiday_date', [$startDate, $endDate])
                      ->pluck('holiday_date')
                      ->toArray();

                    $shiftAssignment = ShiftAssignment::where('employee_id', $employee->id)
                        ->where('effective_from', '<=', $endDate)
                        ->where(function($q) use ($startDate) {
                            $q->whereNull('effective_to')
                              ->orWhere('effective_to', '>=', $startDate);
                        })
                        ->with('shift')
                        ->first();

                    $weeklyOffs = $shiftAssignment && $shiftAssignment->shift 
                        ? explode(',', $shiftAssignment->shift->weekly_off_days) 
                        : ['Saturday', 'Sunday'];
                    $weeklyOffs = array_map('trim', $weeklyOffs);

                    $present = 0;
                    $late = 0;
                    $halfDay = 0;
                    $absent = 0;
                    $paidLeaves = 0;
                    $unpaidLeaves = 0;
                    $weeklyOffsCount = 0;

                    for ($d = 1; $d <= $daysInMonth; $d++) {
                        $currentDate = Carbon::create($year, $month, $d);
                        $dateString = $currentDate->toDateString();
                        $dayName = $currentDate->format('l');

                        $att = $attendances->firstWhere('attendance_date', $dateString);
                        if ($att) {
                            if ($att->status == 'half_day') {
                                $halfDay++;
                            } elseif ($att->status == 'late') {
                                $late++;
                                $present++;
                            } elseif ($att->status == 'present') {
                                $present++;
                            }
                            continue;
                        }

                        $approvedLeave = null;
                        foreach ($leaves as $lv) {
                            if ($dateString >= $lv->from_date && $dateString <= $lv->to_date) {
                                $approvedLeave = $lv;
                                break;
                            }
                        }

                        if ($approvedLeave) {
                            $policy = LeavePolicy::find($approvedLeave->leave_policy_id);
                            if ($policy && $policy->leave_type == 'paid') {
                                $paidLeaves++;
                            } else {
                                $unpaidLeaves++;
                            }
                            continue;
                        }

                        if (in_array($dateString, $holidays)) {
                            continue;
                        }

                        if (in_array($dayName, $weeklyOffs)) {
                            $weeklyOffsCount++;
                            continue;
                        }

                        $absent++;
                    }

                    $summary[] = (object)[
                        'employee_code' => $employee->employee_code ?? 'N/A',
                        'name' => $employee->name,
                        'department' => $employee->department->department_name ?? 'N/A',
                        'present' => $present,
                        'late' => $late,
                        'half_day' => $halfDay,
                        'absent' => $absent,
                        'weekly_offs' => $weeklyOffsCount,
                        'paid_leaves' => $paidLeaves,
                        'unpaid_leaves' => $unpaidLeaves,
                        'paid_days' => $r->paid_days,
                        'days_in_month' => $daysInMonth
                    ];
                }
            }
            return view('admin.payroll.print_report', compact('records', 'type', 'title', 'month', 'year', 'summary'));
        }

        // CSV/Excel stream
        $filename = "payroll_report_" . $type . "_" . $monthName . "_" . date('Ymd_His') . ($format === 'excel' ? '.xls' : '.csv');
        
        $headers = [
            "Content-type"        => $format === 'excel' ? "application/vnd.ms-excel" : "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($type, $records) {
            $file = fopen('php://output', 'w');

            if ($type === 'monthly_payroll') {
                fputcsv($file, ['Employee Code', 'Employee Name', 'Department', 'Gross Salary', 'Basic Salary', 'HRA', 'DA', 'Travel Allowance', 'Special Allowance', 'Overtime Pay', 'Bonus', 'Incentives', 'PF Deduction', 'ESIC Deduction', 'Professional Tax', 'TDS', 'Loan Repay', 'Advance Deduct', 'Net Salary', 'Paid Days', 'Status']);
                foreach ($records as $r) {
                    fputcsv($file, [
                        $r->employee->employee_code ?? 'N/A',
                        $r->employee->name,
                        $r->employee->department->department_name ?? 'N/A',
                        $r->gross_salary,
                        $r->basic_salary,
                        $r->hra,
                        $r->da,
                        $r->travel_allowance,
                        $r->special_allowance,
                        $r->overtime_amount,
                        $r->bonus,
                        $r->incentives,
                        $r->pf,
                        $r->esic,
                        $r->professional_tax,
                        $r->tds,
                        $r->loan_deduction,
                        $r->advance_salary,
                        $r->net_salary,
                        $r->paid_days,
                        $r->status,
                    ]);
                }
            } elseif ($type === 'department_payroll') {
                fputcsv($file, ['Department Name', 'Employee Count', 'Total Gross Expense', 'Total Deductions', 'Total Net Disbursed']);
                $deptSummary = $records->groupBy(function($pr) {
                    return $pr->employee->department ? $pr->employee->department->department_name : 'Unassigned';
                });
                foreach ($deptSummary as $dept => $rows) {
                    fputcsv($file, [
                        $dept,
                        $rows->count(),
                        $rows->sum('gross_salary'),
                        $rows->sum('total_deductions'),
                        $rows->sum('net_salary')
                    ]);
                }
            } elseif ($type === 'deduction_report') {
                fputcsv($file, ['Employee Code', 'Employee Name', 'Department', 'PF Deduction', 'ESIC Deduction', 'Professional Tax (PT)', 'Income Tax (TDS)', 'Loan Repayments', 'Advance Salary Deduct', 'Total Deductions']);
                foreach ($records as $r) {
                    fputcsv($file, [
                        $r->employee->employee_code ?? 'N/A',
                        $r->employee->name,
                        $r->employee->department->department_name ?? 'N/A',
                        $r->pf,
                        $r->esic,
                        $r->professional_tax,
                        $r->tds,
                        $r->loan_deduction,
                        $r->advance_salary,
                        $r->total_deductions,
                    ]);
                }
            } elseif ($type === 'attendance_impact') {
                fputcsv($file, ['Employee Code', 'Employee Name', 'Calendar Days', 'Paid Days', 'Unpaid Absents/Leaves', 'Absent Deductions', 'Half-day Deductions', 'Late Check-in Penalties', 'Total Impact']);
                foreach ($records as $r) {
                    $unpaid = $r->payable_days - $r->paid_days;
                    $impact = $r->absent_deduction + $r->half_day_deduction + $r->late_penalty;
                    fputcsv($file, [
                        $r->employee->employee_code ?? 'N/A',
                        $r->employee->name,
                        $r->payable_days,
                        $r->paid_days,
                        $unpaid,
                        $r->absent_deduction,
                        $r->half_day_deduction,
                        $r->late_penalty,
                        $impact
                    ]);
                }
            } elseif ($type === 'overtime_report') {
                fputcsv($file, ['Employee Code', 'Employee Name', 'Department', 'Overtime Hours (Hrs)', 'Overtime Rate/Hr', 'Overtime Payout (Rs)']);
                $otRate = (float) Setting::get('overtime_rate_per_hour', '150');
                foreach ($records as $r) {
                    fputcsv($file, [
                        $r->employee->employee_code ?? 'N/A',
                        $r->employee->name,
                        $r->employee->department->department_name ?? 'N/A',
                        $r->overtime_hours,
                        $otRate,
                        $r->overtime_amount,
                    ]);
                }
            } elseif ($type === 'salary_revision') {
                fputcsv($file, ['Revision Date', 'Employee Code', 'Employee Name', 'Previous Gross', 'New Gross', 'Increment Amount', 'Revised By', 'Remarks']);
                
                $revisions = \App\Models\SalaryRevision::with(['employee.department', 'revisedBy'])
                    ->whereBetween('effective_date', [
                        Carbon::create($year, $month)->startOfMonth()->toDateString(),
                        Carbon::create($year, $month)->endOfMonth()->toDateString()
                    ])->get();
                
                foreach ($revisions as $rev) {
                    fputcsv($file, [
                        $rev->effective_date->format('Y-m-d'),
                        $rev->employee->employee_code ?? 'N/A',
                        $rev->employee->name,
                        $rev->previous_gross_salary,
                        $rev->new_gross_salary,
                        $rev->new_gross_salary - $rev->previous_gross_salary,
                        $rev->revisedBy->name ?? 'System',
                        $rev->remarks ?? ''
                    ]);
                }
            } elseif ($type === 'attendance_summary') {
                fputcsv($file, ['Employee Code', 'Employee Name', 'Department', 'Present Days', 'Late Marks', 'Half Days', 'Absent Days', 'Weekly Offs', 'Paid Leaves', 'Unpaid Leaves', 'Paid Days', 'Total Days in Month']);
                foreach ($records as $r) {
                    $employee = $r->employee;
                    
                    // Run a quick day-by-day analysis matching the payroll calculateAndSavePayroll logic
                    $daysInMonth = Carbon::create($year, $month)->daysInMonth;
                    $startDate = Carbon::create($year, $month)->startOfMonth()->toDateString();
                    $endDate = Carbon::create($year, $month)->endOfMonth()->toDateString();

                    $attendances = Attendance::where('user_id', $employee->id)
                        ->whereBetween('attendance_date', [$startDate, $endDate])
                        ->get();

                    $leaves = LeaveApplication::where('employee_id', $employee->id)
                        ->where('status', 'approved')
                        ->where(function ($query) use ($startDate, $endDate) {
                            $query->whereBetween('from_date', [$startDate, $endDate])
                                  ->orWhereBetween('to_date', [$startDate, $endDate])
                                  ->orWhere(function ($q) use ($startDate, $endDate) {
                                      $q->where('from_date', '<=', $startDate)
                                        ->where('to_date', '>=', $endDate);
                                  });
                        })
                        ->get();

                    $holidays = Holiday::where(function ($q) use ($employee) {
                        $q->whereNull('location_id')
                          ->orWhere('location_id', $employee->location_id);
                    })->whereBetween('holiday_date', [$startDate, $endDate])
                      ->pluck('holiday_date')
                      ->toArray();

                    $shiftAssignment = ShiftAssignment::where('employee_id', $employee->id)
                        ->where('effective_from', '<=', $endDate)
                        ->where(function($q) use ($startDate) {
                            $q->whereNull('effective_to')
                              ->orWhere('effective_to', '>=', $startDate);
                        })
                        ->with('shift')
                        ->first();

                    $weeklyOffs = $shiftAssignment && $shiftAssignment->shift 
                        ? explode(',', $shiftAssignment->shift->weekly_off_days) 
                        : ['Saturday', 'Sunday'];
                    $weeklyOffs = array_map('trim', $weeklyOffs);

                    $present = 0;
                    $late = 0;
                    $halfDay = 0;
                    $absent = 0;
                    $paidLeaves = 0;
                    $unpaidLeaves = 0;
                    $weeklyOffsCount = 0;

                    for ($d = 1; $d <= $daysInMonth; $d++) {
                        $currentDate = Carbon::create($year, $month, $d);
                        $dateString = $currentDate->toDateString();
                        $dayName = $currentDate->format('l');

                        $att = $attendances->firstWhere('attendance_date', $dateString);
                        if ($att) {
                            if ($att->status == 'half_day') {
                                $halfDay++;
                            } elseif ($att->status == 'late') {
                                $late++;
                                $present++;
                            } elseif ($att->status == 'present') {
                                $present++;
                            }
                            continue;
                        }

                        $approvedLeave = null;
                        foreach ($leaves as $lv) {
                            if ($dateString >= $lv->from_date && $dateString <= $lv->to_date) {
                                $approvedLeave = $lv;
                                break;
                            }
                        }

                        if ($approvedLeave) {
                            $policy = LeavePolicy::find($approvedLeave->leave_policy_id);
                            if ($policy && $policy->leave_type == 'paid') {
                                $paidLeaves++;
                            } else {
                                $unpaidLeaves++;
                            }
                            continue;
                        }

                        if (in_array($dateString, $holidays)) {
                            continue;
                        }

                        if (in_array($dayName, $weeklyOffs)) {
                            $weeklyOffsCount++;
                            continue;
                        }

                        $absent++;
                    }

                    fputcsv($file, [
                        $employee->employee_code ?? 'N/A',
                        $employee->name,
                        $employee->department->department_name ?? 'N/A',
                        $present,
                        $late,
                        $halfDay,
                        $absent,
                        $weeklyOffsCount,
                        $paidLeaves,
                        $unpaidLeaves,
                        $r->paid_days,
                        $daysInMonth
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

