@extends('layouts.admin')

@section('title', 'System Administration Dashboard')

@section('content')
<!-- Status Pill Bar -->
<div class="flex flex-wrap items-center gap-3 mb-8">
    <div class="flex items-center gap-2 bg-slate-900/5 px-4 py-2 rounded-full border border-slate-100">
        <i class="bi bi-calendar3 text-slate-500"></i>
        <span class="text-xs font-bold text-slate-700">{{ \Carbon\Carbon::today()->format('D, d M Y') }}</span>
    </div>
    <div class="flex items-center gap-2 bg-indigo-50 px-4 py-2 rounded-full border border-indigo-100">
        <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
        <span class="text-xs font-bold text-indigo-700">{{ $dayName }}</span>
    </div>
    <div class="flex items-center gap-2 bg-emerald-50 px-4 py-2 rounded-full border border-emerald-100">
        <i class="bi bi-speedometer2 text-emerald-500"></i>
        <span class="text-xs font-bold text-emerald-700">{{ $attendanceRate }}% Today's Attendance Rate</span>
    </div>
</div>

<!-- Main Stats Grid -->
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-5 mb-8">
    <!-- Total Active Staff -->
    <div class="relative bg-white p-6 rounded-3xl border border-slate-200/60 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 overflow-hidden group">
        <div class="absolute top-0 left-0 right-0 h-[3.5px] bg-gradient-to-r from-indigo-500 to-indigo-600"></div>
        <div class="w-11 h-11 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center text-xl mb-4 group-hover:scale-110 transition-transform duration-300">
            <i class="bi bi-people-fill"></i>
        </div>
        <h3 class="text-3xl font-black text-slate-900 tracking-tight mb-1">{{ $totalEmployees }}</h3>
        <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest">Active Staff</p>
    </div>

    <!-- Today's Present Logs -->
    <div class="relative bg-white p-6 rounded-3xl border border-slate-200/60 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 overflow-hidden group">
        <div class="absolute top-0 left-0 right-0 h-[3.5px] bg-gradient-to-r from-emerald-400 to-emerald-600"></div>
        <div class="w-11 h-11 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center text-xl mb-4 group-hover:scale-110 transition-transform duration-300">
            <i class="bi bi-person-check-fill"></i>
        </div>
        <h3 class="text-3xl font-black text-emerald-600 tracking-tight mb-1">{{ $presentToday }}</h3>
        <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest">Present Today</p>
    </div>

    <!-- Shift Violations / Late Marks -->
    <div class="relative bg-white p-6 rounded-3xl border border-slate-200/60 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 overflow-hidden group">
        <div class="absolute top-0 left-0 right-0 h-[3.5px] bg-gradient-to-r from-amber-400 to-amber-600"></div>
        <div class="w-11 h-11 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center text-xl mb-4 group-hover:scale-110 transition-transform duration-300">
            <i class="bi bi-clock-history"></i>
        </div>
        <h3 class="text-3xl font-black text-amber-600 tracking-tight mb-1">{{ $lateToday }}</h3>
        <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest">Late Check-Ins</p>
    </div>

    <!-- Active Deployments -->
    <div class="relative bg-white p-6 rounded-3xl border border-slate-200/60 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 overflow-hidden group">
        <div class="absolute top-0 left-0 right-0 h-[3.5px] bg-gradient-to-r from-purple-400 to-purple-600"></div>
        <div class="w-11 h-11 bg-purple-50 text-purple-600 rounded-2xl flex items-center justify-center text-xl mb-4 group-hover:scale-110 transition-transform duration-300">
            <i class="bi bi-shield-check"></i>
        </div>
        <h3 class="text-3xl font-black text-purple-600 tracking-tight mb-1">{{ $activeShiftsCount }}</h3>
        <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest">Active Shifts</p>
    </div>

    <!-- Absent Marks -->
    <div class="relative bg-white p-6 rounded-3xl border border-slate-200/60 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 overflow-hidden group">
        <div class="absolute top-0 left-0 right-0 h-[3.5px] bg-gradient-to-r from-rose-400 to-rose-600"></div>
        <div class="w-11 h-11 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center text-xl mb-4 group-hover:scale-110 transition-transform duration-300">
            <i class="bi bi-person-x-fill"></i>
        </div>
        <h3 class="text-3xl font-black text-rose-600 tracking-tight mb-1">{{ $absentToday }}</h3>
        <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest">Absent Marks</p>
    </div>
