<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - {{ $payroll->employee->name }} - {{ $monthName }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        @media print {
            body {
                background-color: #ffffff;
                color: #000000;
            }
            .no-print {
                display: none;
            }
            .print-border {
                border: 1px solid #cbd5e1 !important;
            }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 p-4 sm:p-8">

    <div class="max-w-3xl mx-auto bg-white p-8 rounded-3xl border border-slate-200/80 shadow-sm print-border">
        
        <!-- Header / Actions -->
        <div class="flex justify-between items-center mb-8 no-print">
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Payslip Preview</span>
            </div>
            <div class="flex gap-2">
                <button onclick="window.print()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-extrabold rounded-xl shadow-sm transition-all flex items-center gap-1.5">
                    <i class="bi bi-printer"></i> Print / Save PDF
                </button>
                <button onclick="window.close()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-extrabold rounded-xl transition-all">
                    Close Window
                </button>
            </div>
        </div>

        <!-- Payslip Document Header -->
        <div class="flex justify-between items-start border-b border-slate-100 pb-6 mb-6">
            <div>
                <h1 class="text-xl font-extrabold text-slate-900 tracking-tight leading-none mb-1">InOut ENTERPRISES</h1>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Workspace HRMS Node</p>
                <p class="text-xs text-slate-500 mt-2 max-w-xs leading-relaxed font-semibold">102 Corporate Towers, Geofence District, Tech Park Road, Bengaluru, India</p>
            </div>
            <div class="text-right">
                <h2 class="text-base font-extrabold text-indigo-600 uppercase tracking-wider mb-1">Salary Slip</h2>
                <p class="text-xs text-slate-700 font-extrabold">{{ $monthName }}</p>
                <span class="inline-block mt-2 px-2.5 py-0.5 bg-emerald-50 border border-emerald-100 text-emerald-700 text-[9px] font-extrabold rounded-md uppercase tracking-wider">
                    Status: {{ $payroll->status }}
                </span>
            </div>
        </div>

        <!-- Employee Metadata Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-y-4 gap-x-6 bg-slate-50/50 border border-slate-100 p-6 rounded-2xl mb-6 text-xs">
            <div>
                <span class="text-slate-400 font-bold uppercase tracking-wider block mb-0.5">Employee Name</span>
                <span class="font-extrabold text-slate-800">{{ $payroll->employee->name }}</span>
            </div>
            <div>
                <span class="text-slate-400 font-bold uppercase tracking-wider block mb-0.5">Employee Code</span>
                <span class="font-bold text-slate-800">{{ $payroll->employee->employee_code ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="text-slate-400 font-bold uppercase tracking-wider block mb-0.5">Department</span>
                <span class="font-bold text-slate-800">{{ $payroll->employee->department->department_name ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="text-slate-400 font-bold uppercase tracking-wider block mb-0.5">Designation</span>
                <span class="font-bold text-slate-800">{{ $payroll->employee->position->position_name ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="text-slate-400 font-bold uppercase tracking-wider block mb-0.5">Location Node</span>
                <span class="font-bold text-slate-800">{{ $payroll->employee->location->location_name ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="text-slate-400 font-bold uppercase tracking-wider block mb-0.5">Pay Period</span>
                <span class="font-bold text-slate-800">{{ date('m / Y', mktime(0,0,0,$payroll->month,10,$payroll->year)) }}</span>
            </div>
        </div>

        <!-- Attendance Summary -->
        <div class="mb-6">
            <h3 class="text-xs font-extrabold text-slate-500 uppercase tracking-widest mb-3">Attendance & Duty Summary</h3>
            <div class="grid grid-cols-4 gap-4 bg-slate-50/20 border border-slate-100 rounded-xl p-4 text-center text-xs">
                <div>
                    <span class="text-slate-400 font-bold block mb-1">Calendar Days</span>
                    <span class="font-extrabold text-slate-800 text-sm">{{ $payroll->payable_days }}</span>
                </div>
                <div>
                    <span class="text-slate-400 font-bold block mb-1">Paid Days</span>
                    <span class="font-extrabold text-emerald-600 text-sm">{{ $payroll->paid_days }}</span>
                </div>
                <div>
                    <span class="text-slate-400 font-bold block mb-1">Lop / Absent Days</span>
                    <span class="font-extrabold text-rose-600 text-sm">{{ $payroll->payable_days - $prDays = $payroll->paid_days }}</span>
                </div>
                <div>
                    <span class="text-slate-400 font-bold block mb-1">Overtime Worked</span>
                    <span class="font-extrabold text-indigo-600 text-sm">{{ $payroll->overtime_hours }} hrs</span>
                </div>
            </div>
        </div>

        <!-- Salary Earnings vs Deductions Table -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-px bg-slate-200 border border-slate-200 rounded-2xl overflow-hidden mb-6 text-xs">
            
            <!-- Earnings Section -->
            <div class="bg-white p-6">
                <h4 class="font-extrabold text-indigo-600 uppercase tracking-wider border-b border-slate-100 pb-2.5 mb-4">Earnings</h4>
                <div class="space-y-3 font-semibold text-slate-700">
                    <div class="flex justify-between">
                        <span>Basic Salary</span>
                        <span>Rs. {{ number_format($payroll->basic_salary, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>HRA Portion</span>
                        <span>Rs. {{ number_format($payroll->hra, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>DA Allowance</span>
                        <span>Rs. {{ number_format($payroll->da, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Travel Allowance</span>
                        <span>Rs. {{ number_format($payroll->travel_allowance, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Special Allowance</span>
                        <span>Rs. {{ number_format($payroll->special_allowance, 2) }}</span>
                    </div>
                    @if($payroll->overtime_amount > 0)
                        <div class="flex justify-between text-indigo-600 font-bold">
                            <span>Overtime Pay ({{ $payroll->overtime_hours }}h)</span>
                            <span>+Rs. {{ number_format($payroll->overtime_amount, 2) }}</span>
                        </div>
                    @endif
                    @if($payroll->bonus > 0)
                        <div class="flex justify-between text-indigo-600 font-bold">
                            <span>Bonus payout</span>
                            <span>+Rs. {{ number_format($payroll->bonus, 2) }}</span>
                        </div>
                    @endif
                    @if($payroll->incentives > 0)
                        <div class="flex justify-between text-indigo-600 font-bold">
                            <span>Incentives</span>
                            <span>+Rs. {{ number_format($payroll->incentives, 2) }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Deductions Section -->
            <div class="bg-white p-6">
                <h4 class="font-extrabold text-rose-600 uppercase tracking-wider border-b border-slate-100 pb-2.5 mb-4">Deductions</h4>
                <div class="space-y-3 font-semibold text-slate-700">
                    @if($payroll->absent_deduction > 0)
                        <div class="flex justify-between text-rose-600">
                            <span>Absent days Lop</span>
                            <span>-Rs. {{ number_format($payroll->absent_deduction, 2) }}</span>
                        </div>
                    @endif
                    @if($payroll->half_day_deduction > 0)
                        <div class="flex justify-between text-rose-600">
                            <span>Half-Day deduction</span>
                            <span>-Rs. {{ number_format($payroll->half_day_deduction, 2) }}</span>
                        </div>
                    @endif
                    @if($payroll->late_penalty > 0)
                        <div class="flex justify-between text-rose-600">
                            <span>Late Mark Penalties</span>
                            <span>-Rs. {{ number_format($payroll->late_penalty, 2) }}</span>
                        </div>
                    @endif
                    @if($payroll->pf > 0)
                        <div class="flex justify-between">
                            <span>Provident Fund (PF)</span>
                            <span>-Rs. {{ number_format($payroll->pf, 2) }}</span>
                        </div>
                    @endif
                    @if($payroll->esic > 0)
                        <div class="flex justify-between">
                            <span>ESIC contribution</span>
                            <span>-Rs. {{ number_format($payroll->esic, 2) }}</span>
                        </div>
                    @endif
                    @if($payroll->professional_tax > 0)
                        <div class="flex justify-between">
                            <span>Professional Tax (PT)</span>
                            <span>-Rs. {{ number_format($payroll->professional_tax, 2) }}</span>
                        </div>
                    @endif
                    @if($payroll->tds > 0)
                        <div class="flex justify-between text-rose-600">
                            <span>Income Tax (TDS)</span>
                            <span>-Rs. {{ number_format($payroll->tds, 2) }}</span>
                        </div>
                    @endif
                    @if($payroll->loan_deduction > 0)
                        <div class="flex justify-between text-rose-600">
                            <span>Loan Repayment</span>
                            <span>-Rs. {{ number_format($payroll->loan_deduction, 2) }}</span>
                        </div>
                    @endif
                    @if($payroll->advance_salary > 0)
                        <div class="flex justify-between text-rose-600">
                            <span>Advance Salary Deduct</span>
                            <span>-Rs. {{ number_format($payroll->advance_salary, 2) }}</span>
                        </div>
                    @endif
                    
                    {{-- Fill space if needed --}}
                    @if($payroll->pf == 0 && $payroll->esic == 0 && $payroll->tds == 0 && $payroll->absent_deduction == 0)
                        <div class="flex justify-between text-slate-400 font-medium italic">
                            <span>No statutory deductions applied</span>
                            <span>Rs. 0.00</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Net Salary Summary Card -->
        <div class="bg-indigo-50 border border-indigo-100 p-6 rounded-2xl mb-8 flex justify-between items-center flex-wrap gap-4 text-xs">
            <div>
                <span class="text-slate-400 font-bold block mb-1 uppercase tracking-wide">Total Earnings</span>
                <span class="font-extrabold text-slate-800 text-sm">Rs. {{ number_format($payroll->total_earnings, 2) }}</span>
            </div>
            <div>
                <span class="text-slate-400 font-bold block mb-1 uppercase tracking-wide">Total Deductions</span>
                <span class="font-extrabold text-slate-800 text-sm">Rs. {{ number_format($payroll->total_deductions, 2) }}</span>
            </div>
            <div class="text-right">
                <span class="text-indigo-600 font-extrabold block mb-1 uppercase tracking-wider">Net take-home Salary</span>
                <span class="font-extrabold text-indigo-700 text-base">Rs. {{ number_format($payroll->net_salary, 2) }}</span>
            </div>
        </div>

        <!-- Signatures & Authority -->
        <div class="flex justify-between items-end border-t border-slate-100 pt-10 text-xs">
            <div class="text-center w-1/3">
                <div class="border-b border-slate-200 h-10 w-full mb-2"></div>
                <span class="text-slate-400 font-bold uppercase tracking-wider block">Employee Signature</span>
            </div>
            <div class="text-center w-1/3">
                <div class="border-b border-slate-200 h-10 w-full mb-2"></div>
                <span class="text-slate-400 font-bold uppercase tracking-wider block">Authorized Signatory</span>
            </div>
        </div>

    </div>

</body>
</html>
