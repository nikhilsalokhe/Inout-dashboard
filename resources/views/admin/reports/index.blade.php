@extends('layouts.admin')
 
@section('title', 'Time & Attendance Board')

@section('content')
<!-- Include CSS / CDN resources inside layout context safely -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="space-y-8 animate-fade-in">
    
    <!-- Central Control Filter Card -->
    <div class="bg-white/80 backdrop-blur-md border border-slate-200/60 rounded-3xl p-6 shadow-sm shadow-slate-100/50">
        <form action="{{ route('admin.reports.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
            <!-- Preset View Filters -->
            <div class="lg:col-span-5 flex gap-2">
                <button type="button" onclick="setPreset('daily')" class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition">Daily View</button>
                <button type="button" onclick="setPreset('weekly')" class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition">Weekly View</button>
                <button type="button" onclick="setPreset('monthly')" class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition">Monthly View</button>
            </div>
            <!-- Date Range -->
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Start Date</label>
                <div class="relative">
                    <input type="date" name="start_date" value="{{ $startDate }}" class="w-full bg-slate-50 border border-slate-200/80 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-200">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">End Date</label>
                <div class="relative">
                    <input type="date" name="end_date" value="{{ $endDate }}" class="w-full bg-slate-50 border border-slate-200/80 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-200">
                </div>
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

            <!-- Employee Select -->
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Employee</label>
                <select name="user_id" class="w-full bg-slate-50 border border-slate-200/80 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-200">
                    <option value="">All Employees</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ $employeeId == $emp->id ? 'selected' : '' }}>{{ $emp->name }} ({{ $emp->employee_code }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Action buttons -->
            <div class="lg:col-span-5 flex flex-wrap gap-3 justify-between items-center pt-2 border-t border-slate-100">
                <div class="flex gap-2">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-wider px-5 py-3 rounded-xl transition-all duration-200 shadow-lg shadow-indigo-600/10 flex items-center gap-2">
                        <i class="bi bi-filter"></i> Apply Filters
                    </button>
                    <a href="{{ route('admin.reports.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs uppercase tracking-wider px-5 py-3 rounded-xl transition-all duration-200 flex items-center gap-2">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </a>
                </div>

                <!-- Export Utilities Dropdown/Button array -->
                <div class="flex gap-2" id="export-actions">
                    <a href="#" onclick="triggerExport('excel')" class="bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 text-emerald-700 font-bold text-xs uppercase tracking-wider px-4 py-3 rounded-xl transition-all duration-200 flex items-center gap-2">
                        <i class="bi bi-file-earmark-excel-fill text-emerald-500"></i> Export Excel
                    </a>
                    <a href="#" onclick="triggerExport('csv')" class="bg-sky-50 border border-sky-200 hover:bg-sky-100 text-sky-700 font-bold text-xs uppercase tracking-wider px-4 py-3 rounded-xl transition-all duration-200 flex items-center gap-2">
                        <i class="bi bi-file-earmark-spreadsheet-fill text-sky-500"></i> Export CSV
                    </a>
                    <a href="#" onclick="triggerExport('pdf')" class="bg-rose-50 border border-rose-200 hover:bg-rose-100 text-rose-700 font-bold text-xs uppercase tracking-wider px-4 py-3 rounded-xl transition-all duration-200 flex items-center gap-2">
                        <i class="bi bi-file-earmark-pdf-fill text-rose-500"></i> Browser Print (PDF)
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Modern Glass Tab Navigation Container -->
    <div class="border-b border-slate-200/60 flex flex-wrap gap-2">
        <button onclick="switchTab('analytics-tab')" id="tab-analytics-tab" class="tab-button active-tab px-5 py-3.5 text-sm font-bold border-b-2 border-indigo-600 text-indigo-600 flex items-center gap-2 transition-all duration-200">
            <i class="bi bi-pie-chart-fill"></i> Charts & Analytics
        </button>
        <button onclick="switchTab('calendar-tab')" id="tab-calendar-tab" class="tab-button px-5 py-3.5 text-sm font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300 flex items-center gap-2 transition-all duration-200">
            <i class="bi bi-calendar-event-fill"></i> Interactive Calendar
        </button>
        <button onclick="switchTab('logs-tab')" id="tab-logs-tab" class="tab-button px-5 py-3.5 text-sm font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300 flex items-center gap-2 transition-all duration-200">
            <i class="bi bi-file-text-fill"></i> Employee Logs
        </button>
        <button onclick="switchTab('summary-tab')" id="tab-summary-tab" class="tab-button px-5 py-3.5 text-sm font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300 flex items-center gap-2 transition-all duration-200">
            <i class="bi bi-calculator-fill"></i> Monthly Summary
        </button>
        <button onclick="switchTab('shifts-tab')" id="tab-shifts-tab" class="tab-button px-5 py-3.5 text-sm font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300 flex items-center gap-2 transition-all duration-200">
            <i class="bi bi-clock-fill"></i> Shift Policies
        </button>
        <button onclick="switchTab('late-tab')" id="tab-late-tab" class="tab-button px-5 py-3.5 text-sm font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300 flex items-center gap-2 transition-all duration-200">
            <i class="bi bi-exclamation-triangle-fill"></i> Late Marks
        </button>
        <button onclick="switchTab('hours-tab')" id="tab-hours-tab" class="tab-button px-5 py-3.5 text-sm font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300 flex items-center gap-2 transition-all duration-200">
            <i class="bi bi-hourglass-split"></i> Working Hours
        </button>
        <button onclick="switchTab('face-tab')" id="tab-face-tab" class="tab-button px-5 py-3.5 text-sm font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300 flex items-center gap-2 transition-all duration-200">
            <i class="bi bi-person-bounding-box"></i> Biometric Audits
        </button>
        <button onclick="switchTab('gps-tab')" id="tab-gps-tab" class="tab-button px-5 py-3.5 text-sm font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300 flex items-center gap-2 transition-all duration-200">
            <i class="bi bi-geo-alt-fill"></i> Location/GPS Geofencing
        </button>
    </div>

    <!-- TABS VIEWPORT CANVAS -->
    <div id="tabs-viewport" class="space-y-6">

        <!-- 1. CHARTS & ANALYTICS TAB -->
        <div id="analytics-tab" class="tab-content grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Total Stats Cards -->
            <div class="md:col-span-2 grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-5 flex items-center gap-4">
                    <div class="w-12 h-12 bg-emerald-500 text-white rounded-xl flex items-center justify-center text-xl shadow-md shadow-emerald-500/20"><i class="bi bi-check-circle-fill"></i></div>
                    <div>
                        <span class="text-2xl font-black text-slate-900 block">{{ $analytics['present_count'] }}</span>
                        <span class="text-xs font-bold text-emerald-700 uppercase tracking-wide">Present Logs</span>
                    </div>
                </div>
                <div class="bg-amber-50 border border-amber-100 rounded-2xl p-5 flex items-center gap-4">
                    <div class="w-12 h-12 bg-amber-500 text-white rounded-xl flex items-center justify-center text-xl shadow-md shadow-amber-500/20"><i class="bi bi-alarm-fill"></i></div>
                    <div>
                        <span class="text-2xl font-black text-slate-900 block">{{ $analytics['late_count'] }}</span>
                        <span class="text-xs font-bold text-amber-700 uppercase tracking-wide">Late Check-ins</span>
                    </div>
                </div>
                <div class="bg-purple-50 border border-purple-100 rounded-2xl p-5 flex items-center gap-4">
                    <div class="w-12 h-12 bg-purple-500 text-white rounded-xl flex items-center justify-center text-xl shadow-md shadow-purple-500/20"><i class="bi bi-clock-history"></i></div>
                    <div>
                        <span class="text-2xl font-black text-slate-900 block">{{ $analytics['half_day_count'] }}</span>
                        <span class="text-xs font-bold text-purple-700 uppercase tracking-wide">Half Days</span>
                    </div>
                </div>
                <div class="bg-rose-50 border border-rose-100 rounded-2xl p-5 flex items-center gap-4">
                    <div class="w-12 h-12 bg-rose-500 text-white rounded-xl flex items-center justify-center text-xl shadow-md shadow-rose-500/20"><i class="bi bi-x-circle-fill"></i></div>
                    <div>
                        <span class="text-2xl font-black text-slate-900 block">{{ $analytics['absent_count'] }}</span>
                        <span class="text-xs font-bold text-rose-700 uppercase tracking-wide">Absent Days</span>
                    </div>
                </div>
            </div>

            <!-- Distribution Chart -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/60 shadow-sm flex flex-col items-center">
                <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-6 self-start flex items-center gap-2"><i class="bi bi-pie-chart text-indigo-500"></i> Attendance Status Distribution</h3>
                <div class="w-full max-w-[280px] aspect-square">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            <!-- Performance Trend Chart -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/60 shadow-sm">
                <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-6 flex items-center gap-2"><i class="bi bi-activity text-indigo-500"></i> Weekly Breakdown trend</h3>
                <div class="h-[280px]">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>

        <!-- 2. INTERACTIVE CALENDAR TAB -->
        <div id="calendar-tab" class="tab-content hidden space-y-4">
            <div class="bg-white rounded-3xl p-6 border border-slate-200/60 shadow-sm">
                <div id="full-calendar" class="w-full min-h-[600px] text-slate-700"></div>
            </div>
        </div>

        <!-- 3. EMPLOYEE LOGS TAB -->
        <div id="logs-tab" class="tab-content hidden bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center flex-wrap gap-4">
                <h3 class="font-extrabold text-slate-900 flex items-center gap-2"><i class="bi bi-file-text text-indigo-500"></i> Employee Daily Attendance Logs</h3>
                <span class="text-xs font-bold text-slate-400 uppercase bg-slate-50 border px-3 py-1.5 rounded-full">{{ $attendances->count() }} records fetched</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 border-b border-slate-100 text-xs font-bold text-slate-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Date</th>
                            <th class="px-6 py-4">Employee</th>
                            <th class="px-6 py-4">Location</th>
                            <th class="px-6 py-4">Shift Details</th>
                            <th class="px-6 py-4">Check In</th>
                            <th class="px-6 py-4">Check Out</th>
                            <th class="px-6 py-4">Working Hours</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                        @forelse($attendances as $row)
                            <tr class="hover:bg-slate-50/50 transition duration-150">
                                <td class="px-6 py-4 text-slate-900 font-bold">{{ $row->attendance_date }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 text-xs font-bold">{{ substr($row->user->name ?? '?', 0, 2) }}</div>
                                        <div>
                                            <span class="block text-slate-900 text-xs font-extrabold leading-none mb-1">{{ $row->user->name ?? 'N/A' }}</span>
                                            <span class="text-[10px] font-bold text-slate-400 tracking-wider">{{ $row->user->employee_code ?? 'N/A' }} • {{ $row->user->department->department_name ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-xs">
                                    {{ $row->location ?? 'N/A' }}
                                    @if ($row->login_type === 'remote')
                                        <span class="block mt-1 text-[9px] font-extrabold uppercase tracking-wider px-1.5 py-0.5 rounded border bg-sky-50 text-sky-700 border-sky-200 w-max flex items-center gap-1">
                                            <i class="bi bi-broadcast text-sky-500"></i> Remote
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 text-[10px] font-extrabold tracking-wider uppercase rounded-full bg-slate-100 text-slate-600 border border-slate-200">{{ $row->shift->shift_name ?? 'Default Policy' }}</span>
                                </td>
                                <td class="px-6 py-4 text-xs text-indigo-600">{{ $row->check_in ? \Carbon\Carbon::parse($row->check_in)->format('h:i A') : 'N/A' }}</td>
                                <td class="px-6 py-4 text-xs text-indigo-600">{{ $row->check_out ? \Carbon\Carbon::parse($row->check_out)->format('h:i A') : 'N/A' }}</td>
                                <td class="px-6 py-4 text-slate-900 font-bold">{{ $row->working_hours ? $row->working_hours . ' hrs' : '0.00' }}</td>
                                <td class="px-6 py-4">
                                    @php
                                        $badge = 'bg-emerald-50 border-emerald-200 text-emerald-700';
                                        if($row->status === 'late') $badge = 'bg-amber-50 border-amber-200 text-amber-700';
                                        elseif($row->status === 'half_day') $badge = 'bg-purple-50 border-purple-200 text-purple-700';
                                        elseif($row->status === 'absent') $badge = 'bg-rose-50 border-rose-200 text-rose-700';
                                        elseif($row->status === 'weekly_off') $badge = 'bg-slate-50 border-slate-200 text-slate-500';
                                    @endphp
                                    <span class="px-2.5 py-1 border text-[10px] font-extrabold tracking-wider uppercase rounded-full {{ $badge }}">{{ $row->status }}</span>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-400 max-w-[200px] truncate" title="{{ $row->remarks }}">{{ $row->remarks ?? 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-slate-400 font-semibold">No records found for this date range/filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 4. MONTHLY SUMMARY TAB -->
        <div id="summary-tab" class="tab-content hidden bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100">
                <h3 class="font-extrabold text-slate-900 flex items-center gap-2"><i class="bi bi-calculator text-indigo-500"></i> Monthly Cumulative Aggregated Totals</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 border-b border-slate-100 text-xs font-bold text-slate-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Emp Code</th>
                            <th class="px-6 py-4">Employee Name</th>
                            <th class="px-6 py-4">Department</th>
                            <th class="px-6 py-4 text-emerald-600">Present Days</th>
                            <th class="px-6 py-4 text-amber-600">Late Marks</th>
                            <th class="px-6 py-4 text-purple-600">Half Days</th>
                            <th class="px-6 py-4 text-rose-600">Absent Days</th>
                            <th class="px-6 py-4 text-slate-500">Weekly Offs</th>
                            <th class="px-6 py-4 text-indigo-500">Paid Leaves</th>
                            <th class="px-6 py-4 text-rose-400">Unpaid Leaves</th>
                            <th class="px-6 py-4 text-emerald-700">Paid Days</th>
                            <th class="px-6 py-4">Hours Worked</th>
                            <th class="px-6 py-4 text-indigo-600">Overtime</th>
                            <th class="px-6 py-4 text-rose-500">Under-time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                        @forelse($monthlySummary as $row)
                            <tr class="hover:bg-slate-50/50 transition duration-150">
                                <td class="px-6 py-4 font-bold text-slate-900">{{ $row['employee_code'] }}</td>
                                <td class="px-6 py-4 text-slate-900">{{ $row['name'] }}</td>
                                <td class="px-6 py-4 text-xs">{{ $row['department'] }}</td>
                                <td class="px-6 py-4 text-emerald-600 font-extrabold">{{ $row['present'] }}</td>
                                <td class="px-6 py-4 text-amber-600 font-extrabold">{{ $row['late'] }}</td>
                                <td class="px-6 py-4 text-purple-600 font-extrabold">{{ $row['half_day'] }}</td>
                                <td class="px-6 py-4 text-rose-600 font-extrabold">{{ $row['absent'] }}</td>
                                <td class="px-6 py-4 text-slate-500 font-extrabold">{{ $row['weekly_offs'] }}</td>
                                <td class="px-6 py-4 text-indigo-500 font-extrabold">{{ $row['paid_leaves'] }}</td>
                                <td class="px-6 py-4 text-rose-400 font-extrabold">{{ $row['unpaid_leaves'] }}</td>
                                <td class="px-6 py-4 text-emerald-700 font-black">{{ $row['paid_days'] }}</td>
                                <td class="px-6 py-4 text-slate-900 font-bold">{{ $row['total_hours'] }} hrs</td>
                                <td class="px-6 py-4 text-indigo-600 font-extrabold">+{{ $row['overtime'] }} hrs</td>
                                <td class="px-6 py-4 text-rose-500 font-extrabold">-{{ $row['undertime'] }} hrs</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="14" class="px-6 py-12 text-center text-slate-400 font-semibold">No summary metrics could be computed.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 5. SHIFT POLICIES TAB -->
        <div id="shifts-tab" class="tab-content hidden bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100">
                <h3 class="font-extrabold text-slate-900 flex items-center gap-2"><i class="bi bi-clock text-indigo-500"></i> Active Shift Policy Deployment Logs</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 border-b border-slate-100 text-xs font-bold text-slate-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Emp Code</th>
                            <th class="px-6 py-4">Employee</th>
                            <th class="px-6 py-4">Department</th>
                            <th class="px-6 py-4">Shift Name</th>
                            <th class="px-6 py-4">Shift Type</th>
                            <th class="px-6 py-4">Hours required</th>
                            <th class="px-6 py-4">Weekly Offs</th>
                            <th class="px-6 py-4">Effective Range</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                        @forelse($shiftAssignments as $row)
                            <tr class="hover:bg-slate-50/50 transition duration-150">
                                <td class="px-6 py-4 font-bold text-slate-900">{{ $row->employee->employee_code ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-slate-900">{{ $row->employee->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-xs">{{ $row->employee->department->department_name ?? 'N/A' }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 border text-[10px] font-extrabold tracking-wider uppercase rounded-full bg-slate-100 border-slate-200 text-slate-600">{{ $row->shift->shift_name ?? 'Default Policy' }}</span>
                                </td>
                                <td class="px-6 py-4 uppercase text-xs tracking-wider text-indigo-600 font-extrabold">{{ $row->shift->shift_type ?? 'Flexible' }}</td>
                                <td class="px-6 py-4 font-extrabold text-slate-900">{{ $row->shift->minimum_working_hours ?? '8.00' }} hrs</td>
                                <td class="px-6 py-4 text-xs text-slate-400">{{ $row->shift && $row->shift->weekly_off_days ? str_replace(',', ', ', $row->shift->weekly_off_days) : 'None' }}</td>
                                <td class="px-6 py-4 text-xs">
                                    <span class="text-indigo-600">{{ $row->effective_from }}</span> to 
                                    <span class="{{ $row->effective_to ? 'text-slate-600' : 'text-emerald-600 font-bold' }}">{{ $row->effective_to ?? 'Ongoing' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-slate-400 font-semibold">No active shift allocations found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 6. LATE MARKS TAB -->
        <div id="late-tab" class="tab-content hidden bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100">
                <h3 class="font-extrabold text-slate-900 flex items-center gap-2"><i class="bi bi-exclamation-triangle text-amber-500"></i> Late Check-in Compliance Marks</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 border-b border-slate-100 text-xs font-bold text-slate-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Date</th>
                            <th class="px-6 py-4">Employee</th>
                            <th class="px-6 py-4">Shift Assigned</th>
                            <th class="px-6 py-4">Shift Start Time</th>
                            <th class="px-6 py-4">Grace Time</th>
                            <th class="px-6 py-4 text-rose-500">Actual Clock-in</th>
                            <th class="px-6 py-4 text-rose-500">Minutes Late</th>
                            <th class="px-6 py-4">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                        @forelse($lateMarks as $row)
                            <tr class="hover:bg-slate-50/50 transition duration-150">
                                <td class="px-6 py-4 font-bold text-slate-900">{{ $row->attendance_date }}</td>
                                <td class="px-6 py-4 text-slate-900">{{ $row->user->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-xs font-bold">{{ $row->shift->shift_name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-xs">{{ $row->shift->start_time ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-xs text-slate-400">{{ $row->shift->grace_time_minutes ?? 0 }} mins</td>
                                <td class="px-6 py-4 text-xs text-rose-600 font-extrabold">{{ $row->check_in ? \Carbon\Carbon::parse($row->check_in)->format('h:i:s A') : 'N/A' }}</td>
                                <td class="px-6 py-4 text-rose-600 font-black">
                                    @php
                                        $minsLate = 0;
                                        if($row->check_in && $row->shift && $row->shift->start_time) {
                                            $start = \Carbon\Carbon::createFromFormat('H:i:s', $row->shift->start_time);
                                            $startToday = \Carbon\Carbon::parse($row->attendance_date)->setTime($start->hour, $start->minute);
                                            $minsLate = \Carbon\Carbon::parse($row->check_in)->diffInMinutes($startToday);
                                        }
                                    @endphp
                                    {{ $minsLate }} mins
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-400 max-w-[200px] truncate">{{ $row->remarks }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-slate-400 font-semibold">Perfect compliance! No late check-ins recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 7. WORKING HOURS TAB -->
        <div id="hours-tab" class="tab-content hidden bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100">
                <h3 class="font-extrabold text-slate-900 flex items-center gap-2"><i class="bi bi-hourglass-split text-indigo-500"></i> Working Hours & Policy Exceptions</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 border-b border-slate-100 text-xs font-bold text-slate-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Date</th>
                            <th class="px-6 py-4">Employee</th>
                            <th class="px-6 py-4">Shift Name</th>
                            <th class="px-6 py-4">Required Hours</th>
                            <th class="px-6 py-4">Actual Worked</th>
                            <th class="px-6 py-4 text-emerald-600">Overtime</th>
                            <th class="px-6 py-4 text-rose-500">Under-time</th>
                            <th class="px-6 py-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                        @forelse($workingHoursLogs as $row)
                            @php
                                $minHrs = $row->shift->minimum_working_hours ?? 8.00;
                                $overtime = ($row->working_hours > $minHrs) ? round($row->working_hours - $minHrs, 2) : 0.00;
                                $undertime = ($row->working_hours < $minHrs) ? round($minHrs - $row->working_hours, 2) : 0.00;
                            @endphp
                            <tr class="hover:bg-slate-50/50 transition duration-150">
                                <td class="px-6 py-4 font-bold text-slate-900">{{ $row->attendance_date }}</td>
                                <td class="px-6 py-4 text-slate-900">{{ $row->user->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-xs">{{ $row->shift->shift_name ?? 'Default' }}</td>
                                <td class="px-6 py-4">{{ $minHrs }} hrs</td>
                                <td class="px-6 py-4 font-bold text-slate-900">{{ $row->working_hours }} hrs</td>
                                <td class="px-6 py-4 text-emerald-600 font-extrabold">+{{ $overtime }} hrs</td>
                                <td class="px-6 py-4 text-rose-500 font-extrabold">-{{ $undertime }} hrs</td>
                                <td class="px-6 py-4 text-xs font-bold uppercase tracking-wider">{{ $row->status }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-slate-400 font-semibold">No working hour durations tracked.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 8. BIOMETRIC AUDITS TAB -->
        <div id="face-tab" class="tab-content hidden bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100">
                <h3 class="font-extrabold text-slate-900 flex items-center gap-2"><i class="bi bi-person-bounding-box text-indigo-500"></i> Facial Recognition Audit Logs & Liveness checks</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 border-b border-slate-100 text-xs font-bold text-slate-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Timestamp</th>
                            <th class="px-6 py-4">Employee</th>
                            <th class="px-6 py-4">Captured Image</th>
                            <th class="px-6 py-4">Verification Type</th>
                            <th class="px-6 py-4">Confidence score</th>
                            <th class="px-6 py-4">Liveness anti-spoof</th>
                            <th class="px-6 py-4">Match Status</th>
                            <th class="px-6 py-4">System Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                        @forelse($biometricLogs as $row)
                            <tr class="hover:bg-slate-50/50 transition duration-150">
                                <td class="px-6 py-4 text-xs font-bold text-slate-900">{{ $row->created_at->format('M d, Y h:i A') }}</td>
                                <td class="px-6 py-4 text-slate-900">{{ $row->user->name ?? 'System/Unknown' }}</td>
                                <td class="px-6 py-4">
                                    @if($row->captured_image)
                                        <a href="{{ Storage::url($row->captured_image) }}" target="_blank" class="block w-10 h-10 rounded-lg overflow-hidden border border-slate-200 shadow-sm hover:scale-105 transition-all">
                                            <img src="{{ Storage::url($row->captured_image) }}" class="w-full h-full object-cover" />
                                        </a>
                                    @else
                                        <span class="text-xs text-slate-400">N/A</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 uppercase text-[10px] font-extrabold tracking-wider text-indigo-600">{{ $row->action_type }}</td>
                                <td class="px-6 py-4 font-black">{{ $row->confidence_score }}%</td>
                                <td class="px-6 py-4">
                                    @if($row->liveness_passed)
                                        <span class="px-2 py-0.5 text-[9px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-full">PASSED</span>
                                    @else
                                        <span class="px-2 py-0.5 text-[9px] font-bold text-rose-700 bg-rose-50 border border-rose-200 rounded-full">FAILED</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $fBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                        if($row->status !== 'success') $fBadge = 'bg-rose-50 text-rose-700 border-rose-200';
                                    @endphp
                                    <span class="px-2 py-0.5 text-[9px] font-extrabold tracking-wider border uppercase rounded-full {{ $fBadge }}">{{ str_replace('_', ' ', $row->status) }}</span>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-400 max-w-[200px] truncate" title="{{ $row->remarks }}">{{ $row->remarks }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-slate-400 font-semibold">No biometric match checks performed.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 9. GPS GEOFENCING TAB -->
        <div id="gps-tab" class="tab-content hidden bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100">
                <h3 class="font-extrabold text-slate-900 flex items-center gap-2"><i class="bi bi-geo-alt text-indigo-500"></i> Location Coordinates & Geofencing Proximity checks</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 border-b border-slate-100 text-xs font-bold text-slate-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Date</th>
                            <th class="px-6 py-4">Employee</th>
                            <th class="px-6 py-4">Assigned Location</th>
                            <th class="px-6 py-4">Clocked Coordinate</th>
                            <th class="px-6 py-4">Proximity Distance</th>
                            <th class="px-6 py-4">Geofence Violations</th>
                            <th class="px-6 py-4">Logs remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                        @forelse($gpsLogs as $row)
                            <tr class="hover:bg-slate-50/50 transition duration-150">
                                <td class="px-6 py-4 font-bold text-slate-900">{{ $row->attendance_date }}</td>
                                <td class="px-6 py-4 text-slate-900">{{ $row->user->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-xs font-bold">{{ $row->user->location->location_name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-xs font-bold text-indigo-600">
                                    {{ $row->location ?? 'No GPS Captured' }}
                                    @if ($row->login_type === 'remote')
                                        <span class="block mt-1 text-[9px] font-extrabold uppercase tracking-wider px-1.5 py-0.5 rounded border bg-sky-50 text-sky-700 border-sky-200 w-max flex items-center gap-1">
                                            <i class="bi bi-broadcast text-sky-500"></i> Remote Login
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 font-black">
                                    @if($row->distance_km !== null)
                                        {{ round($row->distance_km * 1000, 1) }} meters ({{ round($row->distance_km, 3) }} km)
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($row->login_type === 'remote')
                                        <span class="px-2.5 py-1 border text-[10px] font-extrabold tracking-wider uppercase rounded-full bg-blue-50 text-blue-700 border-blue-200">REMOTE ALLOWED</span>
                                    @elseif($row->distance_km !== null && $row->distance_km > 0.2)
                                        <span class="px-2.5 py-1 border text-[10px] font-extrabold tracking-wider uppercase rounded-full bg-rose-50 text-rose-700 border-rose-200">OUT OF BOUNDS</span>
                                    @elseif($row->distance_km !== null)
                                        <span class="px-2.5 py-1 border text-[10px] font-extrabold tracking-wider uppercase rounded-full bg-emerald-50 text-emerald-700 border-emerald-200">VERIFIED SAFE</span>
                                    @else
                                        <span class="text-xs text-slate-400">N/A</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-400 max-w-[200px] truncate" title="{{ $row->remarks }}">{{ $row->remarks }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-slate-400 font-semibold">No location check records available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- Dynamic FullCalendar details Modal -->
<div id="calendarModal" class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4 transition-all duration-300">
    <div class="bg-white border border-slate-100 rounded-3xl max-w-lg w-full shadow-2xl p-6 relative animate-scale-up">
        <!-- Close Button -->
        <button onclick="closeModal()" class="absolute top-5 right-5 w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition">
            <i class="bi bi-x-lg"></i>
        </button>

        <!-- Modal Head -->
        <div class="flex items-center gap-3 mb-6 pb-4 border-b">
            <div id="modalInitials" class="w-12 h-12 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-lg">FC</div>
            <div>
                <h4 id="modalName" class="text-base font-extrabold text-slate-900 leading-none mb-1">Employee Details</h4>
                <span id="modalCode" class="text-xs font-bold text-slate-400">EMP001 • Technology</span>
            </div>
        </div>

        <!-- Modal Grid Details -->
        <div class="grid grid-cols-2 gap-4 text-sm font-semibold text-slate-700">
            <div>
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Assigned Shift</span>
                <span id="modalShift" class="text-slate-900">-</span>
            </div>
            <div>
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Assigned Location</span>
                <span id="modalLoc" class="text-slate-900">-</span>
            </div>
            <div>
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Check In Time</span>
                <span id="modalCheckIn" class="text-indigo-600 font-bold">-</span>
            </div>
            <div>
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Check Out Time</span>
                <span id="modalCheckOut" class="text-indigo-600 font-bold">-</span>
            </div>
            <div>
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Working Hours</span>
                <span id="modalHours" class="text-slate-900 font-extrabold">-</span>
            </div>
            <div>
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Geofence Distance</span>
                <span id="modalDistance" class="text-slate-900">-</span>
            </div>
            <div>
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Login Type</span>
                <span id="modalLoginType" class="px-2.5 py-1 text-xs font-extrabold uppercase border rounded-full self-start inline-block">-</span>
            </div>
            <div class="col-span-2">
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Policy Status</span>
                <span id="modalStatus" class="px-2 py-0.5 text-xs font-extrabold uppercase border rounded-full self-start inline-block">-</span>
            </div>
            <div class="col-span-2">
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Policy Flags & Remarks</span>
                <span id="modalRemarks" class="text-slate-400 text-xs">-</span>
            </div>

            <!-- Face Snapshot Thumbnail -->
            <div id="modalPhotoContainer" class="col-span-2 hidden pt-3 border-t">
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Biometrics verification snap</span>
                <div class="w-full h-36 rounded-2xl overflow-hidden border border-slate-100 shadow-inner">
                    <img id="modalPhoto" src="" class="w-full h-full object-cover" />
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Tab switching controller
    let activeTabId = 'analytics-tab';
    let calendarInitialized = false;

    function switchTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active-tab', 'border-indigo-600', 'text-indigo-600');
            btn.classList.add('border-transparent', 'text-slate-500');
        });

        document.getElementById(tabId).classList.remove('hidden');
        const activeBtn = document.getElementById('tab-' + tabId);
        activeBtn.classList.add('active-tab', 'border-indigo-600', 'text-indigo-600');
        activeBtn.classList.remove('border-transparent', 'text-slate-500');

        activeTabId = tabId;

        // Initialize Calendar only if selected and not yet done
        if (tabId === 'calendar-tab' && !calendarInitialized) {
            initCalendar();
        }
    }

    // Dynamic Export Link triggerer based on active tab context
    function triggerExport(format) {
        // Map tab names to export sheet types
        const tabToType = {
            'logs-tab': 'employee_logs',
            'summary-tab': 'monthly_summary',
            'shifts-tab': 'shifts',
            'late-tab': 'late_marks',
            'hours-tab': 'working_hours',
            'face-tab': 'face_recognition',
            'gps-tab': 'location_gps',
            'analytics-tab': 'employee_logs', // default back
            'calendar-tab': 'employee_logs'
        };

        const type = tabToType[activeTabId] || 'employee_logs';

        // Read active filter values
        const params = new URLSearchParams({
            type: type,
            start_date: document.querySelector('[name="start_date"]').value,
            end_date: document.querySelector('[name="end_date"]').value,
            department_id: document.querySelector('[name="department_id"]').value,
            location_id: document.querySelector('[name="location_id"]').value,
            user_id: document.querySelector('[name="user_id"]').value,
        });

        const url = `/admin/reports/export/${format}?${params.toString()}`;
        
        if (format === 'pdf') {
            window.open(url, '_blank');
        } else {
            window.location.href = url;
        }
    }

    // FullCalendar Setup
    function initCalendar() {
        const calendarEl = document.getElementById('full-calendar');
        if (!calendarEl) return;

        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,listMonth'
            },
            events: function(info, successCallback, failureCallback) {
                // Fetch events from our provider with active filter values
                const params = new URLSearchParams({
                    start: info.startStr.split('T')[0],
                    end: info.endStr.split('T')[0],
                    department_id: document.querySelector('[name="department_id"]').value,
                    location_id: document.querySelector('[name="location_id"]').value,
                    user_id: document.querySelector('[name="user_id"]').value,
                });

                fetch(`/admin/reports/calendar-events?${params.toString()}`)
                    .then(response => response.json())
                    .then(data => successCallback(data))
                    .catch(err => failureCallback(err));
            },
            eventClick: function(info) {
                // Open dynamic glassmorphic modal prefilled with details
                const props = info.event.extendedProps;
                
                document.getElementById('modalName').innerText = props.employee;
                document.getElementById('modalCode').innerText = `${props.employee_code} • ${props.department}`;
                document.getElementById('modalShift').innerText = props.remarks.includes('Default') ? 'Default Policy' : 'Assigned Shift';
                document.getElementById('modalLoc').innerText = props.location;
                document.getElementById('modalCheckIn').innerText = props.check_in;
                document.getElementById('modalCheckOut').innerText = props.check_out;
                document.getElementById('modalHours').innerText = props.working_hours;
                document.getElementById('modalDistance').innerText = props.distance_km;
                document.getElementById('modalRemarks').innerText = props.remarks;

                // Populate and color login type badge inside modal
                const loginTypeBadge = document.getElementById('modalLoginType');
                const isRemote = props.login_type === 'remote';
                loginTypeBadge.innerText = isRemote ? 'REMOTE' : 'OFFICE';
                loginTypeBadge.className = 'px-2.5 py-1 text-xs font-extrabold uppercase border rounded-full inline-block ';
                if(isRemote) {
                    loginTypeBadge.classList.add('bg-sky-50', 'border-sky-200', 'text-sky-700');
                } else {
                    loginTypeBadge.classList.add('bg-slate-50', 'border-slate-200', 'text-slate-700');
                }

                // Color status badges inside modal
                const statusBadge = document.getElementById('modalStatus');
                statusBadge.innerText = props.status;
                statusBadge.className = 'px-2.5 py-1 text-xs font-extrabold uppercase border rounded-full inline-block ';
                if(props.status.toLowerCase() === 'present') {
                    statusBadge.classList.add('bg-emerald-50', 'border-emerald-200', 'text-emerald-700');
                } else if(props.status.toLowerCase() === 'late') {
                    statusBadge.classList.add('bg-amber-50', 'border-amber-200', 'text-amber-700');
                } else if(props.status.toLowerCase() === 'half_day' || props.status.toLowerCase() === 'half day') {
                    statusBadge.classList.add('bg-purple-50', 'border-purple-200', 'text-purple-700');
                } else {
                    statusBadge.classList.add('bg-rose-50', 'border-rose-200', 'text-rose-700');
                }

                // Show picture if available
                const snapContainer = document.getElementById('modalPhotoContainer');
                const snapImg = document.getElementById('modalPhoto');
                if (props.captured_image) {
                    snapImg.src = props.captured_image;
                    snapContainer.classList.remove('hidden');
                } else {
                    snapContainer.classList.add('hidden');
                }

                // Open modal
                document.getElementById('calendarModal').classList.remove('hidden');
            }
        });

        calendar.render();
        calendarInitialized = true;
    }

    function closeModal() {
        document.getElementById('calendarModal').classList.add('hidden');
    }

    // Chart.js renderers
    window.addEventListener('DOMContentLoaded', () => {
        // Status Distribution
        const ctxStatus = document.getElementById('statusChart');
        if (ctxStatus) {
            new Chart(ctxStatus, {
                type: 'doughnut',
                data: {
                    labels: ['Present', 'Late', 'Half Day', 'Absent'],
                    datasets: [{
                        data: [
                            {{ $analytics['present_count'] }},
                            {{ $analytics['late_count'] }},
                            {{ $analytics['half_day_count'] }},
                            {{ $analytics['absent_count'] }}
                        ],
                        backgroundColor: ['#10b981', '#f59e0b', '#8b5cf6', '#ef4444'],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                padding: 15,
                                font: { weight: 'bold', family: 'Plus Jakarta Sans', size: 11 }
                            }
                        }
                    }
                }
            });
        }

        // Trend graph
        const ctxTrend = document.getElementById('trendChart');
        if (ctxTrend) {
            new Chart(ctxTrend, {
                type: 'bar',
                data: {
                    labels: ['Present logs', 'Late clock-ins', 'Half Days', 'Absent marks'],
                    datasets: [{
                        label: 'Total instances',
                        data: [
                            {{ $analytics['present_count'] }},
                            {{ $analytics['late_count'] }},
                            {{ $analytics['half_day_count'] }},
                            {{ $analytics['absent_count'] }}
                        ],
                        backgroundColor: ['rgba(16, 185, 129, 0.15)', 'rgba(245, 158, 11, 0.15)', 'rgba(139, 92, 246, 0.15)', 'rgba(239, 68, 68, 0.15)'],
                        borderColor: ['#10b981', '#f59e0b', '#8b5cf6', '#ef4444'],
                        borderWidth: 2,
                        borderRadius: 8,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { font: { weight: 'bold', family: 'Plus Jakarta Sans' } }
                        },
                        x: {
                            ticks: { font: { weight: 'bold', family: 'Plus Jakarta Sans' } }
                        }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }   // end if (ctxTrend)
    });     // end DOMContentLoaded

    // setPreset must be GLOBAL so onclick="setPreset(...)" works from HTML buttons
    function setPreset(preset) {
        const today = new Date();
        
        const formatDate = (date) => {
            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const d = String(date.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        };

        let start, end;

        if (preset === 'daily') {
            start = today;
            end = today;
        } else if (preset === 'weekly') {
            const day = today.getDay();
            const startOfWeek = new Date(today);
            const diffToMonday = today.getDate() - day + (day === 0 ? -6 : 1);
            start = new Date(startOfWeek.setDate(diffToMonday));
            const endOfWeek = new Date(start);
            end = new Date(endOfWeek.setDate(start.getDate() + 6));
        } else if (preset === 'monthly') {
            start = new Date(today.getFullYear(), today.getMonth(), 1);
            end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        }

        document.getElementsByName('start_date')[0].value = formatDate(start);
        document.getElementsByName('end_date')[0].value = formatDate(end);
        
        // Submit the filter form (first form on the page is the reports filter form)
        document.querySelector('form[action]').submit();
    }

</script>

<style>
    /* Clean style transitions */
    .active-tab {
        background-color: rgba(99, 102, 241, 0.04);
    }
    
    @keyframes scaleUp {
        from { transform: scale(0.95); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
    .animate-scale-up {
        animation: scaleUp 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
</style>
@endsection