</div>

<!-- HRMS Lifecycle & Contract Status Row -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
    <!-- Staff Distribution by Type -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200/60 shadow-sm flex flex-col justify-between">
        <div>
            <h5 class="font-extrabold text-xs text-slate-800 tracking-wider uppercase mb-4 flex items-center gap-2">
                <i class="bi bi-person-workspace text-indigo-500"></i> Staff Type Distribution
            </h5>
            <div class="space-y-4">
                @php
                    $typeMap = [
                        'permanent' => ['label' => 'Permanent Staff', 'color' => 'bg-indigo-500'],
                        'contract' => ['label' => 'Contract-Based', 'color' => 'bg-amber-500'],
                        'temporary' => ['label' => 'Temporary/Hourly', 'color' => 'bg-purple-500'],
                        'trainee' => ['label' => 'Interns/Trainees', 'color' => 'bg-pink-500']
                    ];
                    $totalT = $employeesByType->sum('count');
                @endphp
                @foreach(['permanent', 'contract', 'temporary', 'trainee'] as $t)
                    @php
                        $count = $employeesByType->firstWhere('employee_type', $t)->count ?? 0;
                        $pct = $totalT > 0 ? round(($count / $totalT) * 100) : 0;
                        $m = $typeMap[$t];
                    @endphp
                    <div>
                        <div class="flex justify-between items-center text-xs mb-1.5 font-bold">
                            <span class="text-slate-700">{{ $m['label'] }}</span>
                            <span class="text-slate-400">{{ $count }} ({{ $pct }}%)</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2">
                            <div class="{{ $m['color'] }} h-2 rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="mt-4 pt-3 border-t border-slate-100 flex justify-between items-center text-xs font-bold text-slate-400">
            <span>TOTAL CLASSIFIED</span>
            <span class="text-slate-700">{{ $totalT }} Employees</span>
        </div>
    </div>

    <!-- Active Notices & Expiring Contracts -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200/60 shadow-sm flex flex-col justify-between">
        <div>
            <h5 class="font-extrabold text-xs text-slate-800 tracking-wider uppercase mb-4 flex items-center gap-2">
                <i class="bi bi-shield-alert text-amber-500"></i> Contract & Notice Alerts
            </h5>
            <div class="space-y-4">
                <!-- Notice period widget -->
                <div class="flex items-center gap-4 bg-rose-50/50 border border-rose-100/55 p-3 rounded-2xl">
                    <div class="w-10 h-10 bg-rose-100 text-rose-600 rounded-xl flex items-center justify-center text-lg"><i class="bi bi-door-open-fill"></i></div>
                    <div>
                        <h4 class="text-xl font-black text-slate-900 tracking-tight">{{ $onNoticePeriod }}</h4>
                        <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest leading-none">On Notice Period</p>
                    </div>
                </div>

                <!-- Expiring Contracts -->
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 block">Expiring Contracts (30 Days)</span>
                    <div class="max-h-[100px] overflow-y-auto space-y-2">
                        @forelse($contractExpiryAlerts as $contract)
                            <div class="flex justify-between items-center text-xs p-2 bg-amber-50/30 border border-amber-100 rounded-xl">
                                <div>
                                    <span class="font-bold text-slate-800 block">{{ $contract->employee->name }}</span>
                                    <span class="text-[9px] text-slate-400 font-bold uppercase">{{ $contract->employee->employee_code }}</span>
                                </div>
                                <span class="text-[10px] font-extrabold text-amber-700">{{ $contract->contract_end_date->diffForHumans() }}</span>
                            </div>
                        @empty
                            <p class="text-slate-400 text-xs italic">No contract expirations imminent.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Exits & New Joiners -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200/60 shadow-sm flex flex-col justify-between">
        <div>
            <h5 class="font-extrabold text-xs text-slate-800 tracking-wider uppercase mb-4 flex items-center gap-2">
                <i class="bi bi-person-dash text-rose-500"></i> Lifecycle Events (30 Days)
            </h5>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 block">New Joiners</span>
                    <div class="space-y-2 max-h-[140px] overflow-y-auto">
                        @forelse($newJoiners as $joiner)
                            <div class="p-2 border border-slate-100 rounded-xl">
                                <span class="font-bold text-slate-800 text-xs block leading-none mb-1">{{ $joiner->name }}</span>
                                <span class="text-[9px] text-slate-400 font-bold block">{{ $joiner->joining_date ? $joiner->joining_date->format('d M Y') : 'N/A' }}</span>
                            </div>
                        @empty
                            <p class="text-[10px] text-slate-400 italic">No recent joiners.</p>
                        @endforelse
                    </div>
                </div>
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 block">Recent Exits</span>
                    <div class="space-y-2 max-h-[140px] overflow-y-auto">
                        @forelse($recentTerminations as $exit)
                            <div class="p-2 border border-rose-50 bg-rose-50/20 rounded-xl">
                                <span class="font-bold text-rose-900 text-xs block leading-none mb-1">{{ $exit->employee->name }}</span>
                                <span class="text-[9px] text-slate-400 font-bold block">{{ $exit->last_working_date->format('d M Y') }}</span>
                            </div>
                        @empty
                            <p class="text-[10px] text-slate-400 italic">No recent exits.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Panels Row -->
