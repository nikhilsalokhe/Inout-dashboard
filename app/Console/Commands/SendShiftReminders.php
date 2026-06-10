<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Attendance;
use App\Models\ShiftAssignment;
use App\Models\LeaveApplication;
use App\Models\Notification;
use App\Services\FirebaseService;
use Carbon\Carbon;

class SendShiftReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send push notifications 10 minutes before work starts and 10 minutes after shift ends';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $today = Carbon::today()->toDateString();
        $dayName = Carbon::today()->format('l');

        // 10 minutes before shift starts
        $targetStartStr = Carbon::now()->addMinutes(10)->format('H:i');
        // 10 minutes after shift ends
        $targetEndStr = Carbon::now()->subMinutes(10)->format('H:i');

        $this->info("========================================");
        $this->info("Attendance Shift Reminder Job");
        $this->info("Current Time: " . Carbon::now()->toDateTimeString());
        $this->info("Target Start Shift (Time + 10m): {$targetStartStr}");
        $this->info("Target End Shift (Time - 10m): {$targetEndStr}");
        $this->info("========================================");

        $firebaseService = new FirebaseService();

        // --- PART 1: Work Start Reminders (10m before shift starts) ---
        $this->info("Checking for shifts starting in 10 minutes (at {$targetStartStr})...");
        
        $startAssignments = ShiftAssignment::with(['shift', 'employee'])
            ->where('effective_from', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $today);
            })
            ->whereHas('shift', function ($query) use ($targetStartStr) {
                $query->whereRaw("DATE_FORMAT(start_time, '%H:%i') = ?", [$targetStartStr])
                      ->where('status', 'active');
            })
            ->get();

        $startCount = 0;
        foreach ($startAssignments as $assignment) {
            $employee = $assignment->employee;
            if (!$employee || $employee->status !== 'active') continue;

            // Check if today is a weekly off day
            if ($assignment->shift->weekly_off_days) {
                $offDays = array_map('trim', explode(',', $assignment->shift->weekly_off_days));
                if (in_array($dayName, $offDays)) {
                    continue;
                }
            }

            // Check if employee is on approved leave today
            $onLeave = LeaveApplication::where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->where('from_date', '<=', $today)
                ->where('to_date', '>=', $today)
                ->exists();
            if ($onLeave) continue;

            // Check if they have already checked in today
            $alreadyCheckedIn = Attendance::where('user_id', $employee->id)
                ->where('attendance_date', $today)
                ->whereNotNull('check_in')
                ->exists();

            if (!$alreadyCheckedIn) {
                $title = 'Work Starting Soon';
                $description = 'Your shift starts in 10 minutes. Please remember to clock in!';
                
                $this->sendReminder($firebaseService, $employee, $title, $description);
                $this->line("  Sent start reminder to: {$employee->name} (ID: {$employee->id})");
                $startCount++;
            }
        }

        // --- PART 2: Shift End Reminders (10m after shift ends) ---
        $this->info("Checking for shifts that ended 10 minutes ago (at {$targetEndStr})...");

        $endAssignments = ShiftAssignment::with(['shift', 'employee'])
            ->where('effective_from', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $today);
            })
            ->whereHas('shift', function ($query) use ($targetEndStr) {
                $query->whereRaw("DATE_FORMAT(end_time, '%H:%i') = ?", [$targetEndStr])
                      ->where('status', 'active');
            })
            ->get();

        $endCount = 0;
        foreach ($endAssignments as $assignment) {
            $employee = $assignment->employee;
            if (!$employee || $employee->status !== 'active') continue;

            // Check if today is a weekly off day
            if ($assignment->shift->weekly_off_days) {
                $offDays = array_map('trim', explode(',', $assignment->shift->weekly_off_days));
                if (in_array($dayName, $offDays)) {
                    continue;
                }
            }

            // Check if they are currently clocked in (checked in, but not checked out)
            $stillClockedIn = Attendance::where('user_id', $employee->id)
                ->where('attendance_date', $today)
                ->whereNotNull('check_in')
                ->whereNull('check_out')
                ->exists();

            if ($stillClockedIn) {
                $title = 'Shift Ended';
                $description = 'Your shift ended 10 minutes ago. Please remember to clock out!';
                
                $this->sendReminder($firebaseService, $employee, $title, $description);
                $this->line("  Sent end reminder to: {$employee->name} (ID: {$employee->id})");
                $endCount++;
            }
        }

        $this->info("Reminder run complete. Sent {$startCount} start reminders and {$endCount} end reminders.");
        return Command::SUCCESS;
    }

    /**
     * Send database and FCM push notification reminders.
     */
    private function sendReminder(FirebaseService $firebaseService, User $employee, string $title, string $description)
    {
        // 1. Create database notification record
        Notification::create([
            'user_id' => $employee->id,
            'title' => $title,
            'description' => $description,
            'type' => 'attendance',
            'unread' => true,
        ]);

        // 2. Send via FCM if token exists
        if (!empty($employee->fcm_token)) {
            $firebaseService->sendNotification($employee->fcm_token, $title, $description, [
                'type' => 'attendance_reminder'
            ]);
        }
    }
}
