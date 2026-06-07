@extends('layouts.admin')

@section('title', 'Payroll Reports & Analytics Suite')

@section('content')
<div class="mb-8 flex flex-col sm:flex-row justify-between sm:items-center gap-4">
    <div>
        <p class="text-slate-500 text-sm font-medium">Generate, inspect, and export comprehensive payroll audit and deduction sheets.</p>
    </div>
    <div class="flex items-center gap-3">
        <!-- Filter Form -->
        <form method="GET" action="{{ route('admin.payroll.reports') }}" class="flex flex-wrap items-center gap-2">
            <select name="month" class="px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:outline-none">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,10)) }}</option>
                @endfor
            </select>
            <select name="year" class="px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:outline-none">
                @for($y = date('Y') - 2; $y <= date('Y') + 1; $y++)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
            <select name="department_id" class="px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:outline-none">
                <option value="">All Departments</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ $departmentId == $dept->id ? 'selected' : '' }}>{{ $dept->department_name }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 text-xs font-bold rounded-xl border border-indigo-150 transition-all">Filter</button>
        </form>
    </div>
</div>

<!-- Key Performance Indicators (KPIs) -->
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
    <div class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Processed Employees</span>
        <span class="text-slate-800 font-extrabold text-lg">{{ $employeeCount }}</span>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm">
        <span class="text-[10px] font-bold text-indigo-500 uppercase tracking-wider block mb-1">Net Disbursed</span>
        <span class="text-indigo-600 font-extrabold text-lg">Rs. {{ number_format($totalNet, 2) }}</span>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Gross Base Spend</span>
        <span class="text-slate-800 font-extrabold text-lg">Rs. {{ number_format($totalGross, 2) }}</span>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm">
        <span class="text-[10px] font-bold text-rose-500 uppercase tracking-wider block mb-1">Total Deductions</span>
        <span class="text-rose-600 font-extrabold text-lg">Rs. {{ number_format($totalDeductions, 2) }}</span>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm">
        <span class="text-[10px] font-bold text-emerald-500 uppercase tracking-wider block mb-1">Overtime Expense</span>
        <span class="text-emerald-600 font-extrabold text-lg">Rs. {{ number_format($totalOt, 2) }}</span>
    </div>
</div>

<!-- Available Reports Workspace -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <!-- Report Options Cards -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6">
            <div class="flex items-center gap-2 mb-6">
                <div class="w-1 h-5 bg-indigo-500 rounded-full"></div>
                <h3 class="font-bold text-slate-800 text-base">Select Payroll Report Type</h3>
            </div>

            <!-- List of reports -->
            <div class="space-y-4">
                
                <!-- Report Item 1 -->
                <div class="flex flex-col sm:flex-row justify-between sm:items-center p-4 bg-slate-50 rounded-2xl border border-slate-200/40 gap-4">
                    <div>
                        <h4 class="font-bold text-slate-800 text-sm">Monthly Payroll Register</h4>
                        <p class="text-xs text-slate-400 mt-0.5 font-medium">Complete breakdown of all earnings, base salary, and deductions per employee.</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('admin.payroll.reports.export', ['monthly_payroll', 'csv', 'month' => $month, 'year' => $year, 'department_id' => $departmentId]) }}" class="px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-lg shadow-sm transition-all">CSV</a>
                        <a href="{{ route('admin.payroll.reports.export', ['monthly_payroll', 'excel', 'month' => $month, 'year' => $year, 'department_id' => $departmentId]) }}" class="px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-lg shadow-sm transition-all">Excel</a>
                        <a href="{{ route('admin.payroll.reports.export', ['monthly_payroll', 'pdf', 'month' => $month, 'year' => $year, 'department_id' => $departmentId]) }}" target="_blank" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 text-xs font-bold rounded-lg transition-all">Print</a>
                    </div>
                </div>

                <!-- Report Item 2 -->
                <div class="flex flex-col sm:flex-row justify-between sm:items-center p-4 bg-slate-50 rounded-2xl border border-slate-200/40 gap-4">
                    <div>
                        <h4 class="font-bold text-slate-800 text-sm">Department Salary Cost Summary</h4>
                        <p class="text-xs text-slate-400 mt-0.5 font-medium">Departmental salary distributions, headcounts, and combined cost aggregates.</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('admin.payroll.reports.export', ['department_payroll', 'csv', 'month' => $month, 'year' => $year, 'department_id' => $departmentId]) }}" class="px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-lg shadow-sm transition-all">CSV</a>
                        <a href="{{ route('admin.payroll.reports.export', ['department_payroll', 'excel', 'month' => $month, 'year' => $year, 'department_id' => $departmentId]) }}" class="px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-lg shadow-sm transition-all">Excel</a>
                        <a href="{{ route('admin.payroll.reports.export', ['department_payroll', 'pdf', 'month' => $month, 'year' => $year, 'department_id' => $departmentId]) }}" target="_blank" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 text-xs font-bold rounded-lg transition-all">Print</a>
                    </div>
                </div>

                <!-- Report Item 3 -->
                <div class="flex flex-col sm:flex-row justify-between sm:items-center p-4 bg-slate-50 rounded-2xl border border-slate-200/40 gap-4">
                    <div>
                        <h4 class="font-bold text-slate-800 text-sm">Tax & Statutory Deduction Sheet</h4>
                        <p class="text-xs text-slate-400 mt-0.5 font-medium">provident fund (PF), ESIC, Professional Tax, and Income Tax (TDS) audits.</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('admin.payroll.reports.export', ['deduction_report', 'csv', 'month' => $month, 'year' => $year, 'department_id' => $departmentId]) }}" class="px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-lg shadow-sm transition-all">CSV</a>
                        <a href="{{ route('admin.payroll.reports.export', ['deduction_report', 'excel', 'month' => $month, 'year' => $year, 'department_id' => $departmentId]) }}" class="px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-lg shadow-sm transition-all">Excel</a>
                        <a href="{{ route('admin.payroll.reports.export', ['deduction_report', 'pdf', 'month' => $month, 'year' => $year, 'department_id' => $departmentId]) }}" target="_blank" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 text-xs font-bold rounded-lg transition-all">Print</a>
                    </div>
                </div>

                <!-- Report Item 4 -->
                <div class="flex flex-col sm:flex-row justify-between sm:items-center p-4 bg-slate-50 rounded-2xl border border-slate-200/40 gap-4">
                    <div>
                        <h4 class="font-bold text-slate-800 text-sm">Attendance Impact & Penalties Report</h4>
                        <p class="text-xs text-slate-400 mt-0.5 font-medium">Financial penalties logged for late checkins, half-days, and unpaid absent logs.</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('admin.payroll.reports.export', ['attendance_impact', 'csv', 'month' => $month, 'year' => $year, 'department_id' => $departmentId]) }}" class="px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-lg shadow-sm transition-all">CSV</a>
                        <a href="{{ route('admin.payroll.reports.export', ['attendance_impact', 'excel', 'month' => $month, 'year' => $year, 'department_id' => $departmentId]) }}" class="px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-lg shadow-sm transition-all">Excel</a>
                        <a href="{{ route('admin.payroll.reports.export', ['attendance_impact', 'pdf', 'month' => $month, 'year' => $year, 'department_id' => $departmentId]) }}" target="_blank" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 text-xs font-bold rounded-lg transition-all">Print</a>
                    </div>
                </div>

                <!-- Report Item 5 -->
                <div class="flex flex-col sm:flex-row justify-between sm:items-center p-4 bg-slate-50 rounded-2xl border border-slate-200/40 gap-4">
                    <div>
                        <h4 class="font-bold text-slate-800 text-sm">Overtime Payout & Hours Summary</h4>
                        <p class="text-xs text-slate-400 mt-0.5 font-medium">Aggregated overtime hours worked and payouts credited per employee.</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('admin.payroll.reports.export', ['overtime_report', 'csv', 'month' => $month, 'year' => $year, 'department_id' => $departmentId]) }}" class="px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-lg shadow-sm transition-all">CSV</a>
                        <a href="{{ route('admin.payroll.reports.export', ['overtime_report', 'excel', 'month' => $month, 'year' => $year, 'department_id' => $departmentId]) }}" class="px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-lg shadow-sm transition-all">Excel</a>
                        <a href="{{ route('admin.payroll.reports.export', ['overtime_report', 'pdf', 'month' => $month, 'year' => $year, 'department_id' => $departmentId]) }}" target="_blank" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 text-xs font-bold rounded-lg transition-all">Print</a>
                    </div>
                </div>

                <!-- Report Item 6 -->
                <div class="flex flex-col sm:flex-row justify-between sm:items-center p-4 bg-slate-50 rounded-2xl border border-slate-200/40 gap-4">
                    <div>
                        <h4 class="font-bold text-slate-800 text-sm">Salary Appraisal & Revision Log</h4>
                        <p class="text-xs text-slate-400 mt-0.5 font-medium">Increment history logs, appraisal remarks, and new salary structure bindings.</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('admin.payroll.reports.export', ['salary_revision', 'csv', 'month' => $month, 'year' => $year, 'department_id' => $departmentId]) }}" class="px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-lg shadow-sm transition-all">CSV</a>
                        <a href="{{ route('admin.payroll.reports.export', ['salary_revision', 'excel', 'month' => $month, 'year' => $year, 'department_id' => $departmentId]) }}" class="px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-lg shadow-sm transition-all">Excel</a>
                        <a href="{{ route('admin.payroll.reports.export', ['salary_revision', 'pdf', 'month' => $month, 'year' => $year, 'department_id' => $departmentId]) }}" target="_blank" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 text-xs font-bold rounded-lg transition-all">Print</a>
                    </div>
                </div>

                <!-- Report Item 7 -->
                <div class="flex flex-col sm:flex-row justify-between sm:items-center p-4 bg-slate-50 rounded-2xl border border-slate-200/40 gap-4">
                    <div>
                        <h4 class="font-bold text-slate-800 text-sm">Attendance Summary Report</h4>
                        <p class="text-xs text-slate-400 mt-0.5 font-medium">Employee attendance metrics, present/absent counts, paid days, and leaves calculations.</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('admin.payroll.reports.export', ['attendance_summary', 'csv', 'month' => $month, 'year' => $year, 'department_id' => $departmentId]) }}" class="px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-lg shadow-sm transition-all">CSV</a>
                        <a href="{{ route('admin.payroll.reports.export', ['attendance_summary', 'excel', 'month' => $month, 'year' => $year, 'department_id' => $departmentId]) }}" class="px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-lg shadow-sm transition-all">Excel</a>
                        <a href="{{ route('admin.payroll.reports.export', ['attendance_summary', 'pdf', 'month' => $month, 'year' => $year, 'department_id' => $departmentId]) }}" target="_blank" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 text-xs font-bold rounded-lg transition-all">Print</a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Right Column: Department Cost Breakdowns -->
    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6 self-start">
        <div class="flex items-center gap-2 mb-6">
            <div class="w-1 h-5 bg-indigo-500 rounded-full"></div>
            <h3 class="font-bold text-slate-800 text-base">Department Cost Breakdown</h3>
        </div>

        <div class="space-y-4">
            @forelse($deptSummary as $dept => $data)
                <div class="border-b border-slate-100 pb-3 last:border-0 last:pb-0">
                    <div class="flex justify-between items-center mb-1">
                        <span class="font-bold text-xs text-slate-800">{{ $dept }}</span>
                        <span class="text-[10px] text-slate-400 font-extrabold bg-slate-50 border border-slate-100 px-2 py-0.5 rounded">{{ $data['count'] }} Employees</span>
                    </div>
                    <div class="flex justify-between items-center text-xs text-slate-500 font-medium">
                        <span>Base Gross: Rs. {{ number_format($data['gross'] / 1000, 1) }}k</span>
                        <span class="text-indigo-600 font-extrabold">Net: Rs. {{ number_format($data['net'] / 1000, 1) }}k</span>
                    </div>
                </div>
            @empty
                <p class="text-xs text-slate-400 text-center py-6 font-semibold">No cost data available for this month.</p>
            @endforelse
        </div>
    </div>

</div>
@endsection
