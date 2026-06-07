<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use Carbon\Carbon;

class ProcessDailyAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:process-daily {--date= : The date to process (Y-m-d). Defaults to today.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process daily attendance: mark absent for no check-in, resolve incomplete check-outs, and handle weekly offs';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dateStr = $this->option('date') ?? Carbon::today()->toDateString();
        $date = Carbon::parse($dateStr);
        $dayName = $date->format('l'); // e.g. "Saturday"

        $this->info("========================================");
        $this->info("Attendance Auto-Processor");
        $this->info("Processing date: {$dateStr} ({$dayName})");
        $this->info("========================================");

        $employees = User::where('role', 'employee')
            ->where('status', 'active')
            ->get();

        $stats = [
            'absent'     => 0,
            'weekly_off' => 0,
            'on_leave'   => 0,
            'incomplete' => 0,
            'skipped'    => 0,
        ];

        foreach ($employees as $employee) {
            $attendance = Attendance::where('user_id', $employee->id)
                ->where('attendance_date', $dateStr)
                ->first();

            // Get Active Shift Assignment
            $assignment = ShiftAssignment::with('shift')
                ->where('employee_id', $employee->id)
                ->where('effective_from', '<=', $dateStr)
                ->where(function ($q) use ($dateStr) {
                    $q->whereNull('effective_to')
                      ->orWhere('effective_to', '>=', $dateStr);
                })
                ->first();

            $shift = $assignment ? $assignment->shift : null;

            // 1. Approved Leave check
            $approvedLeave = \App\Models\LeaveApplication::with('leavePolicy')
                ->where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->where('from_date', '<=', $dateStr)
                ->where('to_date', '>=', $dateStr)
                ->first();

            if ($approvedLeave) {
                // Determine start and end times based on shift or fallback to default
                $checkInTime = null;
                $checkOutTime = null;
                $workingHours = 0;

                if ($shift && $shift->start_time && $shift->end_time) {
                    $checkInTime = Carbon::parse($dateStr . ' ' . $shift->start_time);
                    $checkOutTime = Carbon::parse($dateStr . ' ' . $shift->end_time);
                    if ($shift->shift_type === 'night' && $checkOutTime->lessThan($checkInTime)) {
                        $checkOutTime->addDay();
                    }
                    $workingHours = round($checkInTime->diffInMinutes($checkOutTime) / 60, 2);
                } else {
                    $checkInTime = Carbon::parse($dateStr . ' 09:00:00');
                    $checkOutTime = Carbon::parse($dateStr . ' 17:00:00');
                    $workingHours = 8.00;
                }

                Attendance::updateOrCreate(
                    ['user_id' => $employee->id, 'attendance_date' => $dateStr],
                    [
                        'check_in'      => $checkInTime,
                        'check_out'     => $checkOutTime,
                        'working_hours' => $workingHours,
                        'shift_id'      => $shift ? $shift->id : null,
                        'status'        => 'on_leave',
                        'remarks'       => 'On Leave: ' . ($approvedLeave->leavePolicy->leave_name ?? 'Approved Leave') . '. Reason: ' . $approvedLeave->reason,
                    ]
                );
                
                $stats['on_leave']++;
                $this->line("  [{$employee->name}] -> On Leave (" . ($approvedLeave->leavePolicy->leave_code ?? 'Leave') . ")");
                continue;
            }

            // 2. Check if it is a weekly off day for this employee's shift
            if ($shift && $shift->weekly_off_days) {
                $offDays = array_map('trim', explode(',', $shift->weekly_off_days));
                if (in_array($dayName, $offDays)) {
                    // Mark or update as weekly off
                    Attendance::updateOrCreate(
                        ['user_id' => $employee->id, 'attendance_date' => $dateStr],
                        [
                            'status'    => 'weekly_off',
                            'shift_id'  => $shift->id,
                            'remarks'   => "Weekly Off ({$dayName})",
                        ]
                    );
                    $stats['weekly_off']++;
                    $this->line("  [{$employee->name}] -> Weekly Off ({$dayName})");
                    continue;
                }
            }

            // 3. If no attendance record exists -> Mark absent
            if (!$attendance) {
                Attendance::create([
                    'user_id'         => $employee->id,
                    'attendance_date' => $dateStr,
                    'shift_id'        => $shift ? $shift->id : null,
                    'status'          => 'absent',
                    'remarks'         => 'Auto-marked absent: No check-in recorded',
                ]);
                $stats['absent']++;
                $this->line("  [{$employee->name}] -> Absent (No check-in)");
                continue;
            }

            // 4. If checked in but did not check out -> incomplete session
            if ($attendance->check_in && !$attendance->check_out) {
                $checkoutMandatory = \App\Models\Setting::get('checkout_mandatory', '1') == '1';
                $checkIn = Carbon::parse($attendance->check_in);
                $estimatedCheckOut = null;
                $workingHours = 0;

                if ($shift && $shift->end_time) {
                    $estimatedCheckOut = Carbon::parse($dateStr . ' ' . $shift->end_time);
                    if ($shift->shift_type === 'night' && $estimatedCheckOut->lessThan($checkIn)) {
                        $estimatedCheckOut->addDay();
                    }
                    $workingHours = round($checkIn->diffInMinutes($estimatedCheckOut) / 60, 2);
                } else {
                    $estimatedCheckOut = $checkIn->copy()->addHours(8);
                    $workingHours = 8.00;
                }

                if (!$checkoutMandatory) {
                    // Automatically checkout
                    $attendance->check_out = $estimatedCheckOut;
                    $attendance->working_hours = $workingHours;
                    if ($attendance->status !== 'late') {
                        $attendance->status = 'present';
                    }
                    $attendance->remarks = ($attendance->remarks ? $attendance->remarks . ' | ' : '') . "Auto-checked out: checkout policy non-mandatory.";
                    $attendance->save();
                    
                    $stats['incomplete']++;
                    $this->line("  [{$employee->name}] -> Missing Checkout Auto-completed -> {$attendance->status}");
                    continue;
                }

                // If checkout IS mandatory, run normal penalization (absent/half_day)
                if ($shift) {
                    if ($workingHours < $shift->half_day_time) {
                        $attendance->status = 'absent';
                        $attendance->remarks = "Auto-processed: Incomplete check-out. Worked ~{$workingHours} hrs (Below half-day threshold)";
                    } elseif ($workingHours < $shift->minimum_working_hours) {
                        $attendance->status = 'half_day';
                        $attendance->remarks = "Auto-processed: Incomplete check-out. Worked ~{$workingHours} hrs (Half-Day)";
                    } else {
                        // Keep existing status (late/present)
                        $attendance->remarks = ($attendance->remarks ? $attendance->remarks . ' | ' : '') . "Auto-processed: Missing check-out. Estimated ~{$workingHours} hrs.";
                    }
                } else {
                    // Default policy
                    if ($workingHours < 4) {
                        $attendance->status = 'absent';
                        $attendance->remarks = "Auto-processed: Missing check-out. Estimated work < 4 hrs.";
                    } elseif ($workingHours < 8) {
                        $attendance->status = 'half_day';
                        $attendance->remarks = "Auto-processed: Missing check-out. Estimated work < 8 hrs.";
                    }
                }

                $attendance->working_hours = $workingHours;
                $attendance->save();

                $stats['incomplete']++;
                $this->line("  [{$employee->name}] -> Incomplete (No check-out) -> {$attendance->status}");
                continue;
            }

            // Already fully processed (check-in + check-out present)
            $stats['skipped']++;
        }

        $this->newLine();
        $this->info("Processing Complete!");
        $this->table(
            ['Status', 'Count'],
            [
                ['Marked Absent',       $stats['absent']],
                ['Weekly Off',          $stats['weekly_off']],
                ['On Leave',            $stats['on_leave']],
                ['Incomplete Resolved', $stats['incomplete']],
                ['Already Processed',   $stats['skipped']],
                ['Total Employees',     $employees->count()],
            ]
        );

        return Command::SUCCESS;
    }
}
