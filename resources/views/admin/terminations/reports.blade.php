@extends('layouts.admin')

@section('title', 'Offboarding & Exit Analytics')

@section('content')
<div class="mb-8 flex flex-col sm:flex-row justify-between sm:items-center gap-4">
    <div>
        <p class="text-slate-500 text-sm font-medium">Analyze offboarding volumes, exit reasons, resignation percentages, and final settlement outlays.</p>
    </div>
    <a href="{{ route('admin.terminations.index') }}" class="px-5 py-3 bg-white border border-slate-200 text-slate-500 hover:text-slate-700 font-extrabold rounded-2xl transition duration-200 hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-2">
        <i class="bi bi-arrow-left-short text-lg"></i>
        <span class="text-sm">Exit Directory</span>
    </a>
</div>

<!-- Grid of Key Analytics Summary -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Settlement outlays -->
    @php
        $totalDues = $settlementStats->sum('total_amount');
        $paidDues = $settlementStats->firstWhere('final_settlement_status', 'paid')->total_amount ?? 0;
        $pendingDues = $settlementStats->firstWhere('final_settlement_status', 'pending')->total_amount ?? 0;
    @endphp
    <div class="bg-white p-6 rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden relative group">
        <div class="absolute top-0 left-0 right-0 h-[3.5px] bg-gradient-to-r from-indigo-500 to-indigo-600"></div>
        <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center text-lg mb-3">
            <i class="bi bi-cash-coin"></i>
        </div>
        <h3 class="text-2xl font-black text-slate-900 tracking-tight mb-1">Rs. {{ number_format($totalDues, 2) }}</h3>
        <p class="text-slate-400 text-[9px] font-bold uppercase tracking-widest leading-none">Total Settlement Dues</p>
        <div class="flex items-center justify-between text-[10px] font-semibold text-slate-400 mt-4 pt-3 border-t border-slate-100">
            <span>PAID: <strong class="text-emerald-600">Rs. {{ number_format($paidDues, 0) }}</strong></span>
            <span>PENDING: <strong class="text-rose-500">Rs. {{ number_format($pendingDues, 0) }}</strong></span>
        </div>
    </div>

    <!-- Total resignations vs involuntary -->
    @php
        $totalVoluntary = $typeStats->whereIn('termination_type', ['resigned', 'retired', 'contract_completed'])->sum('count');
        $totalInvoluntary = $typeStats->whereIn('termination_type', ['terminated', 'absconded'])->sum('count');
        $grandTotal = $totalVoluntary + $totalInvoluntary;
    @endphp
    <div class="bg-white p-6 rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden relative group">
        <div class="absolute top-0 left-0 right-0 h-[3.5px] bg-gradient-to-r from-amber-400 to-amber-500"></div>
        <div class="w-10 h-10 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center text-lg mb-3">
            <i class="bi bi-person-x"></i>
        </div>
        <h3 class="text-2xl font-black text-slate-900 tracking-tight mb-1">{{ $grandTotal }} Exits</h3>
        <p class="text-slate-400 text-[9px] font-bold uppercase tracking-widest leading-none">Offboarding Volume</p>
        <div class="flex items-center justify-between text-[10px] font-semibold text-slate-400 mt-4 pt-3 border-t border-slate-100">
            <span>VOLUNTARY: <strong class="text-slate-700">{{ $totalVoluntary }}</strong></span>
            <span>INVOLUNTARY: <strong class="text-slate-700">{{ $totalInvoluntary }}</strong></span>
        </div>
    </div>

    <!-- Settlement closure percentage -->
    @php
        $paidCount = $settlementStats->firstWhere('final_settlement_status', 'paid')->count ?? 0;
        $totalCount = $settlementStats->sum('count');
        $closureRate = $totalCount > 0 ? round(($paidCount / $totalCount) * 100, 1) : 0;
    @endphp
    <div class="bg-white p-6 rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden relative group">
        <div class="absolute top-0 left-0 right-0 h-[3.5px] bg-gradient-to-r from-emerald-400 to-emerald-500"></div>
        <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center text-lg mb-3">
            <i class="bi bi-shield-check"></i>
        </div>
        <h3 class="text-2xl font-black text-emerald-600 tracking-tight mb-1">{{ $closureRate }}%</h3>
        <p class="text-slate-400 text-[9px] font-bold uppercase tracking-widest leading-none">Settlement Closure Rate</p>
        <div class="flex items-center justify-between text-[10px] font-semibold text-slate-400 mt-4 pt-3 border-t border-slate-100">
            <span>CLOSED: <strong class="text-slate-700">{{ $paidCount }} Cases</strong></span>
            <span>TOTAL EXITS: <strong class="text-slate-700">{{ $totalCount }} Cases</strong></span>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Left Column: Departure type and Settlement metrics -->
    <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6 space-y-6">
        <h4 class="font-extrabold text-xs text-slate-800 tracking-wider uppercase mb-4 pb-2 border-b">
            Exit Type Breakdown
        </h4>

        <div class="space-y-4 font-semibold text-slate-700">
            @php
                $colorMap = [
                    'resigned' => 'bg-indigo-500 text-indigo-700 border-indigo-200',
                    'terminated' => 'bg-rose-500 text-rose-700 border-rose-200',
                    'absconded' => 'bg-red-500 text-red-700 border-red-200',
                    'retired' => 'bg-emerald-500 text-emerald-700 border-emerald-200',
                    'contract_completed' => 'bg-purple-500 text-purple-700 border-purple-200',
                ];
                $bgMap = [
                    'resigned' => 'bg-indigo-500',
                    'terminated' => 'bg-rose-500',
                    'absconded' => 'bg-red-500',
                    'retired' => 'bg-emerald-500',
                    'contract_completed' => 'bg-purple-500',
                ];
            @endphp
            @forelse($typeStats as $stat)
                @php
                    $pct = $grandTotal > 0 ? round(($stat->count / $grandTotal) * 100) : 0;
                @endphp
                <div>
                    <div class="flex justify-between items-center text-xs mb-1.5 font-bold">
                        <span class="text-slate-700 uppercase tracking-wide text-[10px]">{{ str_replace('_', ' ', $stat->termination_type) }}</span>
                        <span class="text-slate-400">{{ $stat->count }} ({{ $pct }}%)</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                        <div class="{{ $bgMap[$stat->termination_type] ?? 'bg-slate-500' }} h-full" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-slate-400 text-xs italic">No exit type data available.</p>
            @endforelse
        </div>
    </div>

    <!-- Right Column: Monthly Exit Volumes -->
    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6 space-y-6">
        <h4 class="font-extrabold text-xs text-slate-800 tracking-wider uppercase mb-4 pb-2 border-b">
            Monthly Exit Frequency
        </h4>

        <div class="space-y-4 text-xs font-semibold text-slate-700">
            @forelse($monthlyStats as $monthStat)
                <div class="flex justify-between items-center p-3 bg-slate-50 border border-slate-100 rounded-2xl">
                    <span class="font-bold text-slate-800"><i class="bi bi-calendar-event text-slate-400 mr-2"></i>{{ $monthStat->month }}</span>
                    <span class="px-2.5 py-1 text-[10px] font-black text-indigo-700 bg-indigo-50 border border-indigo-100 rounded-lg">
                        {{ $monthStat->count }} exits
                    </span>
                </div>
            @empty
                <p class="text-slate-400 text-xs italic text-center p-8">No monthly data logged.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