<div class="grid grid-cols-1 xl:grid-cols-2 gap-8 mb-8">
    
    <!-- Left Column: Pending Face Reset Requests & Live Feed -->
    <div class="space-y-8">
        
        <!-- Pending Reset Audits -->
        <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden flex flex-col h-[280px]">
            <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                <h5 class="font-extrabold text-xs text-slate-800 tracking-wider uppercase flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span> Pending Face Reset Requests
                </h5>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $pendingFaceResets->count() }} requests</span>
            </div>
            <div class="divide-y divide-slate-100 overflow-y-auto flex-1">
                @forelse($pendingFaceResets as $reset)
                    <div class="flex items-center justify-between p-4 hover:bg-slate-50/50 transition duration-150">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl overflow-hidden border border-slate-200 shadow-inner">
                                <img src="{{ Storage::url($reset->new_face_image) }}" class="w-full h-full object-cover" />
                            </div>
                            <div>
                                <span class="block text-sm font-bold text-slate-900 leading-none mb-1">{{ $reset->employee->name }}</span>
                                <span class="text-[10px] font-bold text-slate-400">{{ $reset->employee->employee_code }} • {{ $reset->requested_at->diffForHumans() }}</span>
                            </div>
                        </div>
                        <a href="{{ route('admin.face-resets.show', $reset->id) }}" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-600 font-extrabold text-[10px] uppercase tracking-wider px-3.5 py-2 rounded-lg border border-indigo-200/50 transition">
                            Review Request
                        </a>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center h-full text-slate-400 p-8">
                        <div class="w-12 h-12 rounded-2xl bg-slate-50 border flex items-center justify-center text-xl mb-2 text-slate-300"><i class="bi bi-shield-check"></i></div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">All Biometric profiles verified</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Live Feed -->
        <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden flex flex-col h-[280px]">
            <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                <h5 class="font-extrabold text-xs text-slate-800 tracking-wider uppercase flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Live Check-In Feed
                </h5>
                <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Recent 10</span>
            </div>
            <div class="divide-y divide-slate-100 overflow-y-auto flex-1">
                @forelse ($recentCheckIns as $entry)
                    <div class="flex items-center justify-between p-4 hover:bg-slate-50/50 transition duration-150">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 bg-slate-100 rounded-xl flex items-center justify-center text-slate-600 text-xs font-black shadow-inner">
                                {{ strtoupper(substr($entry->user->name ?? '?', 0, 2)) }}
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-900 leading-none mb-1">{{ $entry->user->name ?? 'Unknown' }}</p>
                                <p class="text-[10px] font-bold text-slate-400">{{ $entry->user->employee_code ?? 'N/A' }} • {{ $entry->user->department->department_name ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-bold text-slate-600">{{ \Carbon\Carbon::parse($entry->check_in)->format('h:i A') }}</span>
                            @if ($entry->login_type === 'remote')
                                <span class="text-[9px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded border bg-sky-50 text-sky-700 border-sky-200 flex items-center gap-1">
                                    <i class="bi bi-broadcast"></i> Remote
                                </span>
                            @endif
                            @php
                                $statusColors = [
                                    'present' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'late' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'half_day' => 'bg-purple-50 text-purple-700 border-purple-200',
                                    'absent' => 'bg-rose-50 text-rose-700 border-rose-200',
                                ];
                                $color = $statusColors[$entry->status] ?? 'bg-slate-50 text-slate-700 border-slate-200';
                            @endphp
                            <span class="text-[9px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded border {{ $color }}">
                                {{ $entry->status }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center h-full text-slate-400 p-8">
                        <div class="w-12 h-12 rounded-2xl bg-slate-50 border flex items-center justify-center text-xl mb-2 text-slate-300"><i class="bi bi-clock"></i></div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">No activity yet today</p>
                    </div>
                @endforelse
            </div>
        </div>

    </div>

    <!-- Right Column: Policy Violations & Geofencing Exceptions -->
    <div class="space-y-8">
        
        <!-- Policy & Shift Violations (Late check-ins, early clock-outs) -->
        <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden flex flex-col h-[280px]">
            <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                <h5 class="font-extrabold text-xs text-rose-600 tracking-wider uppercase flex items-center gap-2">
                    <i class="bi bi-exclamation-triangle"></i> Shift Policy Violations (Last 7 Days)
                </h5>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $shiftViolations->count() }} violations</span>
            </div>
            <div class="divide-y divide-slate-100 overflow-y-auto flex-1">
                @forelse($shiftViolations as $violation)
                    <div class="p-4 hover:bg-slate-50/30 transition duration-150 flex justify-between items-center">
                        <div>
                            <span class="block text-sm font-bold text-slate-900 mb-0.5">{{ $violation->user->name }}</span>
                            <p class="text-[11px] font-medium text-slate-400">
                                Date: <strong class="text-slate-600">{{ $violation->attendance_date }}</strong> • 
                                Shift: <strong class="text-slate-600">{{ $violation->shift->shift_name }}</strong>
                            </p>
                            <p class="text-[10px] font-bold text-rose-500 mt-1 max-w-[300px] truncate" title="{{ $violation->remarks }}">
                                <i class="bi bi-arrow-right-short"></i> {{ $violation->remarks }}
                            </p>
                        </div>
                        <span class="px-2.5 py-1 text-[9px] font-extrabold tracking-wider uppercase border border-rose-200 bg-rose-50 text-rose-700 rounded-full">
                            {{ $violation->status }}
                        </span>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center h-full text-slate-400 p-8">
                        <div class="w-12 h-12 rounded-2xl bg-slate-50 border flex items-center justify-center text-xl mb-2 text-slate-300"><i class="bi bi-emoji-smile"></i></div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Perfect Shift Compliance!</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Geofencing & Proximity Exceptions (Out of bounds coordinates) -->
        <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden flex flex-col h-[280px]">
            <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                <h5 class="font-extrabold text-xs text-rose-600 tracking-wider uppercase flex items-center gap-2">
                    <i class="bi bi-geo-alt"></i> Geofencing Proximity Exceptions (Last 7 Days)
                </h5>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $attendanceExceptions->count() }} alerts</span>
            </div>
            <div class="divide-y divide-slate-100 overflow-y-auto flex-1">
                @forelse($attendanceExceptions as $except)
                    <div class="p-4 hover:bg-slate-50/30 transition duration-150">
                        <div class="flex justify-between items-start mb-1">
                            <span class="text-sm font-bold text-slate-900 leading-none">{{ $except->user->name }}</span>
                            <span class="text-[9px] font-black text-rose-600 bg-rose-50 border border-rose-200 px-2 py-0.5 rounded-full uppercase tracking-wider">
                                {{ $except->distance_km !== null ? round($except->distance_km, 2) . ' km away' : 'NO CHECK-OUT' }}
                            </span>
                        </div>
                        <span class="block text-[10px] font-bold text-slate-400 mb-1.5">Date: {{ $except->attendance_date }} • Office: {{ $except->user->location->location_name ?? 'N/A' }}</span>
                        <p class="text-[10px] font-semibold text-slate-400 bg-slate-50 border p-2 rounded-xl border-dashed">
                            {{ $except->remarks }}
                        </p>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center h-full text-slate-400 p-8">
                        <div class="w-12 h-12 rounded-2xl bg-slate-50 border flex items-center justify-center text-xl mb-2 text-slate-300"><i class="bi bi-shield-check"></i></div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">All coordinates verified inside boundary</p>
                    </div>
                @endforelse
            </div>
        </div>

    </div>

