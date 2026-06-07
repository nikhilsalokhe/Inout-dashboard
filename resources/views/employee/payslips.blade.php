@extends('layouts.employee')

@section('title', 'Finalized Payslips History')

@section('content')
<div class="mb-8">
    <p class="text-slate-500 text-sm font-medium">Access and print your complete authorized monthly payslip registers.</p>
</div>

<!-- Payslips Table Card -->
<div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse whitespace-nowrap">
            <thead class="bg-slate-50/70 border-b border-slate-100 text-slate-400 font-bold text-[10px] uppercase tracking-widest">
                <tr>
                    <th class="px-6 py-5">Month/Year</th>
                    <th class="px-6 py-5">Gross Base Salary</th>
                    <th class="px-6 py-5">Overtime Amount</th>
                    <th class="px-6 py-5">Attendance Deductions</th>
                    <th class="px-6 py-5">Statutory & Tax Deductions</th>
                    <th class="px-6 py-5">Net Credited</th>
                    <th class="px-6 py-5">Disbursed Days</th>
                    <th class="px-6 py-5">Status</th>
                    <th class="px-6 py-5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($payslips as $pr)
                    <tr class="hover:bg-slate-50/30 transition-colors">
                        <td class="px-6 py-4.5 font-bold text-slate-800 text-xs">
                            {{ date('F Y', mktime(0, 0, 0, $pr->month, 10, $pr->year)) }}
                        </td>
                        <td class="px-6 py-4.5 text-xs text-slate-700 font-bold">
                            Rs. {{ number_format($pr->gross_salary, 2) }}
                        </td>
                        <td class="px-6 py-4.5 text-xs text-slate-500 font-bold">
                            Rs. {{ number_format($pr->overtime_amount, 2) }}
                            <span class="text-[9px] text-slate-400 block font-semibold">Hours: {{ $pr->overtime_hours }} hrs</span>
                        </td>
                        <td class="px-6 py-4.5 text-xs text-rose-600 font-bold">
                            -Rs. {{ number_format($pr->absent_deduction + $pr->half_day_deduction + $pr->late_penalty, 2) }}
                        </td>
                        <td class="px-6 py-4.5 text-xs text-rose-500 font-bold">
                            -Rs. {{ number_format($pr->pf + $pr->esic + $pr->professional_tax + $pr->tds + $pr->loan_deduction + $pr->advance_salary, 2) }}
                        </td>
                        <td class="px-6 py-4.5 text-xs text-emerald-600 font-extrabold">
                            Rs. {{ number_format($pr->net_salary, 2) }}
                        </td>
                        <td class="px-6 py-4.5 text-xs text-slate-500 font-bold">
                            {{ $pr->paid_days }} / {{ $pr->payable_days }} days
                        </td>
                        <td class="px-6 py-4.5">
                            @if($pr->status === 'Paid')
                                <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-100 rounded text-[9px] font-extrabold uppercase">Disbursed</span>
                            @else
                                <span class="px-2 py-0.5 bg-blue-50 text-blue-700 border border-blue-100 rounded text-[9px] font-extrabold uppercase">{{ $pr->status }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4.5 text-right">
                            <a href="{{ route('employee.payslip.show', $pr->id) }}" target="_blank" class="px-3 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded-xl text-xs font-bold transition-all inline-flex items-center gap-1.5 border border-indigo-100 shadow-sm">
                                <i class="bi bi-file-pdf"></i> View & Print Payslip
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center text-slate-400">
                            <div class="w-12 h-12 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-300 text-lg border border-slate-100 mx-auto mb-3">
                                <i class="bi bi-wallet2"></i>
                            </div>
                            <h6 class="font-bold text-slate-700 text-xs mb-0.5 font-sans">No finalized payslips found</h6>
                            <p class="text-[10px] text-slate-400 font-semibold">Your finalized monthly payslip logs will appear here once approved by HR.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-6">
    {{ $payslips->links() }}
</div>
@endsection
