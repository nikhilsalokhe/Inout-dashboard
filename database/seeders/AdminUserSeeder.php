<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'employee_type' => 'permanent',
                'employment_status' => 'active',
                'joining_date' => now()->subYear(),
                'employee_code' => 'ADM001',
                'status' => 'active',
            ]
        );

        User::updateOrCreate(
            ['email' => 'employee@example.com'],
            [
                'name' => 'Employee One',
                'password' => Hash::make('password'),
                'role' => 'employee',
                'employee_type' => 'permanent',
                'employment_status' => 'active',
                'joining_date' => now()->subMonths(6),
                'employee_code' => 'EMP001',
                'status' => 'active',
            ]
        );
    }
}
