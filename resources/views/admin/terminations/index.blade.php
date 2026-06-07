@extends('layouts.admin')

@section('title', 'Exit & Termination Management')

@section('content')
<div class="mb-8 flex flex-col sm:flex-row justify-between sm:items-center gap-4">
    <div>
        <p class="text-slate-500 text-sm font-medium">Track employee offboarding, notice periods, final settlements, asset recovery, and exit interviews.</p>
    </div>
    <a href="{{ route('admin.terminations.reports') }}" class="px-5 py-3 bg-gradient-to-r from-slate-800 to-slate-900 hover:from-slate-900 hover:to-black text-white font-extrabold rounded-2xl shadow-md transition-all duration-300 flex items-center justify-center gap-2 self-start sm:self-auto hover:-translate-y-0.5 active:translate-y-0">
        <i class="bi bi-bar-chart-line-fill text-sm"></i>
        <span class="text-sm">Exit Analytics</span>
    </a>
</div>

<!-- Filter Bar -->
<div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6 mb-6">
    <form action="{{ route('admin.terminations.index') }}" method="GET" class="flex flex-wrap items-end gap-4">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-[10px] font-extrabold uppercase tracking-widest text-slate-400 mb-2">Exit Status</label>
            <select name="exit_status" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-700 focus:outline-none focus:border-indigo-500 transition duration-200">
                <option value="">All Exit Statuses</option>
                <option value="initiated" {{ request('exit_status') === 'initiated' ? 'selected' : '' }}>Initiated</option>
                <option value="in_progress" {{ request('exit_status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                <option value="completed" {{ request('exit_status') === 'completed' ? 'selected' : '' }}>Completed</option>
            </select>
        </div>

        <div class="flex-1 min-w-[200px]">
            <label class="block text-[10px] font-extrabold uppercase tracking-widest text-slate-400 mb-2">Termination Type</label>
            <select name="termination_type" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-700 focus:outline-none focus:border-indigo-500 transition duration-200">
                <option value="">All Termination Types</option>
                <option value="resigned" {{ request('termination_type') === 'resigned' ? 'selected' : '' }}>Resigned</option>
                <option value="terminated" {{ request('termination_type') === 'terminated' ? 'selected' : '' }}>Terminated</option>
                <option value="absconded" {{ request('termination_type') === 'absconded' ? 'selected' : '' }}>Absconded</option>
                <option value="retired" {{ request('termination_type') === 'retired' ? 'selected' : '' }}>Retired</option>
                <option value="contract_completed" {{ request('termination_type') === 'contract_completed' ? 'selected' : '' }}>Contract Completed</option>
            </select>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="px-5 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 font-extrabold text-xs uppercase tracking-wider rounded-xl transition duration-200 border border-indigo-200/50">
                Apply Filters
            </button>
            @if(request()->anyFilled(['exit_status', 'termination_type']))
                <a href="{{ route('admin.terminations.index') }}" class="px-5 py-2.5 bg-slate-50 hover:bg-slate-100 text-slate-500 font-extrabold text-xs uppercase tracking-wider rounded-xl transition duration-200 border border-slate-200">
                    Reset
                </a>
            @endif
        </div>
    </form>
</div>

<!-- Table Card container -->
<div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50/70 border-b border-slate-100 text-slate-400 font-bold text-[10px] uppercase tracking-widest">
                <tr>
                    <th class="px-8 py-5">Employee Info</th>
                    <th class="px-8 py-5">Exit Type</th>
                    <th class="px-8 py-5">Last Working Date</th>
                    <th class="px-8 py-5">Settlement Status</th>
                    <th class="px-8 py-5">Exit Status</th>
                    <th class="px-8 py-5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                @forelse($terminations as $term)
                    <tr class="hover:bg-slate-50/30 transition-colors group">
                        <!-- Employee Info -->
                        <td class="px-8 py-4.5">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-gradient-to-tr from-slate-700 to-slate-900 text-white rounded-full flex items-center justify-center font-bold text-sm shadow-md border-2 border-white">
                                    {{ strtoupper(substr($term->employee->name ?? 'U', 0, 1)) }}
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-800 text-sm leading-tight mb-0.5">{{ $term->employee->name ?? 'Unknown' }}</h4>
                                    <p class="text-xs text-slate-400 font-medium">{{ $term->employee->employee_code ?? 'N/A' }} • {{ $term->employee->department->department_name ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </td>

                        <!-- Exit Type -->
                        <td class="px-8 py-4.5">
                            <span class="text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded-lg border {{ $term->termination_type === 'terminated' || $term->termination_type === 'absconded' ? 'bg-rose-50 text-rose-700 border-rose-100' : 'bg-amber-50 text-amber-700 border-amber-100' }}">
                                {{ str_replace('_', ' ', $term->termination_type) }}
                            </span>
                        </td>

                        <!-- Last Working Date -->
                        <td class="px-8 py-4.5">
                            <span class="text-xs font-bold text-slate-600 block">{{ $term->last_working_date->format('d M Y') }}</span>
                            <span class="text-[10px] text-slate-400 font-medium mt-0.5">Notice: {{ $term->notice_period_days }} Days</span>
                        </td>

                        <!-- Settlement Status -->
                        <td class="px-8 py-4.5">
                            @if($term->final_settlement_status === 'paid')
                                <span class="text-emerald-700 text-xs font-bold bg-emerald-50 border border-emerald-100 px-3 py-1 rounded-full w-fit flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Fully Settled
                                </span>
                            @elseif($term->final_settlement_status === 'processed')
                                <span class="text-indigo-700 text-xs font-bold bg-indigo-50 border border-indigo-100 px-3 py-1 rounded-full w-fit flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span> Processed
                                </span>
                            @else
                                <span class="text-amber-700 text-xs font-bold bg-amber-50 border border-amber-100 px-3 py-1 rounded-full w-fit flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> Pending Settlement
                                </span>
                            @endif
                        </td>

                        <!-- Exit Status -->
                        <td class="px-8 py-4.5">
                            @if($term->exit_status === 'completed')
                                <span class="text-slate-700 text-xs font-bold bg-slate-100 border border-slate-200 px-3 py-1 rounded-full w-fit flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span> Completed
                                </span>
                            @elseif($term->exit_status === 'in_progress')
                                <span class="text-indigo-700 text-xs font-bold bg-indigo-50 border border-indigo-100 px-3 py-1 rounded-full w-fit flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span> In Progress
                                </span>
                            @else
                                <span class="text-amber-700 text-xs font-bold bg-amber-50 border border-amber-100 px-3 py-1 rounded-full w-fit flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Initiated
                                </span>
                            @endif
                        </td>

                        <!-- Actions -->
                        <td class="px-8 py-4.5 text-right">
                            <div class="flex items-center justify-end gap-1.5 opacity-0 group-hover:opacity-100 transition-all duration-300">
                                <a href="{{ route('admin.terminations.show', $term->id) }}" class="w-8 h-8 rounded-xl bg-slate-50 hover:bg-indigo-50 text-slate-400 hover:text-indigo-600 flex items-center justify-center transition-colors border border-transparent hover:border-indigo-100 shadow-sm" title="Manage Checklist & Settlement">
                                    <i class="bi bi-check2-square text-sm"></i>
                                </a>
                                <a href="{{ route('admin.terminations.print', $term->id) }}" target="_blank" class="w-8 h-8 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-400 hover:text-slate-600 flex items-center justify-center transition-colors border border-transparent hover:border-slate-200 shadow-sm" title="Print Exit Summary">
                                    <i class="bi bi-printer text-sm"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-8 py-16 text-center text-slate-400">
                            <div class="flex flex-col items-center gap-4 max-w-sm mx-auto">
                                <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-300 text-2xl border border-slate-100">
                                    <i class="bi bi-door-closed-fill"></i>
                                </div>
                                <div>
                                    <h5 class="font-bold text-slate-800 text-base mb-1">No exits logged</h5>
                                    <p class="text-xs text-slate-400 leading-relaxed font-semibold">There are no termination or resignation lifecycles recorded currently.</p>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($terminations->hasPages())
        <div class="p-6 border-t border-slate-100 bg-slate-50/50">
            {{ $terminations->links() }}
        </div>
    @endif
</div>
@endsection
