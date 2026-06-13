@extends('layouts.admin')

@section('title', 'Time & Attendance Board')

@section('content')
<div class="space-y-8 animate-fade-in">
    <!-- Header with Dynamic Tab Switcher -->
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200/60 pb-5">
        <div class="flex flex-wrap gap-2 bg-slate-900/5 p-1 rounded-2xl border border-slate-200/20">
            <a href="{{ route('admin.attendance.board', ['mode' => 'daily', 'date' => $targetDate->toDateString(), 'department_id' => $departmentId, 'location_id' => $locationId]) }}" 
               class="px-5 py-2.5 rounded-xl text-xs font-bold transition-all duration-300 flex items-center gap-2 {{ $mode === 'daily' ? 'bg-slate-950 text-white shadow-md' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-900/5' }}">
                <i class="bi bi-clock-fill text-sm"></i>
                Daily Board
            </a>
            <a href="{{ route('admin.attendance.board', ['mode' => 'weekly', 'date' => $targetDate->toDateString(), 'department_id' => $departmentId, 'location_id' => $locationId]) }}" 
               class="px-5 py-2.5 rounded-xl text-xs font-bold transition-all duration-300 flex items-center gap-2 {{ $mode === 'weekly' ? 'bg-slate-950 text-white shadow-md' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-900/5' }}">
                <i class="bi bi-calendar-range-fill text-sm"></i>
                Weekly Grid
            </a>
            <a href="{{ route('admin.attendance.board', ['mode' => 'monthly', 'date' => $targetDate->toDateString(), 'department_id' => $departmentId, 'location_id' => $locationId]) }}" 
               class="px-5 py-2.5 rounded-xl text-xs font-bold transition-all duration-300 flex items-center gap-2 {{ $mode === 'monthly' ? 'bg-slate-950 text-white shadow-md' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-900/5' }}">
                <i class="bi bi-calendar-month-fill text-sm"></i>
                Monthly Grid
            </a>
        </div>
        
        <div class="flex items-center gap-2 text-xs font-bold text-slate-500 uppercase tracking-widest bg-indigo-50 border border-indigo-100/50 px-4 py-2.5 rounded-2xl">
            <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
            Mode: {{ ucfirst($mode) }} Board Overview
        </div>
    </div>

    <!-- Central Control Filter Card -->
    <div class="bg-white/80 backdrop-blur-md border border-slate-200/60 rounded-3xl p-6 shadow-sm shadow-slate-100/50">
        <form action="{{ route('admin.attendance.board') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
            <input type="hidden" name="mode" value="{{ $mode }}">
            
            <!-- Date Filter -->
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                    @if($mode === 'daily') Select Date @elseif($mode === 'weekly') Select Week Date @else Select Month Date @endif
                </label>
                <input type="date" name="date" value="{{ $targetDate->toDateString() }}" 
                       class="w-full bg-slate-50 border border-slate-200/80 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-200">
            </div>

            <!-- Department Filter -->
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Department</label>
                <select name="department_id" class="w-full bg-slate-50 border border-slate-200/80 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-200">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ $departmentId == $dept->id ? 'selected' : '' }}>{{ $dept->department_name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Location Filter -->
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Office Location</label>
                <select name="location_id" class="w-full bg-slate-50 border border-slate-200/80 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-200">
                    <option value="">All Locations</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" {{ $locationId == $loc->id ? 'selected' : '' }}>{{ $loc->location_name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Action buttons -->
            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-wider py-3.5 rounded-xl transition-all duration-200 shadow-lg shadow-indigo-600/10 flex items-center justify-center gap-2">
                    <i class="bi bi-filter"></i> Apply Filters
                </button>
                <a href="{{ route('admin.attendance.board', ['mode' => $mode]) }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs uppercase tracking-wider px-4 py-3.5 rounded-xl transition-all duration-200 flex items-center justify-center">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Mode 1: DAILY BOARD VIEW -->
    @if($mode === 'daily')
        <!-- KPIs Row -->
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-4">
            <div class="bg-white border border-slate-200/60 rounded-2xl p-4 shadow-sm flex items-center gap-3">
                <div class="w-9 h-9 bg-slate-50 text-slate-600 rounded-xl flex items-center justify-center text-sm border shadow-inner"><i class="bi bi-people-fill"></i></div>
                <div>
                    <span class="text-xl font-black text-slate-900 block leading-tight">{{ $kpis['total'] }}</span>
                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wide">Total Staff</span>
                </div>
            </div>
            <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-4 flex items-center gap-3">
                <div class="w-9 h-9 bg-emerald-500 text-white rounded-xl flex items-center justify-center text-sm shadow-md shadow-emerald-500/20"><i class="bi bi-check-lg"></i></div>
                <div>
                    <span class="text-xl font-black text-emerald-600 block leading-tight">{{ $kpis['present'] + $kpis['late'] + $kpis['half_day'] }}</span>
                    <span class="text-[9px] font-bold text-emerald-700 uppercase tracking-wide">Clocked In</span>
                </div>
            </div>
            <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-4 flex items-center gap-3">
                <div class="w-9 h-9 bg-indigo-500 text-white rounded-xl flex items-center justify-center text-sm shadow-md shadow-indigo-500/20"><i class="bi bi-hourglass-split"></i></div>
                <div>
                    <span class="text-xl font-black text-indigo-600 block leading-tight">{{ $kpis['still_working'] }}</span>
                    <span class="text-[9px] font-bold text-indigo-700 uppercase tracking-wide">Still Working</span>
                </div>
            </div>
            <div class="bg-amber-50 border border-amber-100 rounded-2xl p-4 flex items-center gap-3">
                <div class="w-9 h-9 bg-amber-500 text-white rounded-xl flex items-center justify-center text-sm shadow-md shadow-amber-500/20"><i class="bi bi-alarm"></i></div>
                <div>
                    <span class="text-xl font-black text-amber-600 block leading-tight">{{ $kpis['late'] }}</span>
                    <span class="text-[9px] font-bold text-amber-700 uppercase tracking-wide">Late Marks</span>
                </div>
            </div>
            <div class="bg-rose-50 border border-rose-100 rounded-2xl p-4 flex items-center gap-3">
                <div class="w-9 h-9 bg-rose-500 text-white rounded-xl flex items-center justify-center text-sm shadow-md shadow-rose-500/20"><i class="bi bi-person-x"></i></div>
                <div>
                    <span class="text-xl font-black text-rose-600 block leading-tight">{{ $kpis['absent'] }}</span>
                    <span class="text-[9px] font-bold text-rose-700 uppercase tracking-wide">Absent Today</span>
                </div>
            </div>
            <div class="bg-slate-50 border border-slate-200/80 rounded-2xl p-4 flex items-center gap-3 sm:col-span-2 lg:col-span-1">
                <div class="w-9 h-9 bg-slate-200 text-slate-600 rounded-xl flex items-center justify-center text-sm shadow-inner"><i class="bi bi-shield-slash"></i></div>
                <div>
                    <span class="text-xl font-black text-slate-600 block leading-tight">{{ $kpis['weekly_off'] + $kpis['leave'] + $kpis['holiday'] }}</span>
                    <span class="text-[9px] font-bold text-slate-500 uppercase tracking-wide">Weekly Off/Leaves</span>
                </div>
            </div>
        </div>

        <!-- Daily Logs Table Grid -->
        <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/30">
                <h3 class="font-extrabold text-sm text-slate-800 uppercase tracking-wider flex items-center gap-2">
                    <i class="bi bi-collection-play-fill text-indigo-500"></i> Daily Shift Visualizer ({{ $targetDate->format('d F Y, l') }})
                </h3>
                <span class="text-xs font-bold text-slate-400 uppercase bg-white border px-3 py-1.5 rounded-full">{{ count($boardData) }} staff listed</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 border-b border-slate-100 text-xs font-bold text-slate-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Employee Details</th>
                            <th class="px-6 py-4">Assigned Shift</th>
                            <th class="px-6 py-4">Clock In</th>
                            <th class="px-6 py-4">Clock Out</th>
                            <th class="px-6 py-4 text-center">Hours</th>
                            <th class="px-6 py-4">Proximity / Geofence</th>
                            <th class="px-6 py-4">Method</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Remarks / Activity</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                        @forelse($boardData as $row)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 bg-slate-100 border border-slate-200/60 rounded-xl flex items-center justify-center text-xs font-black shadow-inner text-slate-600">
                                            {{ strtoupper(substr($row['employee']->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <span class="block text-sm font-bold text-slate-900 leading-tight mb-0.5">{{ $row['employee']->name }}</span>
                                            <span class="text-[10px] font-bold text-slate-400">{{ $row['employee']->employee_code }} • {{ $row['employee']->department->department_name ?? 'Unassigned' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="block text-xs font-bold text-slate-800">{{ $row['shift_name'] }}</span>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide"><i class="bi bi-clock"></i> {{ $row['shift_timings'] }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    @if($row['check_in'] !== '—')
                                        <span class="text-slate-800 text-xs font-bold"><i class="bi bi-box-arrow-in-right text-emerald-500"></i> {{ $row['check_in'] }}</span>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($row['check_out'] !== '—')
                                        <span class="text-slate-800 text-xs font-bold"><i class="bi bi-box-arrow-out-left text-rose-500"></i> {{ $row['check_out'] }}</span>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($row['working_hours'] > 0)
                                        <span class="text-xs font-extrabold bg-slate-100 border border-slate-200/60 px-2.5 py-1 rounded-lg text-slate-700 shadow-sm">{{ number_format($row['working_hours'], 2) }}h</span>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($row['gps_distance'] !== null)
                                        @php
                                            $outOfBounds = $row['gps_distance'] > 0.2;
                                            $distanceInMeters = round($row['gps_distance'] * 1000, 1);
                                        @endphp
                                        @if($outOfBounds)
                                            <span class="text-[10px] font-black tracking-wider uppercase border border-rose-200 bg-rose-50 text-rose-700 px-2.5 py-1 rounded-full flex items-center gap-1.5 w-max">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-ping"></span>
                                                Out Bounds ({{ $distanceInMeters }}m)
                                            </span>
                                        @else
                                            <span class="text-[10px] font-black tracking-wider uppercase border border-emerald-200 bg-emerald-50 text-emerald-700 px-2.5 py-1 rounded-full flex items-center gap-1.5 w-max">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                In-Bounds ({{ $distanceInMeters }}m)
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-slate-300">N/A</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($row['method_used'])
                                        <span class="text-[10px] font-extrabold uppercase tracking-wider bg-slate-100 text-slate-600 px-2 py-1 rounded border border-slate-200">
                                            {{ str_replace('_', ' ', $row['method_used']) }}
                                        </span>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $statusColors = [
                                            'present' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'late' => 'bg-amber-50 text-amber-700 border-amber-200',
                                            'half_day' => 'bg-purple-50 text-purple-700 border-purple-200',
                                            'absent' => 'bg-rose-50 text-rose-700 border-rose-200',
                                            'weekly_off' => 'bg-slate-100 text-slate-600 border-slate-200',
                                            'leave' => 'bg-sky-50 text-sky-700 border-sky-200',
                                            'holiday' => 'bg-teal-50 text-teal-700 border-teal-200',
                                            'pending' => 'bg-slate-50 text-slate-400 border-slate-200 border-dashed',
                                        ];
                                        $color = $statusColors[$row['status']] ?? 'bg-slate-50 text-slate-700 border-slate-200';
                                    @endphp
                                    <span class="text-[9px] font-extrabold uppercase tracking-wider px-2.5 py-1 rounded border {{ $color }}">
                                        {{ $row['status'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-xs font-semibold text-slate-400 truncate block max-w-[220px]" title="{{ $row['remarks'] }}">
                                        {{ $row['remarks'] ?? '—' }}
                                    </span>
                                </td>
                                {{-- Actions Column --}}
                                <td class="px-6 py-4 text-right">
                                    <button type="button"
                                            onclick="openManualEditModal(
                                                '{{ $row['employee']->id }}',
                                                '{{ addslashes($row['employee']->name) }}',
                                                '{{ $targetDate->toDateString() }}',
                                                '{{ $row['status'] }}',
                                                '{{ $row['raw_check_in'] ?? '' }}',
                                                '{{ $row['raw_check_out'] ?? '' }}',
                                                '{{ addslashes($row['remarks'] ?? '') }}'
                                            )"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-[10px] uppercase tracking-wider rounded-lg border border-indigo-200/60 transition-all duration-200 shadow-sm hover:shadow-md group">
                                        <i class="bi bi-pencil-square text-xs group-hover:scale-110 transition-transform"></i>
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-slate-400 font-bold uppercase tracking-wider">
                                    No staff matched selected department or location filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Mode 2 & 3: WEEKLY & MONTHLY CROSS-TAB GRID VIEWS -->
    @if($mode === 'weekly' || $mode === 'monthly')
        <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/30">
                <h3 class="font-extrabold text-sm text-slate-800 uppercase tracking-wider flex items-center gap-2">
                    <i class="bi bi-grid-3x3-gap-fill text-indigo-500"></i>
                    @if($mode === 'weekly') 
                        Weekly Status Grid ({{ $startOfWeek->format('d M') }} to {{ $endOfWeek->format('d M Y') }})
                    @else
                        Monthly Cumulative Calendar Board ({{ $targetDate->format('F Y') }})
                    @endif
                </h3>
                
                <!-- Indicators Legend -->
                <div class="flex flex-wrap gap-2 text-[9px] font-extrabold uppercase tracking-wider">
                    <span class="px-2 py-0.5 bg-emerald-500 text-white rounded">P: Present</span>
                    <span class="px-2 py-0.5 bg-amber-500 text-white rounded">L: Late</span>
                    <span class="px-2 py-0.5 bg-purple-500 text-white rounded">H: Half-Day</span>
                    <span class="px-2 py-0.5 bg-rose-500 text-white rounded">A: Absent</span>
                    <span class="px-2 py-0.5 bg-sky-500 text-white rounded">LV: Leave</span>
                    <span class="px-2 py-0.5 bg-teal-500 text-white rounded">HD: Holiday</span>
                    <span class="px-2 py-0.5 bg-slate-100 text-slate-500 border rounded">OFF: Off</span>
                </div>
            </div>
            
            <div class="overflow-x-auto overflow-y-hidden max-w-full">
                <table class="w-full text-left border-collapse whitespace-nowrap">
                    <thead class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="px-5 py-4 sticky left-0 bg-slate-50 border-r border-slate-200/60 z-10 shadow-[2px_0_5px_rgba(0,0,0,0.02)] min-w-[200px]">Employee details</th>
                            @foreach($dates as $date)
                                <th class="px-3 py-4 text-center min-w-[50px] {{ $date->isToday() ? 'bg-indigo-50 text-indigo-700' : '' }}">
                                    <span class="block leading-none mb-1 font-extrabold">{{ $date->format('D') }}</span>
                                    <span class="block text-xs font-black">{{ $date->format('d') }}</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-semibold text-slate-700 text-sm">
                        @forelse($gridData as $row)
                            <tr class="hover:bg-slate-50/30 transition">
                                <!-- Employee Sticky Cell -->
                                <td class="px-5 py-3 sticky left-0 bg-white group-hover:bg-slate-50 border-r border-slate-200/60 z-10 shadow-[2px_0_5px_rgba(0,0,0,0.01)]">
                                    <span class="block text-xs font-bold text-slate-900 leading-tight mb-0.5">{{ $row['employee']->name }}</span>
                                    <span class="text-[9px] font-bold text-slate-400 block">{{ $row['employee']->employee_code }} • {{ $row['employee']->department->department_name ?? 'Unassigned' }}</span>
                                </td>
                                
                                <!-- Daily status cells with native HTML tooltips -->
                                @foreach($dates as $dateObj)
                                    @php
                                        $dayData = $row['days'][$dateObj->toDateString()];
                                        $status = $dayData['status'];
                                        $details = $dayData['details'];

                                        $cellClass = 'text-slate-300 font-bold text-[9px]';
                                        $badgeText = '•';
                                        
                                        if ($status === 'present') {
                                            $cellClass = 'bg-emerald-500 text-white rounded-full w-7 h-7 flex items-center justify-center text-[10px] font-black mx-auto shadow-sm shadow-emerald-500/20';
                                            $badgeText = 'P';
                                        } elseif ($status === 'late') {
                                            $cellClass = 'bg-amber-500 text-white rounded-full w-7 h-7 flex items-center justify-center text-[10px] font-black mx-auto shadow-sm shadow-amber-500/20';
                                            $badgeText = 'L';
                                        } elseif ($status === 'half_day') {
                                            $cellClass = 'bg-purple-500 text-white rounded-full w-7 h-7 flex items-center justify-center text-[10px] font-black mx-auto shadow-sm shadow-purple-500/20';
                                            $badgeText = 'H';
                                        } elseif ($status === 'absent') {
                                            $cellClass = 'bg-rose-500 text-white rounded-full w-7 h-7 flex items-center justify-center text-[10px] font-black mx-auto shadow-sm shadow-rose-500/20';
                                            $badgeText = 'A';
                                        } elseif ($status === 'leave') {
                                            $cellClass = 'bg-sky-500 text-white rounded w-8 h-6 flex items-center justify-center text-[9px] font-extrabold mx-auto';
                                            $badgeText = 'LV';
                                        } elseif ($status === 'holiday') {
                                            $cellClass = 'bg-teal-500 text-white rounded w-8 h-6 flex items-center justify-center text-[9px] font-extrabold mx-auto';
                                            $badgeText = 'HD';
                                        } elseif ($status === 'weekly_off') {
                                            $cellClass = 'bg-slate-100 text-slate-400 border rounded w-8 h-6 flex items-center justify-center text-[8px] font-black mx-auto';
                                            $badgeText = 'OFF';
                                        }
                                    @endphp
                                    <td class="px-2 py-3 text-center transition duration-150 {{ $dateObj->isToday() ? 'bg-indigo-50/30' : '' }}" title="{{ $details }}">
                                        <div class="flex items-center justify-center">
                                            <span class="{{ $cellClass }}">
                                                {{ $badgeText }}
                                            </span>
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($dates) + 1 }}" class="px-5 py-12 text-center text-slate-400 font-bold uppercase tracking-wider">
                                    No staff matched selected department or location filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

{{-- ========================= MANUAL ATTENDANCE UPDATE MODAL ========================= --}}
<div id="manual-edit-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
    <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl border border-slate-200/60 overflow-hidden">
        <!-- Modal Header -->
        <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-slate-900 to-slate-800">
            <h3 class="font-extrabold text-white text-base flex items-center gap-2">
                <i class="bi bi-pencil-square"></i> Manual Attendance Correction
            </h3>
            <button onclick="document.getElementById('manual-edit-modal').classList.add('hidden')"
                    class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition">
                <i class="bi bi-x-lg text-sm"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <form action="{{ route('admin.attendance.manual-update') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="employee_id" id="edit_emp_id">
            <input type="hidden" name="attendance_date" id="edit_att_date">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Employee</label>
                    <input type="text" id="edit_emp_name" readonly
                           class="w-full bg-slate-100 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-500 focus:outline-none cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Target Date</label>
                    <input type="text" id="edit_att_date_display" readonly
                           class="w-full bg-slate-100 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-500 focus:outline-none cursor-not-allowed">
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Attendance Status <span class="text-rose-500">*</span></label>
                <select name="status" id="edit_status" required
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                    <option value="present">Present</option>
                    <option value="late">Late</option>
                    <option value="half_day">Half-Day</option>
                    <option value="absent">Absent</option>
                    <option value="weekly_off">Weekly Off</option>
                    <option value="leave">On Leave</option>
                    <option value="holiday">Public Holiday</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">
                        <i class="bi bi-box-arrow-in-right text-emerald-500"></i> Check-in Time
                    </label>
                    <input type="time" name="check_in" id="edit_check_in"
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">
                        <i class="bi bi-box-arrow-left text-rose-500"></i> Check-out Time
                    </label>
                    <input type="time" name="check_out" id="edit_check_out"
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Remarks / Reason <span class="text-rose-500">*</span></label>
                <textarea name="remarks" id="edit_remarks" required rows="2"
                          placeholder="e.g. Forgot to clock out, correction of late check-in, manual duty entry..."
                          class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all resize-none"></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-2 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('manual-edit-modal').classList.add('hidden')"
                        class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs uppercase tracking-wider rounded-xl transition-colors">
                    Cancel
                </button>
                <button type="submit"
                        class="px-6 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition-colors shadow-sm flex items-center gap-2">
                    <i class="bi bi-floppy-fill"></i> Save Entry
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openManualEditModal(empId, empName, dateStr, status, checkIn, checkOut, remarks) {
        document.getElementById('edit_emp_id').value = empId;
        document.getElementById('edit_emp_name').value = empName;
        document.getElementById('edit_att_date').value = dateStr;
        document.getElementById('edit_att_date_display').value = dateStr;
        
        // Match status select values
        document.getElementById('edit_status').value = status;
        
        document.getElementById('edit_check_in').value = checkIn || '';
        document.getElementById('edit_check_out').value = checkOut || '';
        document.getElementById('edit_remarks').value = remarks || '';
        
        document.getElementById('manual-edit-modal').classList.remove('hidden');
    }

    // Close modal on backdrop click
    document.getElementById('manual-edit-modal').addEventListener('click', function(e) {
        if (e.target === this) this.classList.add('hidden');
    });
</script>
@endsection
