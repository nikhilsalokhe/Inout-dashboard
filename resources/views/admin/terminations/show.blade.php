@extends('layouts.admin')

@section('title', 'Exit Summary & Checklist')

@section('content')
<div class="mb-8 flex flex-col sm:flex-row justify-between sm:items-center gap-4">
    <div>
        <p class="text-slate-500 text-sm font-medium">Update offboarding checklists, final settlement states, and print exit summaries.</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('admin.terminations.print', $termination->id) }}" target="_blank" class="px-5 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-extrabold rounded-2xl shadow-md shadow-indigo-500/20 hover:shadow-indigo-500/30 transition-all duration-300 flex items-center justify-center gap-2 hover:-translate-y-0.5 active:translate-y-0">
            <i class="bi bi-printer-fill text-sm"></i>
            <span class="text-sm">Print exit summary</span>
        </a>
        <a href="{{ route('admin.terminations.index') }}" class="px-5 py-3 bg-white border border-slate-200 text-slate-500 hover:text-slate-700 font-extrabold rounded-2xl transition duration-200 hover:-translate-y-0.5 active:translate-y-0">
            Back to Directory
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Left column: Checklist & Update Form -->
    <div class="lg:col-span-2 space-y-8">
        <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden font-sans">
            <div class="p-8 border-b border-slate-100 bg-slate-50/40">
                <h3 class="font-extrabold text-slate-800 text-sm uppercase tracking-wider flex items-center gap-2">
                    <i class="bi bi-card-checklist text-indigo-500"></i> Exit Management Checklists
                </h3>
            </div>

            <form action="{{ route('admin.terminations.update', $termination->id) }}" method="POST" class="p-8 space-y-6">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Exit Status -->
                    <div>
                        <label for="exit_status" class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Overall Exit Status</label>
                        <select name="exit_status" id="exit_status" required
                            class="w-full px-4 py-3.5 rounded-xl border border-slate-200 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all duration-300 text-sm font-semibold bg-white">
                            <option value="initiated" {{ old('exit_status', $termination->exit_status) == 'initiated' ? 'selected' : '' }}>Initiated</option>
                            <option value="in_progress" {{ old('exit_status', $termination->exit_status) == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="completed" {{ old('exit_status', $termination->exit_status) == 'completed' ? 'selected' : '' }}>Completed (Fully Deactivate Staff)</option>
                        </select>
                        @error('exit_status') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                    </div>

                    <!-- Final Settlement Status -->
                    <div>
                        <label for="final_settlement_status" class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Final Settlement Status</label>
                        <select name="final_settlement_status" id="final_settlement_status" required
                            class="w-full px-4 py-3.5 rounded-xl border border-slate-200 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all duration-300 text-sm font-semibold bg-white">
                            <option value="pending" {{ old('final_settlement_status', $termination->final_settlement_status) == 'pending' ? 'selected' : '' }}>Pending Settlement</option>
                            <option value="processed" {{ old('final_settlement_status', $termination->final_settlement_status) == 'processed' ? 'selected' : '' }}>Settlement Processed</option>
                            <option value="paid" {{ old('final_settlement_status', $termination->final_settlement_status) == 'paid' ? 'selected' : '' }}>Settlement Paid / Closed</option>
                        </select>
                        @error('final_settlement_status') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Asset Return Status -->
                    <div>
                        <label for="asset_return_status" class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Asset Return Status</label>
                        <select name="asset_return_status" id="asset_return_status" required
                            class="w-full px-4 py-3.5 rounded-xl border border-slate-200 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all duration-300 text-sm font-semibold bg-white">
                            <option value="pending" {{ old('asset_return_status', $termination->asset_return_status) == 'pending' ? 'selected' : '' }}>Pending Recovery</option>
                            <option value="partial" {{ old('asset_return_status', $termination->asset_return_status) == 'partial' ? 'selected' : '' }}>Partially Recovered</option>
                            <option value="completed" {{ old('asset_return_status', $termination->asset_return_status) == 'completed' ? 'selected' : '' }}>All Assets Recovered</option>
                        </select>
                        @error('asset_return_status') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                    </div>

                    <!-- Exit Interview Status -->
                    <div>
                        <label for="exit_interview_status" class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Exit Interview Status</label>
                        <select name="exit_interview_status" id="exit_interview_status" required
                            class="w-full px-4 py-3.5 rounded-xl border border-slate-200 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all duration-300 text-sm font-semibold bg-white">
                            <option value="pending" {{ old('exit_interview_status', $termination->exit_interview_status) == 'pending' ? 'selected' : '' }}>Pending Scheduling</option>
                            <option value="scheduled" {{ old('exit_interview_status', $termination->exit_interview_status) == 'scheduled' ? 'selected' : '' }}>Interview Scheduled</option>
                            <option value="completed" {{ old('exit_interview_status', $termination->exit_interview_status) == 'completed' ? 'selected' : '' }}>Interview Completed</option>
                            <option value="skipped" {{ old('exit_interview_status', $termination->exit_interview_status) == 'skipped' ? 'selected' : '' }}>Interview Skipped</option>
                        </select>
                        @error('exit_interview_status') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- Exit Interview Notes -->
                <div>
                    <label for="exit_interview_notes" class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Exit Interview Notes / Feedback</label>
                    <textarea name="exit_interview_notes" id="exit_interview_notes" rows="4"
                        class="w-full px-4 py-3.5 rounded-xl border border-slate-200 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all duration-300 placeholder:text-slate-300 text-sm font-semibold"
                        placeholder="Detail employee suggestions, feedback regarding growth barriers, workplace stress, policy improvements, or transition handovers...">{{ old('exit_interview_notes', $termination->exit_interview_notes) }}</textarea>
                    @error('exit_interview_notes') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                </div>

                <!-- Remarks -->
                <div>
                    <label for="remarks" class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Internal Admin Remarks</label>
                    <textarea name="remarks" id="remarks" rows="2"
                        class="w-full px-4 py-3.5 rounded-xl border border-slate-200 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all duration-300 placeholder:text-slate-300 text-sm font-semibold"
                        placeholder="Internal notes regarding settlements, pending dues, audit signatures, etc...">{{ old('remarks', $termination->remarks) }}</textarea>
                    @error('remarks') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                </div>

                <!-- Save Action -->
                <div class="flex justify-end pt-4">
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-extrabold text-xs rounded-xl shadow-md transition hover:-translate-y-0.5 active:translate-y-0">
                        Update Checklists & Settlements
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Right column: Employee Exit Summary Card -->
    <div class="space-y-8">
        <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6 space-y-6">
            <h4 class="font-extrabold text-xs text-slate-800 tracking-wider uppercase pb-2 border-b">
                Exit Profile Summary
            </h4>

            <div class="space-y-4 text-xs font-semibold text-slate-700">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-slate-900 text-white rounded-xl flex items-center justify-center font-bold text-sm shadow">
                        {{ strtoupper(substr($termination->employee->name ?? 'U', 0, 1)) }}
                    </div>
                    <div>
                        <span class="block text-sm font-bold text-slate-800 leading-none mb-1">{{ $termination->employee->name ?? 'Unknown' }}</span>
                        <span class="text-[10px] text-slate-400">{{ $termination->employee->employee_code ?? 'N/A' }} • {{ $termination->employee->department->department_name ?? 'N/A' }}</span>
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Exit Type:</span>
                        <span class="text-slate-800 uppercase tracking-wide font-extrabold">{{ str_replace('_', ' ', $termination->termination_type) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Last Working:</span>
                        <span class="text-slate-800 font-extrabold">{{ $termination->last_working_date->format('d M Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Notice Days:</span>
                        <span class="text-slate-800 font-bold">{{ $termination->notice_period_days }} Days</span>
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-4 space-y-2">
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-400">Final Month Dues:</span>
                        <span class="text-slate-800 font-extrabold">Rs. {{ number_format($termination->pending_salary, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-400">Leave Encashment:</span>
                        <span class="text-slate-800 font-extrabold">Rs. {{ number_format($termination->leave_encashment, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-xs pt-2 border-t border-dashed">
                        <span class="text-slate-500 font-extrabold">Total Settlement:</span>
                        <span class="text-indigo-600 font-black text-sm">Rs. {{ number_format($termination->pending_salary + $termination->leave_encashment, 2) }}</span>
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-4 space-y-2">
                    <div class="text-slate-400 uppercase text-[9px] tracking-widest block font-bold mb-1">Stated Reason</div>
                    <p class="text-slate-600 leading-relaxed bg-slate-50 p-3 rounded-xl border border-slate-100 font-medium">
                        {{ $termination->termination_reason }}
                    </p>
                </div>

                <div class="border-t border-slate-100 pt-4 space-y-1.5 text-[10px] text-slate-400 font-medium">
                    <div>Logged By: {{ $termination->terminatedBy->name ?? 'System Admin' }}</div>
                    <div>Timestamp: {{ $termination->terminated_at->format('Y-m-d H:i:s') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
