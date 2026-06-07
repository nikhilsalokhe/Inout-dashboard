@extends('layouts.admin')

@section('title', 'Initiate Exit Workflow')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Main Card -->
    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden font-sans">
        <!-- Header -->
        <div class="p-8 border-b border-slate-100 bg-slate-50/40">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-gradient-to-tr from-slate-800 to-slate-950 text-white rounded-2xl flex items-center justify-center font-bold text-lg shadow-md border-2 border-white">
                    {{ strtoupper(substr($employee->name, 0, 1)) }}
                </div>
                <div>
                    <h2 class="text-lg font-extrabold text-slate-800 tracking-tight">Initiate Exit Workflow</h2>
                    <p class="text-slate-400 text-xs font-semibold mt-0.5">Setup exit reasons, final working dates, notice durations, and settle calculations for {{ $employee->name }}.</p>
                </div>
            </div>
        </div>

        <form action="{{ route('admin.terminations.store') }}" method="POST" id="exit-form" class="p-8 space-y-6">
            @csrf
            <input type="hidden" name="employee_id" value="{{ $employee->id }}">

            <!-- Quick Employee Profile info & Warnings -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50 p-6 rounded-2xl border border-slate-100 mb-6">
                <div>
                    <span class="text-[10px] font-black uppercase text-slate-400 tracking-wider block mb-1">Corporate Details</span>
                    <h4 class="font-bold text-sm text-slate-800">{{ $employee->name }}</h4>
                    <p class="text-xs text-slate-400 font-medium">Code: {{ $employee->employee_code }} • Type: {{ ucfirst($employee->employee_type) }}</p>
                    <p class="text-xs text-slate-400 font-medium">Department: {{ $employee->department->department_name ?? 'N/A' }}</p>
                    <p class="text-xs text-slate-400 font-medium">Designation: {{ $employee->position->position_name ?? 'N/A' }}</p>
                </div>
                <div class="border-t md:border-t-0 md:border-l border-slate-200/60 pt-4 md:pt-0 md:pl-6 space-y-2">
                    <span class="text-[10px] font-black uppercase text-slate-400 tracking-wider block mb-1">Accrued Leaves & Daily Rate</span>
                    <div class="flex items-center justify-between text-xs font-semibold">
                        <span class="text-slate-500">Accrued Unused Leaves:</span>
                        <span class="text-slate-800 font-extrabold">{{ $remainingLeaves }} Days</span>
                    </div>
                    <div class="flex items-center justify-between text-xs font-semibold">
                        <span class="text-slate-500">Active Gross Salary:</span>
                        <span class="text-slate-800 font-extrabold">Rs. {{ number_format($grossSalary, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs font-semibold pt-1.5 border-t border-dashed border-slate-200">
                        <span class="text-slate-500">Recommended Encashment:</span>
                        <span class="text-indigo-600 font-black">Rs. {{ number_format($recommendedEncashment, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Form parameters -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Termination Type -->
                <div>
                    <label for="termination_type" class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Exit Type</label>
                    <select name="termination_type" id="termination_type" required
                        class="w-full px-4 py-3.5 rounded-xl border border-slate-200 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all duration-300 text-sm font-semibold bg-white">
                        <option value="resigned" {{ old('termination_type') == 'resigned' ? 'selected' : '' }}>Resignation</option>
                        <option value="terminated" {{ old('termination_type') == 'terminated' ? 'selected' : '' }}>Involuntary Termination</option>
                        <option value="absconded" {{ old('termination_type') == 'absconded' ? 'selected' : '' }}>Absconded</option>
                        <option value="retired" {{ old('termination_type') == 'retired' ? 'selected' : '' }}>Retirement</option>
                        <option value="contract_completed" {{ old('termination_type') == 'contract_completed' ? 'selected' : '' }}>Contract Completed</option>
                    </select>
                    @error('termination_type') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                </div>

                <!-- Last Working Date -->
                <div>
                    <label for="last_working_date" class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Last Working Date</label>
                    <input type="date" name="last_working_date" id="last_working_date" value="{{ old('last_working_date', date('Y-m-d')) }}" required
                        class="w-full px-4 py-3.5 rounded-xl border border-slate-200 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all duration-300 text-sm font-semibold">
                    @error('last_working_date') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Notice Period Days -->
                <div>
                    <label for="notice_period_days" class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Notice Period Served (Days)</label>
                    <input type="number" name="notice_period_days" id="notice_period_days" value="{{ old('notice_period_days', 0) }}" min="0" required
                        class="w-full px-4 py-3.5 rounded-xl border border-slate-200 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all duration-300 text-sm font-semibold">
                    @error('notice_period_days') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                </div>

                <!-- Pending Salary for final month -->
                <div>
                    <label for="pending_salary" class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Estimated Final Month Salary (Rs.)</label>
                    <input type="number" name="pending_salary" id="pending_salary" value="{{ old('pending_salary', 0.00) }}" min="0" step="0.01" required
                        class="w-full px-4 py-3.5 rounded-xl border border-slate-200 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all duration-300 text-sm font-semibold">
                    @error('pending_salary') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Leave Encashment payout -->
                <div>
                    <label for="leave_encashment" class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Leave Encashment Settlement (Rs.)</label>
                    <div class="relative">
                        <input type="number" name="leave_encashment" id="leave_encashment" value="{{ old('leave_encashment', $recommendedEncashment) }}" min="0" step="0.01" required
                            class="w-full px-4 py-3.5 rounded-xl border border-slate-200 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all duration-300 text-sm font-semibold pr-20">
                        <button type="button" onclick="applyRecommendation()" class="absolute right-3 top-1/2 -translate-y-1/2 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 text-indigo-600 font-extrabold text-[9px] uppercase tracking-wider px-2.5 py-1.5 rounded-lg transition">
                            Apply suggested
                        </button>
                    </div>
                    @error('leave_encashment') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Termination Reason -->
            <div>
                <label for="termination_reason" class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Exit & Termination Reason</label>
                <textarea name="termination_reason" id="termination_reason" rows="3" required
                    class="w-full px-4 py-3.5 rounded-xl border border-slate-200 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all duration-300 placeholder:text-slate-300 text-sm font-semibold"
                    placeholder="Provide details behind resignation, contract duration completion, or performance/disciplinary grounds..."></textarea>
                @error('termination_reason') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
            </div>

            <!-- Remarks -->
            <div>
                <label for="remarks" class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Settlement Remarks</label>
                <textarea name="remarks" id="remarks" rows="2"
                    class="w-full px-4 py-3.5 rounded-xl border border-slate-200 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all duration-300 placeholder:text-slate-300 text-sm font-semibold"
                    placeholder="Notes regarding final month deductions, payroll hold states, or unreturned asset values..."></textarea>
                @error('remarks') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
            </div>

            <!-- Action buttons -->
            <div class="pt-6 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('admin.employees.index') }}" class="px-5 py-3 rounded-xl text-slate-500 hover:text-slate-700 hover:bg-slate-100/50 text-xs font-bold transition-all duration-200">
                    Cancel
                </a>
                <button type="button" onclick="confirmExitInitiation()" class="px-6 py-3 bg-gradient-to-r from-rose-500 to-rose-600 hover:from-rose-600 hover:to-rose-700 text-white font-extrabold text-xs rounded-xl shadow-md transition-all duration-300 hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98]">
                    Initiate Exit Process
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Confirmation Overlay Modal -->
<div id="confirm-modal" class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-3xl border border-slate-100 p-8 max-w-md w-full shadow-2xl animate-fade-in mx-4">
        <div class="w-12 h-12 bg-rose-50 text-rose-500 border border-rose-100 rounded-2xl flex items-center justify-center text-xl mb-4">
            <i class="bi bi-person-x-fill"></i>
        </div>
        <h3 class="font-extrabold text-slate-900 text-base leading-snug mb-2">Are you absolutely sure you want to exit this employee?</h3>
        <p class="text-slate-400 text-xs font-semibold leading-relaxed mb-6">
            Initiating this exit will place the employee on notice period or immediately deactivate their profile. Active contracts will be terminated, geofence face-recognition bindings cleared, and they will be blocked from logging into the mobile application.
        </p>
        <div class="flex items-center justify-end gap-3">
            <button type="button" onclick="closeConfirmModal()" class="px-4 py-2 text-slate-500 hover:bg-slate-100/60 font-extrabold text-xs uppercase tracking-wider rounded-xl transition duration-150 border border-transparent">
                Go Back
            </button>
            <button type="button" onclick="submitExitForm()" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs uppercase tracking-wider rounded-xl transition duration-150 shadow-md">
                Confirm & Initiate
            </button>
        </div>
    </div>
</div>

<script>
    function applyRecommendation() {
        document.getElementById('leave_encashment').value = "{{ $recommendedEncashment }}";
    }

    function confirmExitInitiation() {
        const type = document.getElementById('termination_type').value;
        const reason = document.getElementById('termination_reason').value.trim();
        const lastWorking = document.getElementById('last_working_date').value;

        if (!type || !reason || !lastWorking) {
            alert('Please fill out all required fields: Exit Type, Reason, and Last Working Date.');
            return;
        }

        document.getElementById('confirm-modal').classList.remove('hidden');
    }

    function closeConfirmModal() {
        document.getElementById('confirm-modal').classList.add('hidden');
    }

    function submitExitForm() {
        document.getElementById('exit-form').submit();
    }
</script>
@endsection
