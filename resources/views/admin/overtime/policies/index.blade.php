@extends('layouts.admin')

@section('title', 'Overtime Policies')

@section('content')
<div class="max-w-7xl mx-auto space-y-6 animate-fade-in">
    <!-- Header -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-purple-950 text-white rounded-3xl p-8 shadow-xl relative overflow-hidden">
        <div class="absolute right-0 top-0 -mt-6 -mr-6 w-72 h-72 bg-indigo-500/10 rounded-full blur-3xl"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-orange-400 bg-orange-950/60 px-3 py-1 rounded-full border border-orange-900/50 inline-block mb-3">
                    Overtime Engine
                </span>
                <h2 class="text-3xl font-extrabold tracking-tight mb-2">Policy Configurations</h2>
                <p class="text-slate-300 text-sm max-w-xl">
                    Create and manage overtime rules, multiplier rates, and limits for daily, weekly, and monthly calculations.
                </p>
            </div>
            <div>
                <a href="{{ route('admin.overtime.policies.create') }}" class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-white hover:bg-slate-50 text-indigo-900 font-bold text-sm shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all duration-300">
                    <i class="bi bi-plus-lg"></i>
                    <span>Create Policy</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-800">Active Policies</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100 text-xs uppercase tracking-wider font-bold text-slate-500">
                        <th class="p-4 pl-6">Policy Name</th>
                        <th class="p-4">Rate Type</th>
                        <th class="p-4">Active Rules</th>
                        <th class="p-4 text-center">Status</th>
                        <th class="p-4 pr-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($policies as $policy)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="p-4 pl-6">
                            <div class="font-bold text-slate-800">{{ $policy->name }}</div>
                            <div class="text-xs text-slate-500 truncate max-w-xs">{{ $policy->description }}</div>
                        </td>
                        <td class="p-4">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md font-semibold text-[11px] {{ $policy->rate_type === 'salary_based' ? 'bg-indigo-50 text-indigo-700' : 'bg-emerald-50 text-emerald-700' }}">
                                {{ $policy->rate_type === 'salary_based' ? 'Salary Multiplier' : 'Fixed Rate: ₹' . $policy->fixed_rate }}
                            </span>
                        </td>
                        <td class="p-4">
                            <div class="flex flex-wrap gap-1">
                                @if($policy->calc_daily) <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 text-[10px] font-bold">Daily</span> @endif
                                @if($policy->calc_weekly) <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 text-[10px] font-bold">Weekly</span> @endif
                                @if($policy->calc_monthly) <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 text-[10px] font-bold">Monthly</span> @endif
                                @if($policy->calc_weekend) <span class="px-2 py-0.5 rounded bg-rose-50 text-rose-600 text-[10px] font-bold">Weekend</span> @endif
                                @if($policy->calc_holiday) <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-600 text-[10px] font-bold">Holiday</span> @endif
                            </div>
                        </td>
                        <td class="p-4 text-center">
                            @if($policy->is_active)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-600 text-xs font-bold border border-emerald-100">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-rose-50 text-rose-600 text-xs font-bold border border-rose-100">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Inactive
                                </span>
                            @endif
                        </td>
                        <td class="p-4 pr-6 text-right space-x-2">
                            <a href="{{ route('admin.overtime.policies.edit', $policy) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-indigo-600 hover:bg-indigo-50 transition-colors">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <form action="{{ route('admin.overtime.policies.destroy', $policy) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this policy?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-rose-600 hover:bg-rose-50 transition-colors">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-slate-500">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-50 mb-3 text-slate-400">
                                <i class="bi bi-inbox text-2xl"></i>
                            </div>
                            <p class="font-medium">No policies found.</p>
                            <a href="{{ route('admin.overtime.policies.create') }}" class="text-indigo-600 hover:underline text-sm font-semibold mt-1 inline-block">Create your first policy</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $policies->links() }}
        </div>
    </div>
</div>
@endsection
