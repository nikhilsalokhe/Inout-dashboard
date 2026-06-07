<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\User;
use Carbon\Carbon;

class ShiftAndAssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. Create Shifts
        $general = Shift::create([
            'shift_name' => 'General Day Shift',
            'shift_type' => 'general',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'grace_time_minutes' => 15,
            'half_day_time' => 4.00,
            'minimum_working_hours' => 8.00,
            'weekly_off_days' => 'Saturday,Sunday',
            'status' => 'active',
        ]);

        $night = Shift::create([
            'shift_name' => 'Tech Night Shift',
            'shift_type' => 'night',
            'start_time' => '22:00:00',
            'end_time' => '07:00:00',
            'grace_time_minutes' => 15,
            'half_day_time' => 4.00,
            'minimum_working_hours' => 8.00,
            'weekly_off_days' => 'Saturday,Sunday',
            'status' => 'active',
        ]);

        $rotational = Shift::create([
            'shift_name' => 'Operations Evening Shift',
            'shift_type' => 'rotational',
            'start_time' => '14:00:00',
            'end_time' => '23:00:00',
            'grace_time_minutes' => 15,
            'half_day_time' => 4.00,
            'minimum_working_hours' => 8.00,
            'weekly_off_days' => 'Monday,Tuesday',
            'status' => 'active',
        ]);

        $flexible = Shift::create([
            'shift_name' => 'Executive Flexible Hours',
            'shift_type' => 'flexible',
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
            'grace_time_minutes' => 0,
            'half_day_time' => 4.00,
            'minimum_working_hours' => 8.00,
            'weekly_off_days' => 'Saturday,Sunday',
            'status' => 'active',
        ]);

        // 2. Assign General Shift to all current employees/admins
        $users = User::all();
        foreach ($users as $user) {
            ShiftAssignment::create([
                'employee_id' => $user->id,
                'shift_id' => $user->role === 'admin' ? $flexible->id : $general->id,
                'effective_from' => Carbon::now()->subMonths(1)->toDateString(),
                'effective_to' => null, // Continuous
            ]);
        }
    }
}
