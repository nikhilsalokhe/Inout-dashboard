<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LeavePolicy;
use App\Models\LeaveBalance;
use App\Models\Holiday;
use App\Models\User;
use Carbon\Carbon;

class LeaveManagementSeeder extends Seeder
{
    /**
     * Seed leave policies, holidays, and initial leave balances.
     */
    public function run()
    {
        $year = Carbon::now()->year;

        // ── Leave Policies ──────────────────────────────────────────────
        $casualLeave = LeavePolicy::updateOrCreate(
            ['leave_code' => 'CL'],
            [
                'leave_name'         => 'Casual Leave',
                'leave_type'         => 'paid',
                'total_yearly_leave' => 12,
                'monthly_credit'     => 1,
                'carry_forward'      => false,
                'max_carry_forward'  => 0,
                'requires_approval'  => true,
                'status'             => 'active',
            ]
        );

        $sickLeave = LeavePolicy::updateOrCreate(
            ['leave_code' => 'SL'],
            [
                'leave_name'         => 'Sick Leave',
                'leave_type'         => 'paid',
                'total_yearly_leave' => 10,
                'monthly_credit'     => 0.83,
                'carry_forward'      => false,
                'max_carry_forward'  => 0,
                'requires_approval'  => true,
                'status'             => 'active',
            ]
        );

        $earnedLeave = LeavePolicy::updateOrCreate(
            ['leave_code' => 'EL'],
            [
                'leave_name'         => 'Earned Leave',
                'leave_type'         => 'paid',
                'total_yearly_leave' => 15,
                'monthly_credit'     => 1.25,
                'carry_forward'      => true,
                'max_carry_forward'  => 10,
                'requires_approval'  => true,
                'status'             => 'active',
            ]
        );

        $maternityLeave = LeavePolicy::updateOrCreate(
            ['leave_code' => 'ML'],
            [
                'leave_name'         => 'Maternity Leave',
                'leave_type'         => 'paid',
                'total_yearly_leave' => 182,
                'monthly_credit'     => 0,
                'carry_forward'      => false,
                'max_carry_forward'  => 0,
                'requires_approval'  => true,
                'status'             => 'active',
            ]
        );

        $compOffLeave = LeavePolicy::updateOrCreate(
            ['leave_code' => 'CO'],
            [
                'leave_name'         => 'Compensatory Off',
                'leave_type'         => 'paid',
                'total_yearly_leave' => 5,
                'monthly_credit'     => 0,
                'carry_forward'      => false,
                'max_carry_forward'  => 0,
                'requires_approval'  => true,
                'status'             => 'active',
            ]
        );

        $lwpLeave = LeavePolicy::updateOrCreate(
            ['leave_code' => 'LWP'],
            [
                'leave_name'         => 'Leave Without Pay',
                'leave_type'         => 'unpaid',
                'total_yearly_leave' => 0,
                'monthly_credit'     => 0,
                'carry_forward'      => false,
                'max_carry_forward'  => 0,
                'requires_approval'  => true,
                'status'             => 'active',
            ]
        );

        $this->command->info('✅ Leave policies seeded (6 policies)');

        // ── Holidays (India National Holidays for current year) ───────
        $holidays = [
            ['holiday_name' => 'Republic Day',           'holiday_date' => "$year-01-26", 'holiday_type' => 'national'],
            ['holiday_name' => 'Holi',                   'holiday_date' => "$year-03-14", 'holiday_type' => 'national'],
            ['holiday_name' => 'Good Friday',            'holiday_date' => "$year-03-29", 'holiday_type' => 'national'],
            ['holiday_name' => 'Eid ul-Fitr',            'holiday_date' => "$year-04-11", 'holiday_type' => 'national'],
            ['holiday_name' => 'Dr. Ambedkar Jayanti',   'holiday_date' => "$year-04-14", 'holiday_type' => 'national'],
            ['holiday_name' => 'Labour Day',             'holiday_date' => "$year-05-01", 'holiday_type' => 'national'],
            ['holiday_name' => 'Independence Day',       'holiday_date' => "$year-08-15", 'holiday_type' => 'national'],
            ['holiday_name' => 'Janmashtami',            'holiday_date' => "$year-08-26", 'holiday_type' => 'national'],
            ['holiday_name' => 'Gandhi Jayanti',         'holiday_date' => "$year-10-02", 'holiday_type' => 'national'],
            ['holiday_name' => 'Dussehra',               'holiday_date' => "$year-10-12", 'holiday_type' => 'national'],
            ['holiday_name' => 'Diwali',                 'holiday_date' => "$year-11-01", 'holiday_type' => 'national'],
            ['holiday_name' => 'Guru Nanak Jayanti',     'holiday_date' => "$year-11-15", 'holiday_type' => 'national'],
            ['holiday_name' => 'Christmas Day',          'holiday_date' => "$year-12-25", 'holiday_type' => 'national'],
        ];

        foreach ($holidays as $holiday) {
            Holiday::updateOrCreate(
                ['holiday_date' => $holiday['holiday_date'], 'holiday_name' => $holiday['holiday_name']],
                [
                    'holiday_type' => $holiday['holiday_type'],
                    'location_id'  => null, // National → applies to all locations
                ]
            );
        }

        $this->command->info("✅ Holidays seeded (13 national holidays for $year)");

        // ── Initialize Leave Balances for all active employees ───────
        $policies = LeavePolicy::where('status', 'active')
            ->where('leave_type', 'paid')
            ->get();

        $employees = User::where('role', 'employee')
            ->where('status', 'active')
            ->get();

        $count = 0;
        foreach ($employees as $employee) {
            foreach ($policies as $policy) {
                LeaveBalance::firstOrCreate(
                    [
                        'employee_id'     => $employee->id,
                        'leave_policy_id' => $policy->id,
                        'year'            => $year,
                    ],
                    [
                        'total_leave'     => $policy->total_yearly_leave,
                        'used_leave'      => 0,
                        'remaining_leave' => $policy->total_yearly_leave,
                    ]
                );
                $count++;
            }
        }

        $this->command->info("✅ Leave balances initialized ($count records for {$employees->count()} employees)");
    }
}
