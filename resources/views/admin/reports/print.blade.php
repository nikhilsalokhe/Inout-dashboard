<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #ffffff;
            color: #1e293b;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>
<body class="p-8">
    
    <!-- Print toolbar -->
    <div class="no-print mb-6 p-4 bg-slate-50 border rounded-2xl flex justify-between items-center">
        <div>
            <h4 class="font-bold text-slate-800 text-sm">Print Preview Suite</h4>
            <span class="text-xs text-slate-400">Use this browser framework to save your high-fidelity PDF report.</span>
        </div>
        <div class="flex gap-2">
            <button onclick="window.print()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-wider px-4 py-2.5 rounded-xl transition">
                Print Report / Save PDF
            </button>
            <button onclick="window.close()" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs uppercase tracking-wider px-4 py-2.5 rounded-xl transition">
                Close Frame
            </button>
        </div>
    </div>

    <!-- Printable Header Card -->
    <div class="border-b-2 border-slate-900 pb-6 mb-8 flex justify-between items-end">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <div class="w-6 h-6 bg-slate-900 rounded flex items-center justify-center text-white text-xs font-bold">I</div>
                <span class="text-sm font-extrabold tracking-widest text-slate-950 uppercase">InOut Systems</span>
            </div>
            <h1 class="text-2xl font-black text-slate-950 uppercase tracking-tight">{{ str_replace('Report', '', $title) }} Report</h1>
            <span class="text-xs font-bold text-slate-400">Report Range: {{ $startDate }} to {{ $endDate }}</span>
        </div>
        <div class="text-right text-xs font-bold text-slate-500">
            <span>Generated: {{ date('M d, Y h:i A') }}</span><br>
            <span>Authorized by: InOut Suite Admin</span>
        </div>
    </div>

    <!-- Dynamic Tables based on Type -->
    <div class="text-sm font-medium text-slate-800">
        
        @if($type === 'employee_logs')
            <table class="w-full text-left border-collapse border border-slate-200">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-300 text-xs font-bold text-slate-900 uppercase">
                        <th class="border border-slate-200 px-4 py-3">Date</th>
                        <th class="border border-slate-200 px-4 py-3">Emp Code</th>
                        <th class="border border-slate-200 px-4 py-3">Employee Name</th>
                        <th class="border border-slate-200 px-4 py-3">Department</th>
                        <th class="border border-slate-200 px-4 py-3">Check In</th>
                        <th class="border border-slate-200 px-4 py-3">Check Out</th>
                        <th class="border border-slate-200 px-4 py-3">Hours</th>
                        <th class="border border-slate-200 px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($data as $row)
                        <tr>
                            <td class="border border-slate-200 px-4 py-2.5 font-bold">{{ $row->attendance_date }}</td>
                            <td class="border border-slate-200 px-4 py-2.5">{{ $row->user->employee_code ?? 'N/A' }}</td>
                            <td class="border border-slate-200 px-4 py-2.5 font-semibold text-slate-950">{{ $row->user->name ?? 'N/A' }}</td>
                            <td class="border border-slate-200 px-4 py-2.5 text-xs">{{ $row->user->department->department_name ?? 'N/A' }}</td>
                            <td class="border border-slate-200 px-4 py-2.5 text-xs">{{ $row->check_in ? \Carbon\Carbon::parse($row->check_in)->format('h:i A') : 'N/A' }}</td>
                            <td class="border border-slate-200 px-4 py-2.5 text-xs">{{ $row->check_out ? \Carbon\Carbon::parse($row->check_out)->format('h:i A') : 'N/A' }}</td>
                            <td class="border border-slate-200 px-4 py-2.5 font-bold">{{ $row->working_hours ?? '0.00' }} hrs</td>
                            <td class="border border-slate-200 px-4 py-2.5 uppercase font-extrabold text-[10px]">{{ $row->status }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        @elseif($type === 'monthly_summary')
            <table class="w-full text-left border-collapse border border-slate-200">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-300 text-xs font-bold text-slate-900 uppercase">
                        <th class="border border-slate-200 px-4 py-3">Emp Code</th>
                        <th class="border border-slate-200 px-4 py-3">Name</th>
                        <th class="border border-slate-200 px-4 py-3">Department</th>
                        <th class="border border-slate-200 px-4 py-3 text-emerald-800">Present</th>
                        <th class="border border-slate-200 px-4 py-3 text-amber-800">Late</th>
                        <th class="border border-slate-200 px-4 py-3 text-purple-800">Half Days</th>
                        <th class="border border-slate-200 px-4 py-3 text-rose-800">Absent</th>
                        <th class="border border-slate-200 px-4 py-3">Worked Hrs</th>
                        <th class="border border-slate-200 px-4 py-3 text-indigo-700">Overtime</th>
                        <th class="border border-slate-200 px-4 py-3 text-rose-600">Under-time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($data as $row)
                        <tr>
                            <td class="border border-slate-200 px-4 py-2.5 font-bold">{{ $row->employee_code }}</td>
                            <td class="border border-slate-200 px-4 py-2.5 font-bold text-slate-950">{{ $row->name }}</td>
                            <td class="border border-slate-200 px-4 py-2.5 text-xs">{{ $row->department }}</td>
                            <td class="border border-slate-200 px-4 py-2.5 text-emerald-700 font-extrabold">{{ $row->present }}</td>
                            <td class="border border-slate-200 px-4 py-2.5 text-amber-700 font-extrabold">{{ $row->late }}</td>
                            <td class="border border-slate-200 px-4 py-2.5 text-purple-700 font-extrabold">{{ $row->half_day }}</td>
                            <td class="border border-slate-200 px-4 py-2.5 text-rose-700 font-extrabold">{{ $row->absent }}</td>
                            <td class="border border-slate-200 px-4 py-2.5 font-bold">{{ $row->total_hours }} hrs</td>
                            <td class="border border-slate-200 px-4 py-2.5 text-indigo-700 font-extrabold">+{{ $row->overtime }} hrs</td>
                            <td class="border border-slate-200 px-4 py-2.5 text-rose-600 font-extrabold">-{{ $row->undertime }} hrs</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        @elseif($type === 'late_marks')
            <table class="w-full text-left border-collapse border border-slate-200">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-300 text-xs font-bold text-slate-900 uppercase">
                        <th class="border border-slate-200 px-4 py-3">Date</th>
                        <th class="border border-slate-200 px-4 py-3">Employee Name</th>
                        <th class="border border-slate-200 px-4 py-3">Shift Assigned</th>
                        <th class="border border-slate-200 px-4 py-3">Shift Start</th>
                        <th class="border border-slate-200 px-4 py-3">Grace</th>
                        <th class="border border-slate-200 px-4 py-3 text-rose-700">Actual clock-in</th>
                        <th class="border border-slate-200 px-4 py-3 text-rose-700">Mins Late</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($data as $row)
                        <tr>
                            <td class="border border-slate-200 px-4 py-2.5 font-bold">{{ $row->attendance_date }}</td>
                            <td class="border border-slate-200 px-4 py-2.5 font-bold text-slate-950">{{ $row->user->name ?? 'N/A' }}</td>
                            <td class="border border-slate-200 px-4 py-2.5 text-xs font-bold">{{ $row->shift->shift_name ?? 'N/A' }}</td>
                            <td class="border border-slate-200 px-4 py-2.5 text-xs">{{ $row->shift->start_time ?? 'N/A' }}</td>
                            <td class="border border-slate-200 px-4 py-2.5 text-xs text-slate-400">{{ $row->shift->grace_time_minutes ?? 0 }} mins</td>
                            <td class="border border-slate-200 px-4 py-2.5 text-xs text-rose-600 font-extrabold">{{ $row->check_in ? \Carbon\Carbon::parse($row->check_in)->format('h:i:s A') : 'N/A' }}</td>
                            <td class="border border-slate-200 px-4 py-2.5 text-rose-600 font-black">
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
                        </tr>
                    @endforeach
                </tbody>
            </table>

        @elseif($type === 'working_hours')
            <table class="w-full text-left border-collapse border border-slate-200">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-300 text-xs font-bold text-slate-900 uppercase">
                        <th class="border border-slate-200 px-4 py-3">Date</th>
                        <th class="border border-slate-200 px-4 py-3">Employee</th>
                        <th class="border border-slate-200 px-4 py-3">Shift Name</th>
                        <th class="border border-slate-200 px-4 py-3">Required</th>
                        <th class="border border-slate-200 px-4 py-3">Actual Worked</th>
                        <th class="border border-slate-200 px-4 py-3 text-emerald-800">Overtime</th>
                        <th class="border border-slate-200 px-4 py-3 text-rose-600">Under-time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($data as $row)
                        @php
                            $minHrs = $row->shift->minimum_working_hours ?? 8.00;
                            $overtime = ($row->working_hours > $minHrs) ? round($row->working_hours - $minHrs, 2) : 0.00;
                            $undertime = ($row->working_hours < $minHrs) ? round($minHrs - $row->working_hours, 2) : 0.00;
                        @endphp
                        <tr>
                            <td class="border border-slate-200 px-4 py-2.5 font-bold">{{ $row->attendance_date }}</td>
                            <td class="border border-slate-200 px-4 py-2.5 font-bold text-slate-950">{{ $row->user->name ?? 'N/A' }}</td>
                            <td class="border border-slate-200 px-4 py-2.5 text-xs">{{ $row->shift->shift_name ?? 'Default' }}</td>
                            <td class="border border-slate-200 px-4 py-2.5">{{ $minHrs }} hrs</td>
                            <td class="border border-slate-200 px-4 py-2.5 font-bold text-slate-950">{{ $row->working_hours }} hrs</td>
                            <td class="border border-slate-200 px-4 py-2.5 text-emerald-700 font-extrabold">+{{ $overtime }} hrs</td>
                            <td class="border border-slate-200 px-4 py-2.5 text-rose-600 font-extrabold">-{{ $undertime }} hrs</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        @elseif($type === 'location_gps')
            <table class="w-full text-left border-collapse border border-slate-200">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-300 text-xs font-bold text-slate-900 uppercase">
                        <th class="border border-slate-200 px-4 py-3">Date</th>
                        <th class="border border-slate-200 px-4 py-3">Employee</th>
                        <th class="border border-slate-200 px-4 py-3">Assigned Location</th>
                        <th class="border border-slate-200 px-4 py-3">Clocked Coordinates</th>
                        <th class="border border-slate-200 px-4 py-3">Proximity Distance</th>
                        <th class="border border-slate-200 px-4 py-3">Geofence Compliance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($data as $row)
                        <tr>
                            <td class="border border-slate-200 px-4 py-2.5 font-bold">{{ $row->attendance_date }}</td>
                            <td class="border border-slate-200 px-4 py-2.5 font-bold text-slate-950">{{ $row->user->name ?? 'N/A' }}</td>
                            <td class="border border-slate-200 px-4 py-2.5 text-xs">{{ $row->user->location->location_name ?? 'N/A' }}</td>
                            <td class="border border-slate-200 px-4 py-2.5 text-xs font-bold text-indigo-600">{{ $row->location }}</td>
                            <td class="border border-slate-200 px-4 py-2.5 font-bold">
                                @if($row->distance_km !== null)
                                    {{ round($row->distance_km * 1000, 1) }} meters ({{ round($row->distance_km, 3) }} km)
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="border border-slate-200 px-4 py-2.5">
                                @if($row->distance_km !== null && $row->distance_km > 0.2)
                                    <span class="text-rose-700 font-extrabold text-xs">OUT OF BOUNDS</span>
                                @elseif($row->distance_km !== null)
                                    <span class="text-emerald-700 font-extrabold text-xs">IN RANGE</span>
                                @else
                                    <span class="text-slate-400 text-xs">N/A</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        @elseif($type === 'shifts')
            <table class="w-full text-left border-collapse border border-slate-200">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-300 text-xs font-bold text-slate-900 uppercase">
                        <th class="border border-slate-200 px-4 py-3">Emp Code</th>
                        <th class="border border-slate-200 px-4 py-3">Employee</th>
                        <th class="border border-slate-200 px-4 py-3">Department</th>
                        <th class="border border-slate-200 px-4 py-3">Shift Policy</th>
                        <th class="border border-slate-200 px-4 py-3">Shift Type</th>
                        <th class="border border-slate-200 px-4 py-3">Required Hours</th>
                        <th class="border border-slate-200 px-4 py-3">Weekly Offs</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($data as $row)
                        <tr>
                            <td class="border border-slate-200 px-4 py-2.5 font-bold">{{ $row->employee->employee_code ?? 'N/A' }}</td>
                            <td class="border border-slate-200 px-4 py-2.5 font-bold text-slate-950">{{ $row->employee->name ?? 'N/A' }}</td>
                            <td class="border border-slate-200 px-4 py-2.5 text-xs">{{ $row->employee->department->department_name ?? 'N/A' }}</td>
                            <td class="border border-slate-200 px-4 py-2.5 font-bold">{{ $row->shift->shift_name ?? 'Default Policy' }}</td>
                            <td class="border border-slate-200 px-4 py-2.5 text-indigo-700 font-bold uppercase text-xs">{{ $row->shift->shift_type ?? 'Flexible' }}</td>
                            <td class="border border-slate-200 px-4 py-2.5 font-bold">{{ $row->shift->minimum_working_hours ?? '8.00' }} hrs</td>
                            <td class="border border-slate-200 px-4 py-2.5 text-xs text-slate-400">{{ $row->shift && $row->shift->weekly_off_days ? str_replace(',', ', ', $row->shift->weekly_off_days) : 'None' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        @elseif($type === 'face_recognition')
            <table class="w-full text-left border-collapse border border-slate-200">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-300 text-xs font-bold text-slate-900 uppercase">
                        <th class="border border-slate-200 px-4 py-3">Timestamp</th>
                        <th class="border border-slate-200 px-4 py-3">Employee</th>
                        <th class="border border-slate-200 px-4 py-3">Type</th>
                        <th class="border border-slate-200 px-4 py-3">Confidence</th>
                        <th class="border border-slate-200 px-4 py-3">Liveness</th>
                        <th class="border border-slate-200 px-4 py-3">Status</th>
                        <th class="border border-slate-200 px-4 py-3">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($data as $row)
                        <tr>
                            <td class="border border-slate-200 px-4 py-2.5 text-xs font-bold">{{ $row->created_at->format('M d, Y h:i A') }}</td>
                            <td class="border border-slate-200 px-4 py-2.5 font-bold text-slate-950">{{ $row->user->name ?? 'System/Unknown' }}</td>
                            <td class="border border-slate-200 px-4 py-2.5 uppercase font-bold text-indigo-600 text-xs">{{ $row->action_type }}</td>
                            <td class="border border-slate-200 px-4 py-2.5 font-black">{{ $row->confidence_score }}%</td>
                            <td class="border border-slate-200 px-4 py-2.5 text-xs font-bold">{{ $row->liveness_passed ? 'PASSED' : 'FAILED' }}</td>
                            <td class="border border-slate-200 px-4 py-2.5 uppercase text-xs font-extrabold">{{ $row->status }}</td>
                            <td class="border border-slate-200 px-4 py-2.5 text-xs text-slate-500 max-w-[200px] truncate">{{ $row->remarks }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        @endif

    </div>

    <!-- Print trigger -->
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            // Automatically open system printer modal
            setTimeout(() => {
                window.print();
            }, 500);
        });
    </script>
</body>
</html>
