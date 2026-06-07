@extends('layouts.admin')

@section('title', 'Monthly Payroll Process')

@section('content')
<div class="mb-8 flex flex-col sm:flex-row justify-between sm:items-center gap-4">
    <div>
        <p class="text-slate-500 text-sm font-medium">Generate monthly payroll, adjust earnings/deductions, and process payouts.</p>
    </div>
    <div class="flex items-center gap-3">
        <!-- Month Filter Form -->
        <form method="GET" action="{{ route('admin.payroll.index') }}" class="flex items-center gap-2">
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
            <button type="submit" class="px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 text-xs font-bold rounded-xl border border-indigo-150 transition-all">Filter</button>
        </form>

        <form action="{{ route('admin.payroll.generate') }}" method="POST" class="inline">
            @csrf
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="bulk" value="1">
            <button type="submit" class="px-5 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-extrabold rounded-2xl shadow-md shadow-indigo-500/20 hover:shadow-indigo-500/30 transition-all duration-300 flex items-center justify-center gap-2 hover:-translate-y-0.5 active:translate-y-0">
                <i class="bi bi-cpu text-sm"></i>
                <span class="text-sm">Bulk Process Payroll ({{ $missingCount }} Pending)</span>
            </button>
        </form>
    </div>
</div>

<!-- Bulk Transition Actions -->
@if($payrolls->count() > 0)
<div class="mb-6 p-4 bg-slate-50 border border-slate-200/60 rounded-2xl flex flex-wrap items-center justify-between gap-4">
    <div class="flex items-center gap-3">
        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Bulk State Management:</span>
        <span class="px-2 py-0.5 bg-slate-200 text-slate-700 text-[10px] font-extrabold rounded">Total Processed: {{ $payrolls->count() }}</span>
        <span class="px-2 py-0.5 bg-yellow-100 text-yellow-800 text-[10px] font-extrabold rounded">Drafts: {{ $payrolls->where('status', 'Draft')->count() }}</span>
        <span class="px-2 py-0.5 bg-blue-100 text-blue-800 text-[10px] font-extrabold rounded">Approved: {{ $payrolls->where('status', 'Approved')->count() }}</span>
    </div>
    <div class="flex items-center gap-2">
        @if($payrolls->where('status', 'Draft')->count() > 0)
            <form action="{{ route('admin.payroll.bulk-transition') }}" method="POST" onsubmit="return confirm('Approve all generated payrolls for this month?');">
                @csrf
                <input type="hidden" name="month" value="{{ $month }}">
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="action" value="approve">
                <button type="submit" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-extrabold rounded-xl shadow-sm transition-all">Lock & Approve Drafts</button>
            </form>
        @endif
        @if($payrolls->where('status', 'Approved')->count() > 0)
            <form action="{{ route('admin.payroll.bulk-transition') }}" method="POST" onsubmit="return confirm('Mark all approved payrolls as paid?');">
                @csrf
                <input type="hidden" name="month" value="{{ $month }}">
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="action" value="pay">
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-extrabold rounded-xl shadow-sm transition-all">Mark Approved as PAID</button>
            </form>
        @endif
    </div>
</div>
@endif