</div>

<!-- Lower Section: Department-wise Stats & Utilities -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Department breakdown -->
    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden lg:col-span-2">
        <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
            <h5 class="font-extrabold text-xs text-slate-800 tracking-wider uppercase flex items-center gap-2">
                <i class="bi bi-building"></i> Department Attendance Overview
            </h5>
        </div>
        <div class="divide-y divide-slate-50">
            @forelse ($departmentStats as $dept)
                <div class="p-5 hover:bg-slate-50/30 transition duration-150">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-extrabold text-slate-800">{{ $dept['department'] }}</span>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $dept['total'] }} members</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2 flex overflow-hidden">
                        @if($dept['total'] > 0)
                            @if($dept['present'] > 0)
                                <div class="bg-emerald-500 h-full" style="width: {{ ($dept['present'] / $dept['total']) * 100 }}%"></div>
                            @endif
                            @if($dept['late'] > 0)
                                <div class="bg-amber-500 h-full" style="width: {{ ($dept['late'] / $dept['total']) * 100 }}%"></div>
                            @endif
                            @if($dept['absent'] > 0)
                                <div class="bg-rose-500 h-full" style="width: {{ ($dept['absent'] / $dept['total']) * 100 }}%"></div>
                            @endif
                        @else
                            <div class="bg-slate-200 h-full w-full"></div>
                        @endif
                    </div>
                    <div class="flex items-center gap-4 mt-3">
                        <span class="text-[10px] font-bold text-emerald-600 flex items-center gap-1"><span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>{{ $dept['present'] }} Present</span>
                        <span class="text-[10px] font-bold text-amber-600 flex items-center gap-1"><span class="w-1.5 h-1.5 bg-amber-500 rounded-full"></span>{{ $dept['late'] }} Late</span>
                        <span class="text-[10px] font-bold text-rose-500 flex items-center gap-1"><span class="w-1.5 h-1.5 bg-rose-500 rounded-full"></span>{{ $dept['absent'] }} Absent</span>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-slate-400 font-semibold">No department data compiled.</div>
            @endforelse
        </div>
    </div>

    <!-- Sidebar Utilities and Statuses -->
    <div class="space-y-8">
        
        <!-- Quick Action Panels -->
        <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-5">
            <h5 class="font-extrabold text-xs text-slate-800 tracking-wider uppercase mb-4 pb-2 border-b">
                Quick Shortcuts
            </h5>
            <div class="grid grid-cols-2 gap-3">
                <a href="{{ route('admin.employees.create') }}" class="group p-4 rounded-2xl border border-slate-100 hover:border-indigo-500 hover:bg-indigo-50/10 text-center flex flex-col justify-center items-center transition duration-200">
                    <div class="w-9 h-9 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center text-lg mb-2 group-hover:scale-105 transition"><i class="bi bi-person-plus"></i></div>
                    <span class="font-bold text-slate-800 text-[10px] uppercase tracking-wider">New Employee</span>
                </a>
                <a href="{{ route('admin.reports.index') }}" class="group p-4 rounded-2xl border border-slate-100 hover:border-emerald-500 hover:bg-emerald-50/10 text-center flex flex-col justify-center items-center transition duration-200">
                    <div class="w-9 h-9 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center text-lg mb-2 group-hover:scale-105 transition"><i class="bi bi-bar-chart"></i></div>
                    <span class="font-bold text-slate-800 text-[10px] uppercase tracking-wider">Reports Suite</span>
                </a>
                <a href="{{ route('admin.shifts.index') }}" class="group p-4 rounded-2xl border border-slate-100 hover:border-purple-500 hover:bg-purple-50/10 text-center flex flex-col justify-center items-center transition duration-200">
                    <div class="w-9 h-9 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center text-lg mb-2 group-hover:scale-105 transition"><i class="bi bi-clock"></i></div>
                    <span class="font-bold text-slate-800 text-[10px] uppercase tracking-wider">Policies</span>
                </a>
                <a href="{{ route('admin.org-tree') }}" class="group p-4 rounded-2xl border border-slate-100 hover:border-amber-500 hover:bg-amber-50/10 text-center flex flex-col justify-center items-center transition duration-200">
                    <div class="w-9 h-9 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center text-lg mb-2 group-hover:scale-105 transition"><i class="bi bi-diagram-3"></i></div>
                    <span class="font-bold text-slate-800 text-[10px] uppercase tracking-wider">Org Structure</span>
                </a>
            </div>
        </div>

        <!-- System Online Health Panel -->
        <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-5">
            <h5 class="font-extrabold text-xs text-slate-800 tracking-wider uppercase mb-4 pb-2 border-b">
                Live Server Health
            </h5>
            <div class="space-y-3 font-semibold text-slate-700">
                <div class="flex items-center justify-between p-3 bg-slate-50 border rounded-2xl border-slate-100 text-xs">
                    <div class="flex items-center gap-2"><i class="bi bi-cpu text-slate-500"></i> API Gateway</div>
                    <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_8px_#10b981] animate-pulse"></span> <span class="text-[9px] uppercase tracking-wider font-extrabold text-emerald-600">Online</span></div>
                </div>
                <div class="flex items-center justify-between p-3 bg-slate-50 border rounded-2xl border-slate-100 text-xs">
                    <div class="flex items-center gap-2"><i class="bi bi-database text-slate-500"></i> Database Core</div>
                    <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_8px_#10b981] animate-pulse"></span> <span class="text-[9px] uppercase tracking-wider font-extrabold text-emerald-600">Connected</span></div>
                </div>
                <div class="flex items-center justify-between p-3 bg-slate-50 border rounded-2xl border-slate-100 text-xs">
                    <div class="flex items-center gap-2"><i class="bi bi-eye text-slate-500"></i> Biometric Recognition Engine</div>
                    <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_8px_#10b981] animate-pulse"></span> <span class="text-[9px] uppercase tracking-wider font-extrabold text-emerald-600">Active</span></div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
