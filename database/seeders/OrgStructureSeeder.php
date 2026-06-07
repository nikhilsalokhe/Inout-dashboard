<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\Location;
use App\Models\Position;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class OrgStructureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. Create Locations
        $hq = Location::create([
            'location_name' => 'Corporate Headquarters',
            'latitude' => 37.7749000,
            'longitude' => -122.4194000,
            'status' => 'active'
        ]);
        $branchA = Location::create([
            'location_name' => 'San Francisco Office',
            'latitude' => 37.7739000,
            'longitude' => -122.4312000,
            'status' => 'active'
        ]);
        $branchB = Location::create([
            'location_name' => 'New York Tech Hub',
            'latitude' => 40.7128000,
            'longitude' => -74.0060000,
            'status' => 'active'
        ]);

        // 2. Create Departments
        $execDept = Department::create(['department_name' => 'Executive Office', 'status' => 'active']);
        $hrDept = Department::create(['department_name' => 'Human Resources', 'status' => 'active']);
        $opsDept = Department::create(['department_name' => 'Operations', 'status' => 'active']);
        $techDept = Department::create(['department_name' => 'Technology', 'status' => 'active']);

        // 3. Create Positions/Designations
        $ceoPos = Position::create(['position_name' => 'Chief Executive Officer', 'department_id' => $execDept->id]);
        
        $hrMgrPos = Position::create(['position_name' => 'HR Manager', 'department_id' => $hrDept->id]);
        $hrExecPos = Position::create(['position_name' => 'HR Executive', 'department_id' => $hrDept->id]);
        $recruiterPos = Position::create(['position_name' => 'Corporate Recruiter', 'department_id' => $hrDept->id]);

        $opsMgrPos = Position::create(['position_name' => 'Operations Manager', 'department_id' => $opsDept->id]);
        $teamLeadPos = Position::create(['position_name' => 'Team Leader', 'department_id' => $opsDept->id]);
        $supervisorPos = Position::create(['position_name' => 'Supervisor', 'department_id' => $opsDept->id]);

        $ctoPos = Position::create(['position_name' => 'Chief Technology Officer', 'department_id' => $techDept->id]);
        $engLeadPos = Position::create(['position_name' => 'Engineering Lead', 'department_id' => $techDept->id]);
        $swePos = Position::create(['position_name' => 'Software Engineer', 'department_id' => $techDept->id]);

        // 4. Create/Update Employees into the Hierarchy
        
        // Update the existing Admin User (Admin User / admin@example.com) to be the CEO at HQ
        $ceo = User::where('email', 'admin@example.com')->first();
        if ($ceo) {
            $ceo->update([
                'name' => 'William Harrison',
                'mobile' => '+15550100100',
                'department_id' => $execDept->id,
                'location_id' => $hq->id,
                'position_id' => $ceoPos->id,
                'reporting_manager_id' => null, // CEO is at the top
                'employee_code' => 'ADM001',
            ]);
        } else {
            $ceo = User::create([
                'name' => 'William Harrison',
                'email' => 'admin@example.com',
                'mobile' => '+15550100100',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'employee_code' => 'ADM001',
                'department_id' => $execDept->id,
                'location_id' => $hq->id,
                'position_id' => $ceoPos->id,
                'reporting_manager_id' => null,
                'status' => 'active',
            ]);
        }

        // Create CTO reporting to CEO
        $cto = User::create([
            'name' => 'Dr. Elizabeth Chen',
            'email' => 'cto@example.com',
            'mobile' => '+15550100110',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'employee_code' => 'EMP002',
            'department_id' => $techDept->id,
            'location_id' => $hq->id,
            'position_id' => $ctoPos->id,
            'reporting_manager_id' => $ceo->id,
            'status' => 'active',
        ]);

        // Create HR Manager reporting to CEO
        $hrManager = User::create([
            'name' => 'Sarah Jenkins',
            'email' => 'hr_mgr@example.com',
            'mobile' => '+15550100200',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'employee_code' => 'EMP003',
            'department_id' => $hrDept->id,
            'location_id' => $hq->id,
            'position_id' => $hrMgrPos->id,
            'reporting_manager_id' => $ceo->id,
            'status' => 'active',
        ]);

        // Create HR Executive reporting to HR Manager
        $hrExec = User::create([
            'name' => 'Marcus Aurelius',
            'email' => 'hr_exec@example.com',
            'mobile' => '+15550100220',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'employee_code' => 'EMP004',
            'department_id' => $hrDept->id,
            'location_id' => $hq->id,
            'position_id' => $hrExecPos->id,
            'reporting_manager_id' => $hrManager->id,
            'status' => 'active',
        ]);

        // Create Recruiter reporting to HR Manager
        $recruiter = User::create([
            'name' => 'Sophia Varga',
            'email' => 'recruiter@example.com',
            'mobile' => '+15550100230',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'employee_code' => 'EMP005',
            'department_id' => $hrDept->id,
            'location_id' => $branchA->id,
            'position_id' => $recruiterPos->id,
            'reporting_manager_id' => $hrManager->id,
            'status' => 'active',
        ]);

        // Create Operations Manager reporting to CEO
        $opsManager = User::create([
            'name' => 'David Miller',
            'email' => 'ops_mgr@example.com',
            'mobile' => '+15550100300',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'employee_code' => 'EMP006',
            'department_id' => $opsDept->id,
            'location_id' => $branchA->id,
            'position_id' => $opsMgrPos->id,
            'reporting_manager_id' => $ceo->id,
            'status' => 'active',
        ]);

        // Create Team Leader reporting to Operations Manager
        $teamLead = User::create([
            'name' => 'Robert Stark',
            'email' => 'team_lead@example.com',
            'mobile' => '+15550100320',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'employee_code' => 'EMP007',
            'department_id' => $opsDept->id,
            'location_id' => $branchA->id,
            'position_id' => $teamLeadPos->id,
            'reporting_manager_id' => $opsManager->id,
            'status' => 'active',
        ]);

        // Update the existing default Employee One to be Employee 1 reporting to Team Leader
        $empOne = User::where('email', 'employee@example.com')->first();
        if ($empOne) {
            $empOne->update([
                'name' => 'Jonathan Snow',
                'mobile' => '+15550100001',
                'department_id' => $opsDept->id,
                'location_id' => $branchA->id,
                'position_id' => $supervisorPos->id,
                'reporting_manager_id' => $teamLead->id,
                'employee_code' => 'EMP001',
            ]);
        } else {
            $empOne = User::create([
                'name' => 'Jonathan Snow',
                'email' => 'employee@example.com',
                'mobile' => '+15550100001',
                'password' => Hash::make('password'),
                'role' => 'employee',
                'employee_code' => 'EMP001',
                'department_id' => $opsDept->id,
                'location_id' => $branchA->id,
                'position_id' => $supervisorPos->id,
                'reporting_manager_id' => $teamLead->id,
                'status' => 'active',
            ]);
        }

        // Create Employee 2 reporting to Team Leader
        $empTwo = User::create([
            'name' => 'Arya Stark',
            'email' => 'employee2@example.com',
            'mobile' => '+15550100350',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'employee_code' => 'EMP008',
            'department_id' => $opsDept->id,
            'location_id' => $branchA->id,
            'position_id' => $supervisorPos->id,
            'reporting_manager_id' => $teamLead->id,
            'status' => 'active',
        ]);

        // Create Engineering Lead reporting to CTO
        $engLead = User::create([
            'name' => 'Alexander Bell',
            'email' => 'eng_lead@example.com',
            'mobile' => '+15550100410',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'employee_code' => 'EMP009',
            'department_id' => $techDept->id,
            'location_id' => $branchB->id,
            'position_id' => $engLeadPos->id,
            'reporting_manager_id' => $cto->id,
            'status' => 'active',
        ]);

        // Create Software Engineer reporting to Engineering Lead
        $swe = User::create([
            'name' => 'Ada Lovelace',
            'email' => 'swe@example.com',
            'mobile' => '+15550100420',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'employee_code' => 'EMP010',
            'department_id' => $techDept->id,
            'location_id' => $branchB->id,
            'position_id' => $swePos->id,
            'reporting_manager_id' => $engLead->id,
            'status' => 'active',
        ]);
    }
}
