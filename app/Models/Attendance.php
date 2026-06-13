<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shift_id',
        'check_in',
        'check_out',
        'working_hours',
        'status',
        'attendance_date',
        'location',
        'login_type',
        'image',
        'remarks',
        'method_used',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the shift active during this attendance registration.
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get the regularization requests associated with this attendance record.
     */
    public function regularizations()
    {
        return $this->hasMany(AttendanceRegularization::class, 'attendance_id');
    }

    /**
     * Calculate check-in status based on shift type and time.
     */
    public static function calculateCheckInStatus(Shift $shift, Carbon $now): array
    {
        if (!$shift->start_time) {
            return ['status' => 'present', 'remarks' => 'Shift checked (no start time configured)'];
        }

        switch ($shift->shift_type) {
            case 'general':
                $shiftStart = Carbon::createFromFormat('H:i:s', $shift->start_time);
                $shiftStartToday = Carbon::parse($now->toDateString())->setTime($shiftStart->hour, $shiftStart->minute, $shiftStart->second);
                $graceDeadline = $shiftStartToday->copy()->addMinutes($shift->grace_time_minutes);

                if ($now->greaterThan($graceDeadline)) {
                    $minutesLate = $now->diffInMinutes($shiftStartToday);
                    return [
                        'status'  => 'late',
                        'remarks' => "Late Check-in by {$minutesLate} minutes (Grace: {$shift->grace_time_minutes} mins)",
                    ];
                }
                return ['status' => 'present', 'remarks' => 'On-time Check-in'];

            case 'night':
                $shiftStart = Carbon::createFromFormat('H:i:s', $shift->start_time);
                if ($now->hour < 12 && $shiftStart->hour >= 12) {
                    $shiftStartDate = Carbon::parse($now->toDateString())->subDay()->setTime($shiftStart->hour, $shiftStart->minute, $shiftStart->second);
                } else {
                    $shiftStartDate = Carbon::parse($now->toDateString())->setTime($shiftStart->hour, $shiftStart->minute, $shiftStart->second);
                }
                $graceDeadline = $shiftStartDate->copy()->addMinutes($shift->grace_time_minutes);

                if ($now->greaterThan($graceDeadline)) {
                    $minutesLate = $now->diffInMinutes($shiftStartDate);
                    return [
                        'status'  => 'late',
                        'remarks' => "Night Shift: Late Check-in by {$minutesLate} minutes (Grace: {$shift->grace_time_minutes} mins)",
                    ];
                }
                return ['status' => 'present', 'remarks' => 'Night Shift: On-time Check-in'];

            case 'rotational':
                $shiftStart = Carbon::createFromFormat('H:i:s', $shift->start_time);
                if ($shift->end_time) {
                    $shiftEnd = Carbon::createFromFormat('H:i:s', $shift->end_time);
                    $crossesMidnight = $shiftEnd->lessThan($shiftStart);

                    if ($crossesMidnight && $now->hour < 12 && $shiftStart->hour >= 12) {
                        $shiftStartDate = Carbon::parse($now->toDateString())->subDay()->setTime($shiftStart->hour, $shiftStart->minute, $shiftStart->second);
                    } else {
                        $shiftStartDate = Carbon::parse($now->toDateString())->setTime($shiftStart->hour, $shiftStart->minute, $shiftStart->second);
                    }
                } else {
                    $shiftStartDate = Carbon::parse($now->toDateString())->setTime($shiftStart->hour, $shiftStart->minute, $shiftStart->second);
                }
                $graceDeadline = $shiftStartDate->copy()->addMinutes($shift->grace_time_minutes);

                if ($now->greaterThan($graceDeadline)) {
                    $minutesLate = $now->diffInMinutes($shiftStartDate);
                    return [
                        'status'  => 'late',
                        'remarks' => "Rotational Shift: Late Check-in by {$minutesLate} minutes (Grace: {$shift->grace_time_minutes} mins)",
                    ];
                }
                return ['status' => 'present', 'remarks' => 'Rotational Shift: On-time Check-in'];

            case 'flexible':
                return [
                    'status'  => 'present',
                    'remarks' => 'Flexible shift: No fixed timing policy. Checked in at ' . $now->format('h:i A'),
                ];

            default:
                return [
                    'status'  => 'present',
                    'remarks' => 'Unknown shift type. Default check-in applied.',
                ];
        }
    }

    /**
     * Calculate status and working hours on checkOut or regularization.
     * $checkOut may be null for cases where the employee has no recorded check-out.
     */
    public static function calculateStatusAndHours(Carbon $checkIn, ?Carbon $checkOut, $shift = null)
    {
        // If no check-out, we cannot calculate working hours — treat as absent
        if (is_null($checkOut)) {
            return [
                'working_hours' => 0,
                'status'        => 'absent',
                'remarks'       => 'Absent: No check-out recorded for this attendance period.',
            ];
        }

        $workingHours = round($checkIn->diffInMinutes($checkOut) / 60, 2);
        
        // Determine baseline status/remarks from check-in
        $status = 'present';
        $remarks = '';
        
        if ($shift) {
            $checkInStatus = self::calculateCheckInStatus($shift, $checkIn);
            $status = $checkInStatus['status'];
            $remarks = $checkInStatus['remarks'];
            
            $overtime = 0.00;
            $undertime = 0.00;
            $statusRemarks = "";
            
            if ($workingHours > $shift->minimum_working_hours) {
                $overtime = round($workingHours - $shift->minimum_working_hours, 2);
                $statusRemarks .= " Overtime: {$overtime} hrs.";
            } elseif ($workingHours < $shift->minimum_working_hours && $workingHours > $shift->half_day_time) {
                $undertime = round($shift->minimum_working_hours - $workingHours, 2);
                $statusRemarks .= " Under-time: {$undertime} hrs.";
            }

            if ($workingHours < $shift->half_day_time) {
                $status = 'absent';
                $remarks = "Absent: Worked only {$workingHours} hrs. (Required {$shift->half_day_time} hrs for Half-Day)";
            } elseif ($workingHours < $shift->minimum_working_hours) {
                $status = 'half_day';
                $remarks = "Half-Day: Worked {$workingHours} hrs. (Required {$shift->minimum_working_hours} hrs for Full Day)" . $statusRemarks;
            } else {
                // If it was late check-in, keep it late, otherwise present
                if ($status !== 'late') {
                    $status = 'present';
                }
                $remarks = ($remarks ? $remarks . " | " : "") . "Clocked out successfully. Total shift: {$workingHours} hrs." . $statusRemarks;
            }
        } else {
            // Default shift (8 hrs required for full day, 4 for half day)
            if ($workingHours < 4) {
                $status = 'absent';
                $remarks = "Absent: Default threshold not met (Worked < 4 hrs)";
            } elseif ($workingHours < 8) {
                $status = 'half_day';
                $remarks = "Half-Day: Worked < 8 hrs | Under-time: " . round(8 - $workingHours, 2) . " hrs.";
            } else {
                $status = 'present';
                $remarks = "On-time default shift completion | Overtime: " . round($workingHours - 8, 2) . " hrs.";
            }
        }
        
        return [
            'working_hours' => $workingHours,
            'status' => $status,
            'remarks' => $remarks
        ];
    }
}

