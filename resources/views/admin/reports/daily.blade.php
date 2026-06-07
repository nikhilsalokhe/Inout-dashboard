@extends('layouts.admin')

@section('title', 'Daily Attendance Log')

@section('content')
<!-- Filter bar & Header metadata -->
<div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-5 rounded-3xl border border-slate-200/60 shadow-sm shadow-slate-100">
    <div class="flex items-center gap-2.5 text-slate-500">
        <div class="w-8 h-8 rounded-xl bg-slate-50 flex items-center justify-center text-slate-500 text-sm">
            <i class="bi bi-calendar3"></i>
        </div>
        <span class="text-xs font-semibold text-slate-500">Auditing logs for <strong class="text-slate-800 font-bold">{{ \Carbon\Carbon::parse($date)->format('F d, Y') }}</strong></span>
    </div>
    
    <form action="{{ route('admin.reports.daily') }}" method="GET" class="flex gap-2 w-full md:w-auto">
        <input type="date" name="date" value="{{ $date }}" 
            class="px-4 py-2.5 rounded-xl border border-slate-200 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all text-xs font-bold text-slate-600 bg-white">
        <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-extrabold rounded-xl shadow-md shadow-indigo-500/10 hover:shadow-indigo-500/20 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-300 text-xs">
            Filter Log
        </button>
    </form>
</div>

<!-- Table Card wrapper -->
<div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50/70 border-b border-slate-100 text-slate-400 font-bold text-[10px] uppercase tracking-widest">
                <tr>
                    <th class="px-8 py-5">Employee Name</th>
                    <th class="px-8 py-5 text-center">Clock In</th>
                    <th class="px-8 py-5 text-center">Clock Out</th>
                    <th class="px-8 py-5 text-center">Active Hours</th>
                    <th class="px-8 py-5">Scan Verification</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($attendances as $attendance)
                    <tr class="hover:bg-slate-50/30 transition-colors">
                        <!-- Employee profile -->
                        <td class="px-8 py-4.5">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-gradient-to-tr from-indigo-500 to-purple-600 text-white rounded-full flex items-center justify-center font-bold text-sm shadow-md shadow-indigo-500/10 border-2 border-white">
                                    {{ strtoupper(substr($attendance->user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-800 text-sm leading-tight mb-0.5">{{ $attendance->user->name }}</h4>
                                    <p class="text-xs text-slate-400 font-medium">{{ $attendance->user->employee_code }}</p>
                                </div>
                            </div>
                        </td>
                        
                        <!-- Clock in -->
                        <td class="px-8 py-4.5 text-center">
                            <span class="text-xs font-bold text-slate-700 bg-slate-50 border border-slate-100 px-3 py-1.5 rounded-xl">
                                {{ $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('h:i A') : '--:--' }}
                            </span>
                        </td>
                        
                        <!-- Clock out -->
                        <td class="px-8 py-4.5 text-center">
                            <span class="text-xs font-bold text-slate-700 bg-slate-50 border border-slate-100 px-3 py-1.5 rounded-xl">
                                {{ $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('h:i A') : '--:--' }}
                            </span>
                        </td>
                        
                        <!-- Active Hours -->
                        <td class="px-8 py-4.5 text-center">
                            <span class="px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded-xl text-xs font-extrabold border border-indigo-100/50">
                                {{ number_format($attendance->working_hours, 1) }} hrs
                            </span>
                        </td>
                        
                        <!-- Verification -->
                        <td class="px-8 py-4.5">
                            @if($attendance->image)
                                <span class="flex items-center gap-1.5 text-emerald-700 text-xs font-bold bg-emerald-50 border border-emerald-100/60 px-3 py-1 rounded-full w-fit shadow-sm shadow-emerald-500/5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-[0_0_6px_#10b981]"></span>
                                    AI Verified
                                </span>
                            @else
                                <span class="flex items-center gap-1.5 text-amber-700 text-xs font-bold bg-amber-50 border border-amber-100/60 px-3 py-1 rounded-full w-fit">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                    Manual Adjust
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-8 py-16 text-center text-slate-400">
                            <div class="flex flex-col items-center gap-4 max-w-sm mx-auto">
                                <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-300 text-2xl border border-slate-100">
                                    <i class="bi bi-calendar-x"></i>
                                </div>
                                <div>
                                    <h5 class="font-bold text-slate-800 text-base mb-1">No shifts recorded</h5>
                                    <p class="text-xs text-slate-400 leading-relaxed font-semibold">We couldn't discover any active attendance records logged for this selected date.</p>
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
