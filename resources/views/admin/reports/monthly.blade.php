@extends('layouts.admin')

@section('title', 'Monthly Summary')

@section('content')
<!-- Filter bar & Header metadata -->
<div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-5 rounded-3xl border border-slate-200/60 shadow-sm shadow-slate-100">
    <div class="flex items-center gap-2.5 text-slate-500">
        <div class="w-8 h-8 rounded-xl bg-slate-50 flex items-center justify-center text-slate-500 text-sm">
            <i class="bi bi-bar-chart-fill"></i>
        </div>
        <span class="text-xs font-semibold text-slate-500">Audit for <strong class="text-slate-800 font-bold">{{ DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}</strong></span>
    </div>
    
    <form action="{{ route('admin.reports.monthly') }}" method="GET" class="flex gap-2 w-full md:w-auto">
        <select name="month" class="px-4 py-2.5 rounded-xl border border-slate-200 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none text-xs font-bold text-slate-600 bg-white transition-all">
            @foreach(range(1, 12) as $m)
                <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                    {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                </option>
            @endforeach
        </select>
        <input type="number" name="year" value="{{ $year }}" min="2020" max="2100"
            class="px-4 py-2.5 rounded-xl border border-slate-200 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all text-xs font-bold text-slate-600 bg-white w-24">
        <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-extrabold rounded-xl shadow-md shadow-indigo-500/10 hover:shadow-indigo-500/20 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-300 text-xs">
            Filter Month
        </button>
    </form>
</div>

<!-- Table Card container -->
<div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50/70 border-b border-slate-100 text-slate-400 font-bold text-[10px] uppercase tracking-widest">
                <tr>
                    <th class="px-8 py-5">Employee Info</th>
                    <th class="px-8 py-5 text-center">Days Clocked</th>
                    <th class="px-8 py-5 text-center">Total Working Hours</th>
                    <th class="px-8 py-5 text-center">Avg. Daily Hours</th>
                    <th class="px-8 py-5 text-right">Corporate Record</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @php
                    $grouped = $attendances->groupBy('user_id');
                @endphp
                @forelse($grouped as $userId => $userAttendances)
                    @php
                        $user = $userAttendances->first()->user;
                        $daysPresent = $userAttendances->count();
                        $totalHours = $userAttendances->sum('working_hours');
                    @endphp
                    <tr class="hover:bg-slate-50/30 transition-colors group">
                        <!-- Employee info -->
                        <td class="px-8 py-4.5">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-gradient-to-tr from-indigo-500 to-purple-600 text-white rounded-full flex items-center justify-center font-bold text-sm shadow-md shadow-indigo-500/10 border-2 border-white">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-800 text-sm leading-tight mb-0.5">{{ $user->name }}</h4>
                                    <p class="text-xs text-slate-400 font-medium">{{ $user->employee_code }}</p>
                                </div>
                            </div>
                        </td>
                        
                        <!-- Days Present -->
                        <td class="px-8 py-4.5 text-center">
                            <span class="text-xs font-bold text-slate-700 bg-slate-50 border border-slate-100 px-3 py-1.5 rounded-xl">
                                {{ $daysPresent }} days
                            </span>
                        </td>
                        
                        <!-- Total Hours -->
                        <td class="px-8 py-4.5 text-center">
                            <span class="text-xs font-extrabold text-indigo-700 bg-indigo-50 border border-indigo-100/50 px-3 py-1.5 rounded-xl shadow-sm shadow-indigo-500/5">
                                {{ number_format($totalHours, 1) }}h total
                            </span>
                        </td>
                        
                        <!-- Avg Daily -->
                        <td class="px-8 py-4.5 text-center">
                            <span class="px-3 py-1.5 bg-slate-50 text-slate-600 rounded-xl text-xs font-bold border border-slate-100">
                                {{ number_format($totalHours / max($daysPresent, 1), 1) }}h/day
                            </span>
                        </td>
                        
                        <!-- Actions -->
                        <td class="px-8 py-4.5 text-right">
                            <button class="text-indigo-600 font-extrabold text-xs hover:underline hover:text-indigo-800 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                View Sheet
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-8 py-16 text-center text-slate-400">
                            <div class="flex flex-col items-center gap-4 max-w-sm mx-auto">
                                <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-300 text-2xl border border-slate-100">
                                    <i class="bi bi-graph-down"></i>
                                </div>
                                <div>
                                    <h5 class="font-bold text-slate-800 text-base mb-1">No monthly summary found</h5>
                                    <p class="text-xs text-slate-400 leading-relaxed font-semibold">We couldn't discover any logged attendance stats for this selected month and year.</p>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
