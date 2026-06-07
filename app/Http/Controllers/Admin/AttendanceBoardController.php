<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use App\Models\ShiftAssignment;
use App\Models\Department;
use App\Models\Location;
use App\Models\Holiday;
use App\Models\LeaveApplication;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceBoardController extends Controller
{
    public function index(Request $request)
    {
        $mode = $request->get('mode', 'daily'); // 'daily', 'weekly', 'monthly'
        $departmentId = $request->get('department_id');
        $locationId = $request->get('location_id');

        // Fetch lists for filters
        $departments = Department::where('status', 'active')->orderBy('department_name')->get();
        $locations = Location::where('status', 'active')->orderBy('location_name')->get();

        // 1. Determine date params based on mode
        $targetDateStr = $request->get('date', Carbon::today()->toDateString());
        $targetDate = Carbon::parse($targetDateStr);

        // Fetch all active employees
        $employeeQuery = User::where('role', 'employee')
            ->where('status', 'active')
            ->with(['department', 'location']);

        if ($departmentId) {
            $employeeQuery->where('department_id', $departmentId);
        }
        if ($locationId) {
            $employeeQuery->where('location_id', $locationId);
        }
        $employees = $employeeQuery->orderBy('name')->get();

        if ($mode === 'daily') {
            return $this->handleDailyBoard($employees, $targetDate, $departments, $locations, $departmentId, $locationId);
        } elseif ($mode === 'weekly') {
            return $this->handleWeeklyBoard($employees, $targetDate, $departments, $locations, $departmentId, $locationId);
        } else {
            return $this->handleMonthlyBoard($employees, $targetDate, $departments, $locations, $departmentId, $locationId);
        }
    }

    /**
     * Render the Daily Board View
     */
    private function handleDailyBoard($employees, $date, $departments, $locations, $departmentId, $locationId)
    {
        $dateStr = $date->toDateString();
        
        // Fetch all attendance records for this date
        $attendances = Attendance::with('shift')
            ->where('attendance_date', $dateStr)
            ->get()
            ->keyBy('user_id');

        // Fetch holidays for this date
        $holidays = Holiday::where('holiday_date', $dateStr)->get();

        // Fetch approved leaves covering this date
        $leaves = LeaveApplication::where('status', 'approved')
            ->where('from_date', '<=', $dateStr)
            ->where('to_date', '>=', $dateStr)
            ->get()
            ->keyBy('employee_id');

        // Fetch active shift assignments covering this date
        $shiftAssignments = ShiftAssignment::with('shift')
            ->where('effective_from', '<=', $dateStr)
            ->where(function($q) use ($dateStr) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $dateStr);
            })
            ->get()
            ->keyBy('employee_id');

        $boardData = [];
        $kpis = [
            'total' => $employees->count(),
            'present' => 0,
            'late' => 0,
            'half_day' => 0,
            'absent' => 0,
            'weekly_off' => 0,
            'leave' => 0,
            'holiday' => 0,
            'still_working' => 0,
        ];

        $dayName = $date->format('l');

        foreach ($employees as $employee) {
            $record = $attendances->get($employee->id);
            $leave = $leaves->get($employee->id);
            
            // Resolve Weekly Offs
            $assignment = $shiftAssignments->get($employee->id);
            $weeklyOffs = $assignment && $assignment->shift 
                ? explode(',', $assignment->shift->weekly_off_days) 
                : ['Saturday', 'Sunday'];
            $weeklyOffs = array_map('trim', array_map('strtolower', $weeklyOffs));
            $isWeeklyOff = in_array(strtolower($dayName), $weeklyOffs);

            // Resolve Holidays
            $locHoliday = $holidays->filter(function($h) use ($employee) {
                return $h->location_id === null || $h->location_id == $employee->location_id;
            })->first();

            $status = 'absent';
            $checkIn = null;
            $checkOut = null;
            $workingHours = 0;
            $remarks = null;
            $shiftName = $assignment && $assignment->shift ? $assignment->shift->shift_name : 'Default Shift';
            $shiftTimings = $assignment && $assignment->shift 
                ? substr($assignment->shift->start_time, 0, 5) . ' - ' . substr($assignment->shift->end_time, 0, 5) 
                : '09:00 - 17:00';

            if ($record) {
                $status = $record->status; // present, late, half_day, weekly_off, absent
                $checkIn = $record->check_in ? Carbon::parse($record->check_in)->format('h:i A') : '—';
                $checkOut = $record->check_out ? Carbon::parse($record->check_out)->format('h:i A') : '—';
                $workingHours = $record->working_hours ?? 0;
                $remarks = $record->remarks;
                if ($record->check_in && !$record->check_out) {
                    $kpis['still_working']++;
                }

                if ($status === 'present') $kpis['present']++;
                elseif ($status === 'late') $kpis['late']++;
                elseif ($status === 'half_day') $kpis['half_day']++;
                elseif ($status === 'absent') $kpis['absent']++;
                elseif ($status === 'weekly_off') $kpis['weekly_off']++;
            } elseif ($leave) {
                $status = 'leave';
                $remarks = "On Approved Leave: " . $leave->reason;
                $kpis['leave']++;
            } elseif ($locHoliday) {
                $status = 'holiday';
                $remarks = "Public Holiday: " . $locHoliday->holiday_name;
                $kpis['holiday']++;
            } elseif ($isWeeklyOff) {
                $status = 'weekly_off';
                $remarks = "Weekly Off Day";
                $kpis['weekly_off']++;
            } else {
                // If it's today and shift hasn't started or we are in the future, don't mark as strictly absent
                if ($date->isFuture() || ($date->isToday() && Carbon::now()->format('H:i:s') < ($assignment && $assignment->shift ? $assignment->shift->start_time : '09:00:00'))) {
                    $status = 'pending';
                    $remarks = "Shift Not Yet Started";
                } else {
                    $status = 'absent';
                    $remarks = "Unexcused Absence";
                    $kpis['absent']++;
                }
            }

            $boardData[] = [
                'employee' => $employee,
                'status' => $status,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'raw_check_in' => $record && $record->check_in ? Carbon::parse($record->check_in)->format('H:i') : '',
                'raw_check_out' => $record && $record->check_out ? Carbon::parse($record->check_out)->format('H:i') : '',
                'working_hours' => $workingHours,
                'shift_name' => $shiftName,
                'shift_timings' => $shiftTimings,
                'remarks' => $remarks,
                'gps_distance' => $record ? $record->distance_km : null,
                'gps_location' => $record ? $record->location : null,
            ];
        }

        return view('admin.attendance.board', [
            'mode' => 'daily',
            'targetDate' => $date,
            'departments' => $departments,
            'locations' => $locations,
            'departmentId' => $departmentId,
            'locationId' => $locationId,
            'boardData' => $boardData,
            'kpis' => $kpis,
        ]);
    }

    /**
     * Render the Weekly Board Grid (Cross-tabulation)
     */
    private function handleWeeklyBoard($employees, $date, $departments, $locations, $departmentId, $locationId)
    {
        // Calculate start & end of week (Monday to Sunday)
        $startOfWeek = $date->copy()->startOfWeek();
        $endOfWeek = $date->copy()->endOfWeek();

        // Build array of Carbon date objects for the week
        $dates = [];
        for ($i = 0; $i < 7; $i++) {
            $dates[] = $startOfWeek->copy()->addDays($i);
        }

        $gridData = $this->buildCrossTabGrid($employees, $dates, $startOfWeek->toDateString(), $endOfWeek->toDateString());

        return view('admin.attendance.board', [
            'mode' => 'weekly',
            'targetDate' => $date,
            'startOfWeek' => $startOfWeek,
            'endOfWeek' => $endOfWeek,
            'departments' => $departments,
            'locations' => $locations,
            'departmentId' => $departmentId,
            'locationId' => $locationId,
            'dates' => $dates,
            'gridData' => $gridData,
        ]);
    }

    /**
     * Render the Monthly Board Grid (Cross-tabulation)
     */
    private function handleMonthlyBoard($employees, $date, $departments, $locations, $departmentId, $locationId)
    {
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        $daysInMonth = $date->daysInMonth;
        $dates = [];
        for ($i = 0; $i < $daysInMonth; $i++) {
            $dates[] = $startOfMonth->copy()->addDays($i);
        }

        $gridData = $this->buildCrossTabGrid($employees, $dates, $startOfMonth->toDateString(), $endOfMonth->toDateString());

        return view('admin.attendance.board', [
            'mode' => 'monthly',
            'targetDate' => $date,
            'departments' => $departments,
            'locations' => $locations,
            'departmentId' => $departmentId,
            'locationId' => $locationId,
            'dates' => $dates,
            'gridData' => $gridData,
        ]);
    }

    /**
     * Core logic to build a cross-tab grid for dates range
     */
    private function buildCrossTabGrid($employees, $dates, $startRange, $endRange)
    {
        // 1. Fetch all attendance logs in date range
        $attendances = Attendance::with('shift')
            ->whereBetween('attendance_date', [$startRange, $endRange])
            ->get()
            ->groupBy('user_id');

        // 2. Fetch all approved leaves in date range
        $leaves = LeaveApplication::where('status', 'approved')
            ->where(function($q) use ($startRange, $endRange) {
                $q->whereBetween('from_date', [$startRange, $endRange])
                  ->orWhereBetween('to_date', [$startRange, $endRange])
                  ->orWhere(function($sub) use ($startRange, $endRange) {
                      $sub->where('from_date', '<=', $startRange)
                          ->where('to_date', '>=', $endRange);
                  });
            })
            ->get()
            ->groupBy('employee_id');

        // 3. Fetch all holidays in date range
        $holidays = Holiday::whereBetween('holiday_date', [$startRange, $endRange])
            ->get()
            ->groupBy('holiday_date');

        // 4. Fetch all active shift assignments
        $shiftAssignments = ShiftAssignment::with('shift')
            ->where('effective_from', '<=', $endRange)
            ->where(function($q) use ($startRange) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $startRange);
            })
            ->get()
            ->groupBy('employee_id');

        $grid = [];

        foreach ($employees as $employee) {
            $employeeAtts = $attendances->get($employee->id, collect())->keyBy('attendance_date');
            $employeeLeaves = $leaves->get($employee->id, collect());
            $employeeShifts = $shiftAssignments->get($employee->id, collect());

            $dailyStatuses = [];

            foreach ($dates as $dateObj) {
                $dateStr = $dateObj->toDateString();
                $dayName = $dateObj->format('l');

                $record = $employeeAtts->get($dateStr);
                
                // Find approved leave
                $leave = $employeeLeaves->filter(function($l) use ($dateStr) {
                    return $l->from_date <= $dateStr && $l->to_date >= $dateStr;
                })->first();

                // Find holiday
                $holiday = $holidays->get($dateStr, collect())->filter(function($h) use ($employee) {
                    return $h->location_id === null || $h->location_id == $employee->location_id;
                })->first();

                // Find shift assignment
                $assignment = $employeeShifts->filter(function($sa) use ($dateStr) {
                    return $sa->effective_from <= $dateStr && (is_null($sa->effective_to) || $sa->effective_to >= $dateStr);
                })->first();

                // Resolve Weekly Offs
                $weeklyOffs = $assignment && $assignment->shift 
                    ? explode(',', $assignment->shift->weekly_off_days) 
                    : ['Saturday', 'Sunday'];
                $weeklyOffs = array_map('trim', array_map('strtolower', $weeklyOffs));
                $isWeeklyOff = in_array(strtolower($dayName), $weeklyOffs);

                $status = 'absent';
                $details = null;

                if ($record) {
                    $status = $record->status;
                    $checkInStr = $record->check_in ? Carbon::parse($record->check_in)->format('h:i A') : 'N/A';
                    $checkOutStr = $record->check_out ? Carbon::parse($record->check_out)->format('h:i A') : 'N/A';
                    $details = "Clocked In: " . $checkInStr . "\nClocked Out: " . $checkOutStr . "\nHours Worked: " . ($record->working_hours ?? '—') . "h";
                } elseif ($leave) {
                    $status = 'leave';
                    $details = "Leave Application Approved\nReason: " . $leave->reason;
                } elseif ($holiday) {
                    $status = 'holiday';
                    $details = "Public Holiday: " . $holiday->holiday_name;
                } elseif ($isWeeklyOff) {
                    $status = 'weekly_off';
                    $details = "Assigned Weekly Off Day";
                } else {
                    if ($dateObj->isFuture() || ($dateObj->isToday() && Carbon::now()->format('H:i:s') < ($assignment && $assignment->shift ? $assignment->shift->start_time : '09:00:00'))) {
                        $status = 'pending';
                        $details = "Shift not yet scheduled / future date";
                    } else {
                        $status = 'absent';
                        $details = "No clock-in record found (Absent)";
                    }
                }

                $dailyStatuses[$dateStr] = [
                    'status' => $status,
                    'details' => $details,
                ];
            }

            $grid[] = [
                'employee' => $employee,
                'days' => $dailyStatuses,
            ];
        }

        return $grid;
    }

    /**
     * Manually update or create an attendance record from the Daily Board.
     */
    public function manualUpdate(Request $request)
    {
        $request->validate([
            'employee_id'     => 'required|exists:users,id',
            'attendance_date' => 'required|date',
            'status'          => 'required|string|in:present,late,half_day,absent,weekly_off,leave,holiday',
            'check_in'        => 'nullable|date_format:H:i',
            'check_out'       => 'nullable|date_format:H:i',
            'remarks'         => 'nullable|string|max:500',
        ]);

        $employeeId = $request->employee_id;
        $dateStr = $request->attendance_date;

        DB::beginTransaction();

        try {
            // Find or create attendance record
            $attendance = Attendance::where('user_id', $employeeId)
                ->where('attendance_date', $dateStr)
                ->first();

            if (!$attendance) {
                $attendance = new Attendance();
                $attendance->user_id         = $employeeId;
                $attendance->attendance_date = $dateStr;
            }

            // Set check-in & check-out datetimes if times are provided
            $checkInTime = null;
            $checkOutTime = null;

            if ($request->filled('check_in')) {
                $checkInTime = Carbon::parse($dateStr . ' ' . $request->check_in);
                $attendance->check_in = $checkInTime;
            } else {
                $attendance->check_in = null;
            }

            if ($request->filled('check_out')) {
                $checkOutTime = Carbon::parse($dateStr . ' ' . $request->check_out);
                $attendance->check_out = $checkOutTime;
            } else {
                $attendance->check_out = null;
            }

            // Calculate working hours if both check-in and check-out are set
            if ($checkInTime && $checkOutTime) {
                if ($checkOutTime->lessThan($checkInTime)) {
                    return redirect()->back()->with('error', 'Clock-out time cannot be before Clock-in time.');
                }
                $attendance->working_hours = round($checkInTime->diffInMinutes($checkOutTime) / 60, 2);
            } else {
                $attendance->working_hours = 0.00;
            }

            // Save the admin-specified status & remarks
            $attendance->status = $request->status;
            $attendance->remarks = $request->remarks ?: 'Manually updated by Admin';

            // Resolve shift for the target date if not set
            if (!$attendance->shift_id) {
                $assignment = ShiftAssignment::where('employee_id', $employeeId)
                    ->where('effective_from', '<=', $dateStr)
                    ->where(function ($q) use ($dateStr) {
                        $q->whereNull('effective_to')
                          ->orWhere('effective_to', '>=', $dateStr);
                    })
                    ->first();
                if ($assignment) {
                    $attendance->shift_id = $assignment->shift_id;
                }
            }

            $attendance->save();

            // Log change in audit logs
            \App\Models\AuditLog::create([
                'user_id'     => auth()->id(),
                'module'      => 'attendance_board',
                'action'      => 'manual_attendance_update',
                'new_data'    => $attendance->toArray(),
                'ip_address'  => $request->ip(),
                'device_info' => $request->userAgent(),
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Attendance record manually updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to update attendance record: ' . $e->getMessage());
        }
    }
}
