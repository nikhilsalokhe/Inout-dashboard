@extends('layouts.admin')

@section('title', 'Shift Policies & Assignments')

@section('content')
<div class="mb-8 flex flex-col sm:flex-row justify-between sm:items-center gap-4">
    <div>
        <p class="text-slate-500 text-sm font-medium">Create shift policies, assign working schedules, and define attendance thresholds.</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.shifts.assign') }}" class="px-5 py-3 bg-white hover:bg-slate-50 text-indigo-600 font-extrabold rounded-2xl border border-slate-200 shadow-sm transition-all duration-300 flex items-center justify-center gap-2 hover:-translate-y-0.5 active:translate-y-0">
            <i class="bi bi-person-plus text-sm"></i>
            <span class="text-sm">Assign Shifts</span>
        </a>
        <a href="{{ route('admin.shifts.create') }}" class="px-5 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-extrabold rounded-2xl shadow-md shadow-indigo-500/20 hover:shadow-indigo-500/30 transition-all duration-300 flex items-center justify-center gap-2 hover:-translate-y-0.5 active:translate-y-0">
            <i class="bi bi-plus-lg text-sm"></i>
            <span class="text-sm">Create Shift Policy</span>
        </a>
    </div>
</div>

<!-- Shift Policies Grid -->
<div class="mb-10">
    <div class="flex items-center gap-2 mb-6">
        <div class="w-1 h-5 bg-indigo-500 rounded-full"></div>
        <h3 class="font-bold text-slate-800 text-base">Active Shift Policies</h3>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($shifts as $shift)
            <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden flex flex-col relative group">
                <!-- Status Badge -->
                <div class="absolute top-6 right-6">
                    @if($shift->status === 'active')
                        <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 rounded-lg text-[10px] font-extrabold border border-emerald-100 uppercase tracking-wider">Active</span>
                    @else
                        <span class="px-2.5 py-1 bg-slate-50 text-slate-400 rounded-lg text-[10px] font-extrabold border border-slate-100 uppercase tracking-wider">Inactive</span>
                    @endif
                </div>

                <div class="p-6 flex-1">
                    <!-- Icon & Name -->
                    <div class="flex items-center gap-4.5 mb-6">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-500 text-xl font-bold">
                            @if($shift->shift_type === 'night')
                                <i class="bi bi-moon-stars"></i>
                            @elseif($shift->shift_type === 'flexible')
                                <i class="bi bi-infinity"></i>
                            @elseif($shift->shift_type === 'rotational')
                                <i class="bi bi-arrow-repeat"></i>
                            @else
                                <i class="bi bi-brightness-high"></i>
                            @endif
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-800 text-base leading-tight mb-1">{{ $shift->shift_name }}</h4>
                            <span class="text-[10px] font-extrabold text-indigo-500 uppercase tracking-widest bg-indigo-50/50 border border-indigo-100/30 px-2 py-0.5 rounded-md">
                                {{ $shift->shift_type }} Shift
                            </span>
                        </div>
                    </div>

                    <!-- Timings / Hours Details -->
                    <div class="space-y-3.5 border-t border-slate-100 pt-5 text-sm">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400 font-semibold text-xs">Work Hours</span>
                            <span class="text-slate-700 font-bold text-xs">
                                @if($shift->shift_type === 'flexible')
                                    Flexible Hours
                                @else
                                    {{ \Carbon\Carbon::createFromFormat('H:i:s', $shift->start_time)->format('h:i A') }} - 
                                    {{ \Carbon\Carbon::createFromFormat('H:i:s', $shift->end_time)->format('h:i A') }}
                                @endif
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400 font-semibold text-xs">Grace Time Allowed</span>
                            <span class="text-slate-700 font-bold text-xs">{{ $shift->grace_time_minutes }} minutes</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400 font-semibold text-xs">Full Day Requirement</span>
                            <span class="text-slate-700 font-bold text-xs">{{ $shift->minimum_working_hours }} hrs</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400 font-semibold text-xs">Half Day Requirement</span>
                            <span class="text-slate-700 font-bold text-xs">{{ $shift->half_day_time }} hrs</span>
                        </div>
                        <div class="flex justify-between items-start flex-col gap-1">
                            <span class="text-slate-400 font-semibold text-xs">Weekly Off Days</span>
                            <div class="flex flex-wrap gap-1 mt-1">
                                @foreach(explode(',', $shift->weekly_off_days) as $offDay)
                                    <span class="px-2 py-0.5 bg-slate-50 text-slate-500 rounded-md text-[9px] font-extrabold border border-slate-150">{{ trim($offDay) }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Card Actions -->
                <div class="px-6 py-4.5 bg-slate-50/50 border-t border-slate-100 flex items-center justify-end gap-2 group-hover:bg-slate-50 transition-colors">
                    <a href="{{ route('admin.shifts.edit', $shift->id) }}" class="px-3.5 py-2 rounded-xl bg-white hover:bg-indigo-50 border border-slate-200 hover:border-indigo-100 text-slate-500 hover:text-indigo-600 text-xs font-bold transition-all flex items-center gap-1.5 shadow-sm">
                        <i class="bi bi-pencil-square"></i>
                        <span>Configure</span>
                    </a>
                    @if($shift->status === 'active')
                        <form action="{{ route('admin.shifts.deactivate', $shift->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to deactivate this shift policy? No new employees can be assigned to it.');" class="inline">
                            @csrf
                            <button type="submit" class="px-3.5 py-2 rounded-xl bg-white hover:bg-rose-50 border border-slate-200 hover:border-rose-100 text-slate-400 hover:text-rose-600 text-xs font-bold transition-all flex items-center gap-1.5 shadow-sm">
                                <i class="bi bi-dash-circle"></i>
                                <span>Deactivate</span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-3xl border border-slate-200/60 p-12 text-center text-slate-400">
                <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-300 text-2xl border border-slate-100 mx-auto mb-4">
                    <i class="bi bi-clock"></i>
                </div>
                <h5 class="font-bold text-slate-800 text-base mb-1">No Shifts Policies Configured</h5>
                <p class="text-xs text-slate-400 max-w-sm mx-auto mb-4 leading-relaxed font-semibold">There are currently no shift policies registered. Please create a shift policy to proceed with assigning work hours.</p>
                <a href="{{ route('admin.shifts.create') }}" class="px-4 py-2 bg-indigo-50 border border-indigo-100 text-indigo-600 text-xs font-bold rounded-xl hover:bg-indigo-100 transition-colors">
                    Add Shift Policy
                </a>
            </div>
        @endforelse
    </div>
</div>

<!-- Active Assignments List -->
<div>
    <div class="flex items-center gap-2 mb-6">
        <div class="w-1 h-5 bg-indigo-500 rounded-full"></div>
        <h3 class="font-bold text-slate-800 text-base">Current Shift Assignments Log</h3>
    </div>

    <!-- Table Card container -->
    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50/70 border-b border-slate-100 text-slate-400 font-bold text-[10px] uppercase tracking-widest">
                    <tr>
                        <th class="px-8 py-5">Employee</th>
                        <th class="px-8 py-5">Shift Policy</th>
                        <th class="px-8 py-5">Active From</th>
                        <th class="px-8 py-5">Active To</th>
                        <th class="px-8 py-5">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($assignments as $assignment)
                        <tr class="hover:bg-slate-50/30 transition-colors">
                            <td class="px-8 py-4.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center font-bold text-xs text-indigo-600">
                                        {{ strtoupper(substr($assignment->employee->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-800 text-xs leading-tight mb-0.5">{{ $assignment->employee->name }}</h4>
                                        <p class="text-[9px] text-slate-400 font-medium">Code: {{ $assignment->employee->employee_code ?? '-' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-4.5">
                                <span class="font-bold text-slate-700 text-xs">{{ $assignment->shift->shift_name }}</span>
                                <span class="text-[9px] text-slate-400 block mt-0.5 uppercase font-semibold tracking-wider">{{ $assignment->shift->shift_type }} policy</span>
                            </td>
                            <td class="px-8 py-4.5 text-xs text-slate-500 font-bold">
                                {{ $assignment->effective_from->format('M d, Y') }}
                            </td>
                            <td class="px-8 py-4.5 text-xs text-slate-500 font-semibold">
                                @if($assignment->effective_to)
                                    {{ $assignment->effective_to->format('M d, Y') }}
                                @else
                                    <span class="text-emerald-600 font-bold text-[10px] tracking-wide uppercase bg-emerald-50 px-2 py-0.5 rounded border border-emerald-150">Ongoing</span>
                                @endif
                            </td>
                            <td class="px-8 py-4.5">
                                @php
                                    $today = \Carbon\Carbon::today();
                                    $isActive = $today->greaterThanOrEqualTo($assignment->effective_from) && 
                                                (is_null($assignment->effective_to) || $today->lessThanOrEqualTo($assignment->effective_to));
                                @endphp
                                @if($isActive)
                                    <span class="inline-flex items-center gap-1.5 text-emerald-700 text-[10px] font-extrabold bg-emerald-50 border border-emerald-100 px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                                        Active Now
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-slate-400 text-[10px] font-extrabold bg-slate-50 border border-slate-100 px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                                        Expired
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-8 py-12 text-center text-slate-400">
                                <div class="w-12 h-12 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-300 text-lg border border-slate-100 mx-auto mb-3">
                                    <i class="bi bi-journal-x"></i>
                                </div>
                                <h6 class="font-bold text-slate-700 text-xs mb-0.5">No shift assignments logged</h6>
                                <p class="text-[10px] text-slate-400 font-semibold">Assign employees to shift policies to start collecting and auditing their schedules.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($assignments->hasPages())
            <div class="p-6 border-t border-slate-100 bg-slate-50/50">
                {{ $assignments->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
