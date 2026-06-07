<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-white text-slate-800 p-8" onload="window.print()">

    <div class="mb-6 flex justify-between items-center border-b border-slate-200 pb-4">
        <div>
            <h1 class="text-lg font-extrabold text-slate-900">{{ $title }}</h1>
            <p class="text-xs text-slate-400 font-bold uppercase mt-1">InOut HRMS Reporting System</p>
        </div>
        <div class="text-right">
            <span class="text-xs font-bold text-slate-500">Generated: {{ date('M d, Y h:i A') }}</span>
        </div>
    </div>

    <!-- Reports Table -->
    <div class="overflow-x-auto text-xs">
        <table class="w-full text-left border-collapse border border-slate-200">
            
            @if($type === 'monthly_payroll')
                <thead>
                    <tr class="bg-slate-100 border-b border-slate-200 font-bold text-slate-700">
                        <th class="p-3 border">Employee</th>
                        <th class="p-3 border">Department</th>
                        <th class="p-3 border">Gross Base</th>
                        <th class="p-3 border">Overtime</th>
                        <th class="p-3 border">Bonus/Inc</th>
                        <th class="p-3 border">Deductions</th>
                        <th class="p-3 border">Net Salary</th>
                        <th class="p-3 border">Paid Days</th>
                        <th class="p-3 border">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                    @foreach($records as $r)
                        <tr>
                            <td class="p-3 border font-bold">{{ $r->employee->name }}</td>
                            <td class="p-3 border">{{ $r->employee->department->department_name ?? 'N/A' }}</td>
                            <td class="p-3 border">Rs. {{ number_format($r->gross_salary, 2) }}</td>
                            <td class="p-3 border">Rs. {{ number_format($r->overtime_amount, 2) }}</td>
                            <td class="p-3 border">Rs. {{ number_format($r->bonus + $r->incentives, 2) }}</td>
                            <td class="p-3 border text-rose-600">Rs. {{ number_format($r->total_deductions, 2) }}</td>
                            <td class="p-3 border text-emerald-600 font-extrabold">Rs. {{ number_format($r->net_salary, 2) }}</td>
                            <td class="p-3 border">{{ $r->paid_days }} days</td>
                            <td class="p-3 border">{{ $r->status }}</td>
                        </tr>
                    @endforeach
                </tbody>

            @elseif($type === 'department_payroll')
                <thead>
                    <tr class="bg-slate-100 border-b border-slate-200 font-bold text-slate-700">
                        <th class="p-3 border">Department</th>
                        <th class="p-3 border">Employee Count</th>
                        <th class="p-3 border">Total Gross Expense</th>
                        <th class="p-3 border">Total Deductions</th>
                        <th class="p-3 border">Total Net Disbursed</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                    @php
                        $deptSummary = $records->groupBy(function($pr) {
                            return $pr->employee->department ? $pr->employee->department->department_name : 'Unassigned';
                        });
                    @endphp
                    @foreach($deptSummary as $dept => $rows)
                        <tr>
                            <td class="p-3 border font-bold">{{ $dept }}</td>
                            <td class="p-3 border">{{ $rows->count() }}</td>
                            <td class="p-3 border">Rs. {{ number_format($rows->sum('gross_salary'), 2) }}</td>
                            <td class="p-3 border text-rose-600">Rs. {{ number_format($rows->sum('total_deductions'), 2) }}</td>
                            <td class="p-3 border text-emerald-600 font-extrabold">Rs. {{ number_format($rows->sum('net_salary'), 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>

            @elseif($type === 'deduction_report')
                <thead>
                    <tr class="bg-slate-100 border-b border-slate-200 font-bold text-slate-700">
                        <th class="p-3 border">Employee</th>
                        <th class="p-3 border">PF</th>
                        <th class="p-3 border">ESIC</th>
                        <th class="p-3 border">PT</th>
                        <th class="p-3 border">TDS</th>
                        <th class="p-3 border">Loan</th>
                        <th class="p-3 border">Other Deductions</th>
                        <th class="p-3 border">Total Deductions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                    @foreach($records as $r)
                        <tr>
                            <td class="p-3 border font-bold">{{ $r->employee->name }}</td>
                            <td class="p-3 border">Rs. {{ number_format($r->pf, 2) }}</td>
                            <td class="p-3 border">Rs. {{ number_format($r->esic, 2) }}</td>
                            <td class="p-3 border">Rs. {{ number_format($r->professional_tax, 2) }}</td>
                            <td class="p-3 border text-rose-600">Rs. {{ number_format($r->tds, 2) }}</td>
                            <td class="p-3 border text-rose-600">Rs. {{ number_format($r->loan_deduction, 2) }}</td>
                            <td class="p-3 border text-rose-600">Rs. {{ number_format($r->absent_deduction + $r->half_day_deduction + $r->late_penalty, 2) }}</td>
                            <td class="p-3 border text-rose-600 font-bold">Rs. {{ number_format($r->total_deductions, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>

            @elseif($type === 'attendance_impact')
                <thead>
                    <tr class="bg-slate-100 border-b border-slate-200 font-bold text-slate-700">
                        <th class="p-3 border">Employee</th>
                        <th class="p-3 border">Calendar Days</th>
                        <th class="p-3 border">Paid Days</th>
                        <th class="p-3 border">Lop Days</th>
                        <th class="p-3 border">Absent Deduction</th>
                        <th class="p-3 border">Half-Day Deduction</th>
                        <th class="p-3 border">Late Penalty</th>
                        <th class="p-3 border">Total Deduction Impact</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                    @foreach($records as $r)
                        @php
                            $unpaid = $r->payable_days - $r->paid_days;
                            $impact = $r->absent_deduction + $r->half_day_deduction + $r->late_penalty;
                        @endphp
                        <tr>
                            <td class="p-3 border font-bold">{{ $r->employee->name }}</td>
                            <td class="p-3 border">{{ $r->payable_days }}</td>
                            <td class="p-3 border text-emerald-600">{{ $r->paid_days }}</td>
                            <td class="p-3 border text-rose-600">{{ $unpaid }}</td>
                            <td class="p-3 border">Rs. {{ number_format($r->absent_deduction, 2) }}</td>
                            <td class="p-3 border">Rs. {{ number_format($r->half_day_deduction, 2) }}</td>
                            <td class="p-3 border">Rs. {{ number_format($r->late_penalty, 2) }}</td>
                            <td class="p-3 border text-rose-600 font-extrabold">Rs. {{ number_format($impact, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>

            @elseif($type === 'overtime_report')
                <thead>
                    <tr class="bg-slate-100 border-b border-slate-200 font-bold text-slate-700">
                        <th class="p-3 border">Employee</th>
                        <th class="p-3 border">Department</th>
                        <th class="p-3 border">Overtime Hours</th>
                        <th class="p-3 border">Overtime Payout</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                    @foreach($records as $r)
                        <tr>
                            <td class="p-3 border font-bold">{{ $r->employee->name }}</td>
                            <td class="p-3 border">{{ $r->employee->department->department_name ?? 'N/A' }}</td>
                            <td class="p-3 border">{{ $r->overtime_hours }} hrs</td>
                            <td class="p-3 border text-emerald-600 font-extrabold">Rs. {{ number_format($r->overtime_amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>

            @elseif($type === 'salary_revision')
                <thead>
                    <tr class="bg-slate-100 border-b border-slate-200 font-bold text-slate-700">
                        <th class="p-3 border">Effective Date</th>
                        <th class="p-3 border">Employee Code</th>
                        <th class="p-3 border">Employee Name</th>
                        <th class="p-3 border">Previous Gross</th>
                        <th class="p-3 border">New Gross</th>
                        <th class="p-3 border">Increment</th>
                        <th class="p-3 border">Revised By</th>
                        <th class="p-3 border">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                    @php
                        $monthStart = \Carbon\Carbon::create($year, $month)->startOfMonth()->toDateString();
                        $monthEnd = \Carbon\Carbon::create($year, $month)->endOfMonth()->toDateString();
                        $revisions = \App\Models\SalaryRevision::with(['employee.department', 'revisedBy'])
                            ->whereBetween('effective_date', [$monthStart, $monthEnd])
                            ->get();
                    @endphp
                    @forelse($revisions as $rev)
                        <tr>
                            <td class="p-3 border font-bold">{{ $rev->effective_date->format('Y-m-d') }}</td>
                            <td class="p-3 border">{{ $rev->employee->employee_code ?? 'N/A' }}</td>
                            <td class="p-3 border font-bold">{{ $rev->employee->name }}</td>
                            <td class="p-3 border">Rs. {{ number_format($rev->previous_gross_salary, 2) }}</td>
                            <td class="p-3 border text-emerald-600">Rs. {{ number_format($rev->new_gross_salary, 2) }}</td>
                            <td class="p-3 border font-extrabold text-emerald-600">Rs. {{ number_format($rev->new_gross_salary - $rev->previous_gross_salary, 2) }}</td>
                            <td class="p-3 border">{{ $rev->revisedBy->name ?? 'System' }}</td>
                            <td class="p-3 border text-slate-400 font-medium">{{ $rev->remarks ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-4 border text-center text-slate-400">No revisions logged for this month.</td>
                        </tr>
                    @endforelse
                </tbody>
            @elseif($type === 'attendance_summary')
                <thead>
                    <tr class="bg-slate-100 border-b border-slate-200 font-bold text-slate-700">
                        <th class="p-3 border">Employee Code</th>
                        <th class="p-3 border">Employee Name</th>
                        <th class="p-3 border">Department</th>
                        <th class="p-3 border text-center">Present</th>
                        <th class="p-3 border text-center">Late</th>
                        <th class="p-3 border text-center">Half Day</th>
                        <th class="p-3 border text-center">Absent</th>
                        <th class="p-3 border text-center">Weekly Offs</th>
                        <th class="p-3 border text-center">Paid Leaves</th>
                        <th class="p-3 border text-center">Unpaid Leaves</th>
                        <th class="p-3 border text-center font-extrabold text-indigo-600">Paid Days</th>
                        <th class="p-3 border text-center">Calendar Days</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                    @foreach($summary as $row)
                        <tr>
                            <td class="p-3 border font-bold">{{ $row->employee_code }}</td>
                            <td class="p-3 border font-bold">{{ $row->name }}</td>
                            <td class="p-3 border text-xs">{{ $row->department }}</td>
                            <td class="p-3 border text-center text-emerald-600">{{ $row->present }}</td>
                            <td class="p-3 border text-center text-amber-600">{{ $row->late }}</td>
                            <td class="p-3 border text-center text-purple-600">{{ $row->half_day }}</td>
                            <td class="p-3 border text-center text-rose-600">{{ $row->absent }}</td>
                            <td class="p-3 border text-center text-slate-500">{{ $row->weekly_offs }}</td>
                            <td class="p-3 border text-center text-indigo-500">{{ $row->paid_leaves }}</td>
                            <td class="p-3 border text-center text-rose-400">{{ $row->unpaid_leaves }}</td>
                            <td class="p-3 border text-center font-extrabold text-indigo-600">{{ $row->paid_days }}</td>
                            <td class="p-3 border text-center">{{ $row->days_in_month }}</td>
                        </tr>
                    @endforeach
                </tbody>
            @endif

        </table>
    </div>

</body>
</html>
