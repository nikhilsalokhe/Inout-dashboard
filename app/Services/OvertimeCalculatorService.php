<?php

namespace App\Services;

use App\Models\User;
use App\Models\Attendance;
use App\Models\OvertimePolicy;
use App\Models\OvertimePolicyAssignment;
use App\Models\OvertimeRecord;
use App\Models\Setting;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class OvertimeCalculatorService
{
    /**
     * Determine the applicable overtime policy for a user.
     * Hierarchy: User-specific -> Department-specific -> null.
     */
    public function getApplicablePolicy(User $user)
    {
        // Check User assignment
        $userAssignment = OvertimePolicyAssignment::where('assignable_type', User::class)
            ->where('assignable_id', $user->id)
            ->with('policy')
            ->first();

        if ($userAssignment && $userAssignment->policy && $userAssignment->policy->is_active) {
            return $userAssignment->policy;
        }

        // Check Department assignment
        if ($user->department_id) {
            $deptAssignment = OvertimePolicyAssignment::where('assignable_type', \App\Models\Department::class)
                ->where('assignable_id', $user->department_id)
                ->with('policy')
                ->first();

            if ($deptAssignment && $deptAssignment->policy && $deptAssignment->policy->is_active) {
                return $deptAssignment->policy;
            }
        }

        return null;
    }

    /**
     * Calculate hourly rate based on policy configuration.
     */
    public function calculateHourlyRate(User $user, OvertimePolicy $policy, $multiplier = 1.0)
    {
        if ($policy->rate_type === 'fixed') {
            return ($policy->fixed_rate ?? 0) * $multiplier;
        }

        // Salary-based
        $salaryData = $user->employeeSalary;
        if (!$salaryData) {
            return 0; // Cannot calculate without salary
        }

        // Assume standard 26 working days, 8 hours a day for base calculation if not specified
        $monthlySalary = $salaryData->gross_salary;
        $workingDays = 26;
        $workingHoursPerDay = 8;
        
        $hourlyRate = $monthlySalary / ($workingDays * $workingHoursPerDay);

        return round($hourlyRate * $multiplier, 2);
    }

    /**
     * Main entry point to process overtime for a given attendance record.
     */
    public function processAttendance(Attendance $attendance)
    {
        // Respect the master "Enable Overtime Module" setting
        if (Setting::get('overtime_module_enabled', '0') != '1') {
            return;
        }

        $user = $attendance->user;
        $policy = $this->getApplicablePolicy($user);

        if (!$policy) {
            return; // No active policy
        }

        $date = Carbon::parse($attendance->date);
        $workingHours = $attendance->working_hours;

        if ($workingHours <= 0) return;

        // Check if Weekend
        $defaultOffs = ['Saturday', 'Sunday'];
        $savedOffsStr = Setting::get('weekly_off_days', '[]');
        $savedOffs = json_decode($savedOffsStr, true) ?: $defaultOffs;
        
        $isWeekend = in_array($date->format('l'), $savedOffs);

        // Check if Holiday
        $isHoliday = Holiday::whereDate('date', $date->format('Y-m-d'))->exists();

        // 1. HOLIDAY OVERTIME
        if ($isHoliday && $policy->calc_holiday) {
            $amount = $this->calculateHourlyRate($user, $policy, $policy->holiday_rate_multiplier) * $workingHours;
            $this->createRecord($user, $date, 'holiday', $workingHours, $amount);
            return; // Usually, holiday overwrites standard daily calculation
        }

        // 2. WEEKEND OVERTIME
        if ($isWeekend && $policy->calc_weekend) {
            $amount = $this->calculateHourlyRate($user, $policy, $policy->weekend_rate_multiplier) * $workingHours;
            $this->createRecord($user, $date, 'weekend', $workingHours, $amount);
            return; 
        }

        // 3. DAILY OVERTIME
        if ($policy->calc_daily) {
            $minHours = $policy->daily_min_hours > 0 ? $policy->daily_min_hours : ($attendance->shift->minimum_working_hours ?? 8);
            $threshold = $policy->daily_threshold ?? 0;

            if ($workingHours > ($minHours + $threshold)) {
                $overtimeHours = $workingHours - $minHours;

                // Cap at max_daily if configured
                if ($policy->max_daily > 0 && $overtimeHours > $policy->max_daily) {
                    $overtimeHours = $policy->max_daily;
                }

                $amount = $this->calculateHourlyRate($user, $policy, $policy->daily_rate_multiplier) * $overtimeHours;
                $this->createRecord($user, $date, 'daily', $overtimeHours, $amount);
            }
        }

        // Note: Weekly and Monthly overtimes usually run via a weekly/monthly scheduled cron job,
        // aggregating total hours. We'll leave the stubs here or handle them in the report generation.
    }

    private function createRecord(User $user, Carbon $date, $type, $hours, $amount)
    {
        // Don't duplicate records for the same date and type if already processed/approved
        $existing = OvertimeRecord::where('user_id', $user->id)
            ->whereDate('date', $date->format('Y-m-d'))
            ->where('overtime_type', $type)
            ->first();

        if ($existing && in_array($existing->status, ['hr_approved', 'processed', 'paid'])) {
            return; // Can't touch approved/paid records
        }

        // Determine initial status based on approval levels setting
        $approvalLevels = Setting::get('overtime_approval_levels', '1');
        // '1' means HR only (Manager step skipped if not used). We'll set 'pending' and let the UI handle it.

        if ($existing) {
            $existing->update([
                'hours' => $hours,
                'amount' => $amount,
                'status' => 'pending'
            ]);
        } else {
            OvertimeRecord::create([
                'user_id' => $user->id,
                'date' => $date->format('Y-m-d'),
                'overtime_type' => $type,
                'hours' => $hours,
                'amount' => $amount,
                'status' => 'pending',
                'is_manual_request' => false,
            ]);
        }
    }
}
