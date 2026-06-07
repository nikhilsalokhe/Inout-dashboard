<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\Department;
use App\Models\Location;
use App\Models\Holiday;
use App\Models\FaceRecognitionLog;
use App\Models\FaceResetRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    /**
     * Show the main admin dashboard with advanced KPIs and Quick Panels.
     */
    public function dashboard()
    {
        if (auth()->user()->role === 'employee') {
            return redirect()->route('employee.dashboard');
        }

        $today = Carbon::today()->toDateString();
        $dayName = Carbon::today()->format('l');

        $totalEmployees = User::where('role', 'employee')->where('status', 'active')->count();

        $todayAttendances = Attendance::with(['user', 'shift'])
            ->where('attendance_date', $today)
            ->get();

        $presentToday = $todayAttendances->where('status', 'present')->count();
        $lateToday    = $todayAttendances->where('status', 'late')->count();
        $halfDayToday = $todayAttendances->where('status', 'half_day')->count();
        $absentToday  = $todayAttendances->where('status', 'absent')->count();
        $weeklyOff    = $todayAttendances->where('status', 'weekly_off')->count();

        // Employees who haven't checked in
        $checkedInIds = $todayAttendances->pluck('user_id')->toArray();
        $notCheckedIn = $totalEmployees - count($checkedInIds);
        if ($notCheckedIn < 0) $notCheckedIn = 0;

        // Employees currently working (checked in but no check out)
        $stillWorking = $todayAttendances->whereNotNull('check_in')
            ->filter(function ($a) { return is_null($a->check_out); })
            ->count();

        // Attendance Rate
        $attendanceRate = $totalEmployees > 0
            ? round((($presentToday + $lateToday) / $totalEmployees) * 100, 1)
            : 0;

        // Recent 10 check-ins
        $recentCheckIns = Attendance::with(['user', 'shift'])
            ->where('attendance_date', $today)
            ->whereNotNull('check_in')
            ->orderBy('check_in', 'desc')
            ->limit(10)
            ->get();

        // Department-wise breakdown
        $departmentStats = User::where('role', 'employee')
            ->where('status', 'active')
            ->whereNotNull('department_id')
            ->with('department')
            ->get()
            ->groupBy(function ($user) {
                return $user->department ? $user->department->department_name : 'Unassigned';
            })
            ->map(function ($employees, $deptName) use ($today) {
                $ids = $employees->pluck('id');
                $attendances = Attendance::where('attendance_date', $today)
                    ->whereIn('user_id', $ids)
                    ->get();
                return [
                    'department' => $deptName,
                    'total'      => $employees->count(),
                    'present'    => $attendances->where('status', 'present')->count(),
                    'late'       => $attendances->where('status', 'late')->count(),
                    'absent'     => $attendances->where('status', 'absent')->count() + ($employees->count() - $attendances->count()),
                ];
            })
            ->values();

        // Active Shifts Count
        $activeShiftsCount = Shift::where('status', 'active')->count();

        // QUICK PANEL: 1. Pending Face Reset Requests
        $pendingFaceResets = FaceResetRequest::with('employee')
            ->where('status', 'pending')
            ->orderBy('requested_at', 'desc')
            ->get();

        // QUICK PANEL: 2. Attendance Exceptions (Missing checkouts, Geofencing exceptions)
        // Let's look at the last 7 days of logs
        $lastWeek = Carbon::today()->subDays(7)->toDateString();
        $attendanceExceptions = Attendance::with(['user', 'shift'])
            ->where('attendance_date', '>=', $lastWeek)
            ->where(function($q) use ($today) {
                // Missing check-out for past days
                $q->where(function($sub) use ($today) {
                    $sub->where('attendance_date', '<', $today)
                        ->whereNotNull('check_in')
                        ->whereNull('check_out');
                })
                // Or Geofencing violations (distance > 200m / 0.2km)
                ->orWhere('distance_km', '>', 0.2);
            })
            ->orderBy('attendance_date', 'desc')
            ->get();

        // QUICK PANEL: 3. Policy & Shift Violations (Late check-ins, early clock-outs/undertime)
        $shiftViolations = Attendance::with(['user', 'shift'])
            ->where('attendance_date', '>=', $lastWeek)
            ->where(function($q) {
                $q->where('status', 'late')
                  ->orWhere(function($sub) {
                      // Under-time: checked out, but working hours less than shift's minimum hours
                      $sub->whereNotNull('check_out')
                          ->whereHas('shift', function($sQuery) {
                              $sQuery->whereRaw('working_hours < shifts.minimum_working_hours');
                          });
                  });
            })
            ->orderBy('attendance_date', 'desc')
            ->get();

        // HRMS Enhanced Widgets
        $employeesByType = User::where('role', 'employee')
            ->select('employee_type', DB::raw('count(id) as count'))
            ->groupBy('employee_type')
            ->get();
            
        $onNoticePeriod = User::where('role', 'employee')
            ->where('employment_status', 'notice_period')
            ->count();
            
        $contractExpiryAlerts = \App\Models\EmployeeContract::with('employee')
            ->expiringSoon(30)
            ->get();
            
        $recentTerminations = \App\Models\EmployeeTermination::with('employee')
            ->where('last_working_date', '>=', Carbon::today()->subDays(30))
            ->orderBy('last_working_date', 'desc')
            ->get();
            
        $newJoiners = User::where('role', 'employee')
            ->where('joining_date', '>=', Carbon::today()->subDays(30))
            ->orderBy('joining_date', 'desc')
            ->get();

        return view('admin.dashboard', compact(
            'totalEmployees', 'presentToday', 'lateToday', 'halfDayToday',
            'absentToday', 'weeklyOff', 'notCheckedIn', 'stillWorking',
            'attendanceRate', 'recentCheckIns', 'departmentStats', 'dayName',
            'activeShiftsCount', 'pendingFaceResets', 'attendanceExceptions', 'shiftViolations',
            'employeesByType', 'onNoticePeriod', 'contractExpiryAlerts', 'recentTerminations', 'newJoiners'
        ));
    }

    /**
     * Interactive Central Reports Workspace.
     */
    public function index(Request $request)
    {
        // Filters
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate   = $request->get('end_date', Carbon::now()->endOfMonth()->toDateString());
        $departmentId = $request->get('department_id');
        $locationId   = $request->get('location_id');
        $employeeId   = $request->get('user_id');

        // Fetch filter lists
        $departments = Department::where('status', 'active')->get();
        $locations   = Location::where('status', 'active')->get();
        $employees   = User::where('role', 'employee')->where('status', 'active')->get();

        // Query Builder for Attendance
        $attendanceQuery = Attendance::with(['user.department', 'user.location', 'shift'])
            ->whereBetween('attendance_date', [$startDate, $endDate]);

        if ($departmentId) {
            $attendanceQuery->whereHas('user', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        if ($locationId) {
            $attendanceQuery->whereHas('user', function($q) use ($locationId) {
                $q->where('location_id', $locationId);
            });
        }

        if ($employeeId) {
            $attendanceQuery->where('user_id', $employeeId);
        }

        $attendances = $attendanceQuery->orderBy('attendance_date', 'desc')->get();

        // Query Builder for Biometric logs
        $biometricQuery = FaceRecognitionLog::with('user')
            ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);

        if ($departmentId) {
            $biometricQuery->whereHas('user', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        if ($locationId) {
            $biometricQuery->whereHas('user', function($q) use ($locationId) {
                $q->where('location_id', $locationId);
            });
        }

        if ($employeeId) {
            $biometricQuery->where('user_id', $employeeId);
        }

        $biometricLogs = $biometricQuery->orderBy('created_at', 'desc')->get();

        // 1. Employee Logs: $attendances itself is the collection

        // 2. Monthly Summary Collection
        $monthlySummary = $this->buildMonthlySummaryCollection($attendances, $startDate, $endDate);

        // 3. Shift policy assignments
        $shiftAssignments = ShiftAssignment::with(['employee.department', 'shift'])
            ->where(function($q) use ($startDate, $endDate) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $startDate);
            })
            ->where('effective_from', '<=', $endDate);

        if ($departmentId) {
            $shiftAssignments->whereHas('employee', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }
        if ($locationId) {
            $shiftAssignments->whereHas('employee', function($q) use ($locationId) {
                $q->where('location_id', $locationId);
            });
        }
        if ($employeeId) {
            $shiftAssignments->where('employee_id', $employeeId);
        }
        $shiftAssignments = $shiftAssignments->get();

        // 4. Late Marks
        $lateMarks = $attendances->where('status', 'late');

        // 5. Working Hours & Overtime/Undertime details
        $workingHoursLogs = $attendances->filter(function($r) {
            return $r->working_hours > 0;
        });

        // 6. Facial Recognition Audits -> $biometricLogs

        // 7. GPS/Geofencing Exceptions
        $gpsLogs = $attendances->filter(function($r) {
            return !empty($r->location) || $r->login_type === 'remote';
        });

        // Compute analytics for Chart.js
        $analytics = [
            'present_count'  => $attendances->where('status', 'present')->count(),
            'late_count'     => $attendances->where('status', 'late')->count(),
            'half_day_count' => $attendances->where('status', 'half_day')->count(),
            'absent_count'   => $attendances->where('status', 'absent')->count(),
        ];

        return view('admin.reports.index', compact(
            'startDate', 'endDate', 'departmentId', 'locationId', 'employeeId',
            'departments', 'locations', 'employees', 'attendances', 'monthlySummary',
            'shiftAssignments', 'lateMarks', 'workingHoursLogs', 'biometricLogs', 'gpsLogs', 'analytics'
        ));
    }

    /**
     * Provide attendance status records to feed FullCalendar.js.
     */
    public function calendarEvents(Request $request)
    {
        $start = $request->get('start');
        $end   = $request->get('end');
        $departmentId = $request->get('department_id');
        $locationId   = $request->get('location_id');
        $userId       = $request->get('user_id');

        $query = Attendance::with(['user.department', 'user.location', 'shift']);

        if ($start && $end) {
            $query->whereBetween('attendance_date', [$start, $end]);
        }

        if ($departmentId) {
            $query->whereHas('user', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        if ($locationId) {
            $query->whereHas('user', function($q) use ($locationId) {
                $q->where('location_id', $locationId);
            });
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $attendanceEvents = $query->get()->map(function($record) {
            $color = '#10b981';
            if ($record->status === 'late') {
                $color = '#f59e0b';
            } elseif ($record->status === 'half_day') {
                $color = '#8b5cf6';
            } elseif ($record->status === 'absent') {
                $color = '#ef4444';
            } elseif ($record->status === 'weekly_off') {
                $color = '#6b7280';
            }

            $checkInStr  = $record->check_in  ? Carbon::parse($record->check_in)->format('h:i A')  : 'N/A';
            $checkOutStr = $record->check_out ? Carbon::parse($record->check_out)->format('h:i A') : 'N/A';

            return [
                'id'     => $record->id,
                'title'  => ($record->user->name ?? 'Employee') . ' - ' . ucfirst($record->status),
                'start'  => $record->attendance_date,
                'color'  => $color,
                'allDay' => true,
                'extendedProps' => [
                    'employee'       => $record->user->name ?? 'N/A',
                    'employee_code'  => $record->user->employee_code ?? 'N/A',
                    'department'     => $record->user->department->department_name ?? 'N/A',
                    'location'       => $record->user->location->location_name ?? 'N/A',
                    'check_in'       => $checkInStr,
                    'check_out'      => $checkOutStr,
                    'working_hours'  => $record->working_hours ? $record->working_hours . ' hrs' : 'N/A',
                    'status'         => ucfirst($record->status),
                    'remarks'        => $record->remarks ?? 'No remarks',
                    'captured_image' => $record->image ? Storage::url($record->image) : null,
                    'distance_km'    => $record->distance_km !== null ? round($record->distance_km, 2) . ' km' : 'N/A',
                    'login_type'     => $record->login_type,
                ]
            ];
        });

        // Fetch holidays applicable in this date range
        $holidayQuery = Holiday::query();
        if ($start && $end) {
            $holidayQuery->whereBetween('holiday_date', [$start, $end]);
        }
        // Scope by location filter if provided
        if ($locationId) {
            $holidayQuery->where(function($q) use ($locationId) {
                $q->whereNull('location_id')
                  ->orWhere('location_id', $locationId);
            });
        }
        // Scope by department filter if provided
        if ($departmentId) {
            $holidayQuery->where(function($q) use ($departmentId) {
                $q->whereNull('department_id')
                  ->orWhere('department_id', $departmentId);
            });
        }

        $holidayEvents = $holidayQuery->get()->map(function($holiday) {
            $badge = $holiday->holiday_type === 'gazetted' ? '🎉' : '📅';
            return [
                'id'     => 'holiday_' . $holiday->id,
                'title'  => $badge . ' ' . $holiday->holiday_name,
                'start'  => $holiday->holiday_date->toDateString(),
                'color'  => '#f43f5e',
                'allDay' => true,
                'extendedProps' => [
                    'employee'       => $holiday->holiday_name,
                    'employee_code'  => 'Holiday',
                    'department'     => $holiday->department->department_name ?? 'All',
                    'location'       => $holiday->location->location_name ?? 'All',
                    'check_in'       => 'N/A',
                    'check_out'      => 'N/A',
                    'working_hours'  => 'N/A',
                    'status'         => ucfirst($holiday->holiday_type),
                    'remarks'        => 'Public Holiday - ' . $holiday->holiday_name,
                    'captured_image' => null,
                    'distance_km'    => 'N/A',
                ]
            ];
        });

        return response()->json($attendanceEvents->concat($holidayEvents)->values());
    }

    /**
     * Export dynamic CSV or printable HTML reports based on active filters.
     */
    public function export(Request $request, $format)
    {
        $type = $request->get('type', 'employee_logs');
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate   = $request->get('end_date', Carbon::now()->endOfMonth()->toDateString());
        $departmentId = $request->get('department_id');
        $locationId   = $request->get('location_id');
        $employeeId   = $request->get('user_id');

        // Fetch filtered attendances
        $query = Attendance::with(['user.department', 'user.location', 'shift'])
            ->whereBetween('attendance_date', [$startDate, $endDate]);

        if ($departmentId) {
            $query->whereHas('user', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }
        if ($locationId) {
            $query->whereHas('user', function($q) use ($locationId) {
                $q->where('location_id', $locationId);
            });
        }
        if ($employeeId) {
            $query->where('user_id', $employeeId);
        }

        $records = $query->orderBy('attendance_date', 'desc')->get();

        // 1. Browser Print / PDF View
        if ($format === 'pdf') {
            $title = ucwords(str_replace('_', ' ', $type)) . " Report (" . $startDate . " to " . $endDate . ")";
            
            // Build custom lists based on type
            $data = [];
            if ($type === 'employee_logs') {
                $data = $records;
            } elseif ($type === 'monthly_summary') {
                $data = $this->buildMonthlySummaryCollection($records, $startDate, $endDate)->map(function($item) {
                    return (object)$item;
                });
            } elseif ($type === 'late_marks') {
                $data = $records->where('status', 'late');
            } elseif ($type === 'working_hours') {
                $data = $records->filter(function($r) { return $r->working_hours > 0; });
            } elseif ($type === 'location_gps') {
                $data = $records->filter(function($r) { return !empty($r->location); });
            } elseif ($type === 'shifts') {
                $data = ShiftAssignment::with(['employee.department', 'shift'])
                    ->where(function($q) use ($startDate, $endDate) {
                        $q->whereNull('effective_to')
                          ->orWhere('effective_to', '>=', $startDate);
                    })
                    ->where('effective_from', '<=', $endDate)
                    ->get();
            } elseif ($type === 'face_recognition') {
                $logQuery = FaceRecognitionLog::with('user')
                    ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                if ($departmentId) {
                    $logQuery->whereHas('user', function($q) use ($departmentId) { $q->where('department_id', $departmentId); });
                }
                if ($employeeId) {
                    $logQuery->where('user_id', $employeeId);
                }
                $data = $logQuery->orderBy('created_at', 'desc')->get();
            }

            return view('admin.reports.print', compact('data', 'type', 'title', 'startDate', 'endDate'));
        }

        // 2. Stream CSV / Excel
        $filename = "report_" . $type . "_" . date('Ymd_His') . ($format === 'excel' ? '.xls' : '.csv');
        
        $headers = [
            "Content-type"        => $format === 'excel' ? "application/vnd.ms-excel" : "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($type, $records, $startDate, $endDate, $departmentId, $employeeId) {
            $file = fopen('php://output', 'w');

            if ($type === 'employee_logs') {
                // Header
                fputcsv($file, ['Date', 'Employee Code', 'Employee Name', 'Department', 'Location', 'Shift Name', 'Check In', 'Check Out', 'Working Hours (Hrs)', 'Status', 'Remarks']);
                foreach ($records as $row) {
                    fputcsv($file, [
                        $row->attendance_date,
                        $row->user->employee_code ?? 'N/A',
                        $row->user->name ?? 'N/A',
                        $row->user->department->department_name ?? 'N/A',
                        $row->location ?? 'N/A',
                        $row->shift->shift_name ?? 'Default',
                        $row->check_in ? Carbon::parse($row->check_in)->format('h:i A') : 'N/A',
                        $row->check_out ? Carbon::parse($row->check_out)->format('h:i A') : 'N/A',
                        $row->working_hours ?? '0.00',
                        ucfirst($row->status),
                        $row->remarks ?? ''
                    ]);
                }
            } elseif ($type === 'monthly_summary') {
                fputcsv($file, ['Employee Code', 'Employee Name', 'Department', 'Present Days', 'Late Marks', 'Half Days', 'Absent Days', 'Weekly Offs', 'Paid Leaves', 'Unpaid Leaves', 'Paid Days', 'Total Hours Worked', 'Overtime (Hrs)', 'Under-time (Hrs)']);
                
                $summary = $this->buildMonthlySummaryCollection($records, $startDate, $endDate);

                foreach ($summary as $row) {
                    fputcsv($file, [
                        $row['employee_code'],
                        $row['name'],
                        $row['department'],
                        $row['present'],
                        $row['late'],
                        $row['half_day'],
                        $row['absent'],
                        $row['weekly_offs'],
                        $row['paid_leaves'],
                        $row['unpaid_leaves'],
                        $row['paid_days'],
                        $row['total_hours'],
                        $row['overtime'],
                        $row['undertime']
                    ]);
                }
            } elseif ($type === 'late_marks') {
                fputcsv($file, ['Date', 'Employee Code', 'Employee Name', 'Department', 'Shift Name', 'Shift Start Time', 'Grace Allowed', 'Actual Check-in', 'Minutes Late', 'Remarks']);
                foreach ($records->where('status', 'late') as $row) {
                    $grace = $row->shift->grace_time_minutes ?? 0;
                    $shiftStart = $row->shift->start_time ?? 'N/A';
                    $actualIn = $row->check_in ? Carbon::parse($row->check_in)->format('h:i A') : 'N/A';
                    
                    $minutesLate = 0;
                    if ($row->check_in && $row->shift && $row->shift->start_time) {
                        $start = Carbon::createFromFormat('H:i:s', $row->shift->start_time);
                        $startToday = Carbon::parse($row->attendance_date)->setTime($start->hour, $start->minute);
                        $minutesLate = Carbon::parse($row->check_in)->diffInMinutes($startToday);
                    }

                    fputcsv($file, [
                        $row->attendance_date,
                        $row->user->employee_code ?? 'N/A',
                        $row->user->name ?? 'N/A',
                        $row->user->department->department_name ?? 'N/A',
                        $row->shift->shift_name ?? 'Default',
                        $shiftStart,
                        $grace . ' mins',
                        $actualIn,
                        $minutesLate,
                        $row->remarks ?? ''
                    ]);
                }
            } elseif ($type === 'working_hours') {
                fputcsv($file, ['Date', 'Employee Code', 'Employee Name', 'Department', 'Shift Name', 'Required Hours', 'Actual Hours Worked', 'Overtime (Hrs)', 'Under-time (Hrs)', 'Status']);
                foreach ($records->filter(function($r) { return $r->working_hours > 0; }) as $row) {
                    $minHrs = $row->shift->minimum_working_hours ?? 8.00;
                    $overtime = ($row->working_hours > $minHrs) ? round($row->working_hours - $minHrs, 2) : 0.00;
                    $undertime = ($row->working_hours < $minHrs) ? round($minHrs - $row->working_hours, 2) : 0.00;

                    fputcsv($file, [
                        $row->attendance_date,
                        $row->user->employee_code ?? 'N/A',
                        $row->user->name ?? 'N/A',
                        $row->user->department->department_name ?? 'N/A',
                        $row->shift->shift_name ?? 'Default',
                        $minHrs,
                        $row->working_hours,
                        $overtime,
                        $undertime,
                        ucfirst($row->status)
                    ]);
                }
            } elseif ($type === 'location_gps') {
                fputcsv($file, ['Date', 'Employee Code', 'Employee Name', 'Department', 'Assigned Location', 'Clock-in Coordinates', 'Distance from Office (KM)', 'Geofence Exception?', 'Remarks']);
                foreach ($records->filter(function($r) { return !empty($r->location); }) as $row) {
                    $distance = $row->distance_km !== null ? round($row->distance_km, 3) : 'N/A';
                    $exception = ($row->distance_km !== null && $row->distance_km > 0.2) ? 'YES' : 'NO';

                    fputcsv($file, [
                        $row->attendance_date,
                        $row->user->employee_code ?? 'N/A',
                        $row->user->name ?? 'N/A',
                        $row->user->department->department_name ?? 'N/A',
                        $row->user->location->location_name ?? 'N/A',
                        $row->location,
                        $distance,
                        $exception,
                        $row->remarks ?? ''
                    ]);
                }
            } elseif ($type === 'shifts') {
                fputcsv($file, ['Employee Code', 'Employee Name', 'Department', 'Assigned Shift Policy', 'Shift Type', 'Timing policy', 'Weekly Offs', 'Effective From', 'Effective To']);
                
                $assignments = ShiftAssignment::with(['employee.department', 'shift'])
                    ->where(function($q) use ($startDate, $endDate) {
                        $q->whereNull('effective_to')
                          ->orWhere('effective_to', '>=', $startDate);
                    })
                    ->where('effective_from', '<=', $endDate)
                    ->get();

                foreach ($assignments as $row) {
                    $offs = $row->shift ? implode(', ', $row->shift->weekly_off_days ?? []) : 'N/A';
                    $timing = $row->shift ? $row->shift->start_time . ' - ' . $row->shift->end_time : 'N/A';
                    fputcsv($file, [
                        $row->employee->employee_code ?? 'N/A',
                        $row->employee->name ?? 'N/A',
                        $row->employee->department->department_name ?? 'N/A',
                        $row->shift->shift_name ?? 'Default',
                        $row->shift->shift_type ?? 'N/A',
                        $timing,
                        $offs,
                        $row->effective_from,
                        $row->effective_to ?? 'Ongoing'
                    ]);
                }
            } elseif ($type === 'face_recognition') {
                fputcsv($file, ['Timestamp', 'Employee Code', 'Employee Name', 'Action Type', 'Confidence Score %', 'Liveness Passed?', 'Status', 'Remarks']);
                
                $logQuery = FaceRecognitionLog::with('user')
                    ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                if ($departmentId) {
                    $logQuery->whereHas('user', function($q) use ($departmentId) { $q->where('department_id', $departmentId); });
                }
                if ($employeeId) {
                    $logQuery->where('user_id', $employeeId);
                }
                $logs = $logQuery->orderBy('created_at', 'desc')->get();

                foreach ($logs as $row) {
                    fputcsv($file, [
                        $row->created_at->format('Y-m-d H:i:s'),
                        $row->user->employee_code ?? 'N/A',
                        $row->user->name ?? 'System/Unknown',
                        strtoupper($row->action_type),
                        $row->confidence_score . '%',
                        $row->liveness_passed ? 'YES' : 'NO',
                        strtoupper($row->status),
                        $row->remarks ?? ''
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Compute comprehensive monthly cumulative stats for attendance records.
     */
    private function buildMonthlySummaryCollection($records, $startDate, $endDate)
    {
        $allLeaves = \App\Models\LeaveApplication::where('status', 'approved')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('from_date', [$startDate, $endDate])
                      ->orWhereBetween('to_date', [$startDate, $endDate])
                      ->orWhere(function ($q) use ($startDate, $endDate) {
                          $q->where('from_date', '<=', $startDate)
                            ->where('to_date', '>=', $endDate);
                      });
            })
            ->get();

        $allHolidays = \App\Models\Holiday::whereBetween('holiday_date', [$startDate, $endDate])
            ->get();

        $allShiftAssignments = ShiftAssignment::with('shift')
            ->where('effective_from', '<=', $endDate)
            ->where(function($q) use ($startDate) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $startDate);
            })
            ->get();

        return $records->groupBy('user_id')->map(function($userRecords) use ($startDate, $endDate, $allLeaves, $allHolidays, $allShiftAssignments) {
            $firstRecord = $userRecords->first();
            $user = $firstRecord->user;
            
            $userLeaves = $allLeaves->where('employee_id', $user->id);
            $userHolidays = $allHolidays->filter(function ($h) use ($user) {
                return $h->location_id === null || $h->location_id == $user->location_id;
            })->pluck('holiday_date')->toArray();
            
            $userAssignment = $allShiftAssignments->where('employee_id', $user->id)->first();
            $weeklyOffs = $userAssignment && $userAssignment->shift 
                ? explode(',', $userAssignment->shift->weekly_off_days) 
                : ['Saturday', 'Sunday'];
            $weeklyOffs = array_map('trim', $weeklyOffs);
            
            $startDateObj = Carbon::parse($startDate);
            $endDateObj = Carbon::parse($endDate);
            $daysInPeriod = $startDateObj->diffInDays($endDateObj) + 1;
            
            $present = 0;
            $late = 0;
            $halfDay = 0;
            $absent = 0;
            $paidLeaves = 0;
            $unpaidLeaves = 0;
            $holidaysCount = 0;
            $weeklyOffsCount = 0;
            $totalHours = 0;
            $overtimeSum = 0;
            $undertimeSum = 0;
            
            for ($d = 0; $d < $daysInPeriod; $d++) {
                $currentDate = $startDateObj->copy()->addDays($d);
                $dateString = $currentDate->toDateString();
                $dayName = $currentDate->format('l');
                
                $att = $userRecords->firstWhere('attendance_date', $dateString);
                if ($att) {
                    $totalHours += $att->working_hours;
                    if ($att->status == 'half_day') {
                        $halfDay++;
                    } elseif ($att->status == 'late') {
                        $late++;
                        $present++;
                    } elseif ($att->status == 'present') {
                        $present++;
                    }
                    
                    if ($att->working_hours && $att->shift) {
                        if ($att->working_hours > $att->shift->minimum_working_hours) {
                            $overtimeSum += ($att->working_hours - $att->shift->minimum_working_hours);
                        } elseif ($att->working_hours < $att->shift->minimum_working_hours && $att->working_hours > $att->shift->half_day_time) {
                            $undertimeSum += ($att->shift->minimum_working_hours - $att->working_hours);
                        }
                    }
                    continue;
                }
                
                // Check approved leaves
                $approvedLeave = null;
                foreach ($userLeaves as $lv) {
                    if ($dateString >= $lv->from_date && $dateString <= $lv->to_date) {
                        $approvedLeave = $lv;
                        break;
                    }
                }
                
                if ($approvedLeave) {
                    $policy = \App\Models\LeavePolicy::find($approvedLeave->leave_policy_id);
                    if ($policy && $policy->leave_type == 'paid') {
                        $paidLeaves++;
                    } else {
                        $unpaidLeaves++;
                    }
                    continue;
                }
                
                // Check Holidays
                if (in_array($dateString, $userHolidays)) {
                    $holidaysCount++;
                    continue;
                }
                
                // Check Weekly Offs
                if (in_array($dayName, $weeklyOffs)) {
                    $weeklyOffsCount++;
                    continue;
                }
                
                // Absent
                $absent++;
            }
            
            $paidDays = $daysInPeriod - $unpaidLeaves - $absent - ($halfDay * 0.5);
            
            return [
                'employee_code' => $user->employee_code ?? 'N/A',
                'name' => $user->name,
                'department' => $user->department->department_name ?? 'N/A',
                'present' => $present,
                'late' => $late,
                'half_day' => $halfDay,
                'absent' => $absent,
                'weekly_offs' => $weeklyOffsCount,
                'paid_leaves' => $paidLeaves,
                'unpaid_leaves' => $unpaidLeaves,
                'paid_days' => $paidDays,
                'total_hours' => round($totalHours, 2),
                'overtime' => round($overtimeSum, 2),
                'undertime' => round($undertimeSum, 2),
            ];
        })->values();
    }
}