<!-- Payroll Table List -->
<div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse whitespace-nowrap">
            <thead class="bg-slate-50/70 border-b border-slate-100 text-slate-400 font-bold text-[10px] uppercase tracking-widest">
                <tr>
                    <th class="px-6 py-5">Employee</th>
                    <th class="px-6 py-5">Gross Base</th>
                    <th class="px-6 py-5">Overtime (Hrs)</th>
                    <th class="px-6 py-5">Earnings (OT+Bonus+Inc)</th>
                    <th class="px-6 py-5">Attendance Deduct</th>
                    <th class="px-6 py-5">Tax & statutory Deduct</th>
                    <th class="px-6 py-5">Net Payable</th>
                    <th class="px-6 py-5">Attendance</th>
                    <th class="px-6 py-5">Status</th>
                    <th class="px-6 py-5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($payrolls as $pr)
                    <tr class="hover:bg-slate-50/30 transition-colors">
                        <td class="px-6 py-4.5">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center font-bold text-xs text-indigo-600">
                                    {{ strtoupper(substr($pr->employee->name, 0, 1)) }}
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-800 text-xs leading-tight mb-0.5">{{ $pr->employee->name }}</h4>
                                    <p class="text-[9px] text-slate-400 font-medium">Code: {{ $pr->employee->employee_code ?? '-' }} • Dept: {{ $pr->employee->department->department_name ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4.5 text-xs text-slate-700 font-bold">
                            Rs. {{ number_format($pr->gross_salary, 2) }}
                        </td>
                        <td class="px-6 py-4.5 text-xs text-slate-500 font-bold">
                            {{ $pr->overtime_hours }} hrs <span class="text-[10px] text-indigo-500 block font-semibold">+Rs. {{ number_format($pr->overtime_amount, 2) }}</span>
                        </td>
                        <td class="px-6 py-4.5 text-xs text-slate-700 font-bold">
                            Rs. {{ number_format($pr->gross_salary + $pr->overtime_amount + $pr->bonus + $pr->incentives, 2) }}
                            <span class="text-[9px] text-slate-400 block font-semibold">Bonus/Inc: Rs. {{ number_format($pr->bonus + $pr->incentives, 2) }}</span>
                        </td>
                        <td class="px-6 py-4.5 text-xs text-rose-600 font-bold">
                            -Rs. {{ number_format($pr->absent_deduction + $pr->half_day_deduction + $pr->late_penalty, 2) }}
                            <span class="text-[9px] text-slate-400 block font-semibold">Late marks: {{ $pr->late_penalty > 0 ? 'Yes' : 'No' }}</span>
                        </td>
                        <td class="px-6 py-4.5 text-xs text-rose-500 font-bold">
                            -Rs. {{ number_format($pr->pf + $pr->esic + $pr->professional_tax + $pr->tds + $pr->loan_deduction + $pr->advance_salary, 2) }}
                            <span class="text-[9px] text-slate-400 block font-semibold">PF/ESIC/PT: Rs. {{ number_format($pr->pf + $pr->esic + $pr->professional_tax, 2) }}</span>
                        </td>
                        <td class="px-6 py-4.5 text-xs text-emerald-600 font-extrabold">
                            Rs. {{ number_format($pr->net_salary, 2) }}
                        </td>
                        <td class="px-6 py-4.5 text-xs text-slate-500 font-bold">
                            {{ $pr->paid_days }} / {{ $pr->payable_days }} days
                        </td>
                        <td class="px-6 py-4.5">
                            @if($pr->status === 'Draft')
                                <span class="px-2 py-0.5 bg-yellow-50 text-yellow-700 border border-yellow-100 rounded text-[9px] font-extrabold uppercase">Draft</span>
                            @elseif($pr->status === 'Approved')
                                <span class="px-2 py-0.5 bg-blue-50 text-blue-700 border border-blue-100 rounded text-[9px] font-extrabold uppercase">Approved</span>
                            @elseif($pr->status === 'Paid')
                                <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-100 rounded text-[9px] font-extrabold uppercase">Paid</span>
                            @else
                                <span class="px-2 py-0.5 bg-slate-50 text-slate-400 border border-slate-100 rounded text-[9px] font-extrabold uppercase">{{ $pr->status }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4.5 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                @if($pr->status === 'Draft')
                                    <button onclick="openAdjustModal({{ json_encode($pr) }})" class="px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded-lg text-xs font-bold transition-all" title="Adjust components">
                                        <i class="bi bi-sliders"></i> Adjust
                                    </button>
                                    <form action="{{ route('admin.payroll.transition', $pr->id) }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 rounded-lg text-xs font-bold transition-all">
                                            Approve
                                        </button>
                                    </form>
                                @endif
                                @if($pr->status === 'Approved')
                                    <form action="{{ route('admin.payroll.transition', $pr->id) }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="action" value="pay">
                                        <button type="submit" class="px-2.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition-all">
                                            Pay
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ route('admin.payroll.show', $pr->id) }}" target="_blank" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs font-bold transition-all">
                                    <i class="bi bi-file-pdf"></i> Payslip
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-6 py-12 text-center text-slate-400">
                            <div class="w-12 h-12 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-300 text-lg border border-slate-100 mx-auto mb-3">
                                <i class="bi bi-cpu"></i>
                            </div>
                            <h6 class="font-bold text-slate-700 text-xs mb-0.5 font-sans">No payroll processed for this month</h6>
                            <p class="text-[10px] text-slate-400 font-semibold mb-3">Click on the "Bulk Process Payroll" button above to run calculations.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Adjust Earnings/Deductions Modal -->
<div id="adjustModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
        </div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-middle bg-white rounded-3xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="adjustForm" method="POST">
                @csrf
                <div class="px-8 py-6 border-b border-slate-100">
                    <h3 class="text-base font-extrabold text-slate-900">Adjust Earnings & Deductions</h3>
                </div>
                <div class="px-8 py-6 space-y-4">
                    <div>
                        <span class="text-slate-400 font-bold text-[10px] uppercase block mb-1">Employee</span>
                        <span id="adjust_employee_name" class="font-extrabold text-slate-800 text-sm">N/A</span>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-700 text-xs font-bold mb-2 uppercase tracking-wide">Bonus (Rs.)</label>
                            <input type="number" name="bonus" id="adjust_bonus" step="1" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:border-indigo-500 focus:outline-none font-semibold text-slate-800">
                        </div>
                        <div>
                            <label class="block text-slate-700 text-xs font-bold mb-2 uppercase tracking-wide">Incentives (Rs.)</label>
                            <input type="number" name="incentives" id="adjust_incentives" step="1" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:border-indigo-500 focus:outline-none font-semibold text-slate-800">
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-slate-700 text-xs font-bold mb-2 uppercase tracking-wide">TDS (Rs.)</label>
                            <input type="number" name="tds" id="adjust_tds" step="1" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:border-indigo-500 focus:outline-none font-semibold text-slate-800">
                        </div>
                        <div>
                            <label class="block text-slate-700 text-xs font-bold mb-2 uppercase tracking-wide">Loan Deduct (Rs.)</label>
                            <input type="number" name="loan_deduction" id="adjust_loan" step="1" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:border-indigo-500 focus:outline-none font-semibold text-slate-800">
                        </div>
                        <div>
                            <label class="block text-slate-700 text-xs font-bold mb-2 uppercase tracking-wide">Advance Salary (Rs.)</label>
                            <input type="number" name="advance_salary" id="adjust_advance" step="1" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:border-indigo-500 focus:outline-none font-semibold text-slate-800">
                        </div>
                    </div>
                </div>
                <div class="px-8 py-6 border-t border-slate-100 bg-slate-50 flex items-center justify-end gap-3">
                    <button type="button" onclick="toggleModal('adjustModal')" class="px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-500 text-xs font-bold transition-all">Cancel</button>
                    <button type="submit" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-xs font-bold shadow-md shadow-indigo-500/20 hover:shadow-indigo-500/30 transition-all">Save Adjustments</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.toggle('hidden');
        }
    }

    function openAdjustModal(pr) {
        document.getElementById('adjustForm').action = '/admin/payroll/' + pr.id + '/update';
        document.getElementById('adjust_employee_name').innerText = pr.employee.name + ' (Code: ' + (pr.employee.employee_code || '-') + ')';
        document.getElementById('adjust_bonus').value = Math.round(pr.bonus);
        document.getElementById('adjust_incentives').value = Math.round(pr.incentives);
        document.getElementById('adjust_tds').value = Math.round(pr.tds);
        document.getElementById('adjust_loan').value = Math.round(pr.loan_deduction);
        document.getElementById('adjust_advance').value = Math.round(pr.advance_salary);
        
        toggleModal('adjustModal');
    }
</script>
@endsection
