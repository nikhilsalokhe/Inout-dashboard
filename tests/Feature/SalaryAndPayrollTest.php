<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\SalaryStructure;
use App\Models\EmployeeSalary;
use App\Models\SalaryRevision;
use App\Models\Payroll;
use App\Models\Notification;
use App\Models\Attendance;
use Carbon\Carbon;

class SalaryAndPayrollTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $employee;

    protected function setUp(): void
    {
        parent::setUp();

        // Create standard admin and employee
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active'
        ]);

        $this->employee = User::factory()->create([
            'role' => 'employee',
            'status' => 'active'
        ]);
    }

    /**
     * Test admin can create salary structure package.
     */
    public function test_admin_can_create_salary_structure()
    {
        $response = $this->actingAs($this->admin)->post(route('admin.salary.structure.store'), [
            'structure_name' => 'High Executive Package',
            'basic_percentage' => 45.00,
            'hra_percentage' => 25.00,
            'da_percentage' => 15.00,
            'travel_allowance' => 5000.00,
            'pf_enabled' => 1,
            'esic_enabled' => 1,
            'professional_tax' => 200.00,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('salary_structures', [
            'structure_name' => 'High Executive Package',
            'basic_percentage' => 45.00
        ]);
    }

    /**
     * Test admin can assign salary package.
     */
    public function test_admin_can_assign_salary()
    {
        $structure = SalaryStructure::create([
            'structure_name' => 'Standard Dev Structure',
            'basic_percentage' => 50.00,
            'hra_percentage' => 20.00,
            'da_percentage' => 10.00,
            'travel_allowance' => 2000.00,
            'pf_enabled' => true,
            'esic_enabled' => true,
            'professional_tax' => 200.00,
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.salary.assign'), [
            'employee_id' => $this->employee->id,
            'salary_structure_id' => $structure->id,
            'gross_salary' => 60000.00,
            'effective_from' => Carbon::now()->toDateString()
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('employee_salaries', [
            'employee_id' => $this->employee->id,
            'gross_salary' => 60000.00
        ]);
    }

    /**
     * Test admin can revise salary.
     */
    public function test_admin_can_revise_salary()
    {
        $structure = SalaryStructure::create([
            'structure_name' => 'Structure A',
            'basic_percentage' => 50.00,
            'hra_percentage' => 20.00,
            'da_percentage' => 10.00,
            'travel_allowance' => 2000.00,
            'pf_enabled' => true,
            'esic_enabled' => true,
            'professional_tax' => 200.00,
            'status' => 'active'
        ]);

        $salary = EmployeeSalary::create([
            'employee_id' => $this->employee->id,
            'salary_structure_id' => $structure->id,
            'gross_salary' => 50000.00,
            'effective_from' => Carbon::now()->subMonths(3)->toDateString(),
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.salary.revise', $salary->id), [
            'new_gross_salary' => 70000.00,
            'new_structure_id' => $structure->id,
            'effective_date' => Carbon::now()->toDateString(),
            'remarks' => 'Promotion to Lead Engineer'
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('employee_salaries', [
            'employee_id' => $this->employee->id,
            'gross_salary' => 70000.00,
            'status' => 'active'
        ]);

        $this->assertDatabaseHas('salary_revisions', [
            'employee_id' => $this->employee->id,
            'previous_gross_salary' => 50000.00,
            'new_gross_salary' => 70000.00
        ]);
    }

    /**
     * Test admin can generate payroll.
     */
    public function test_admin_can_generate_payroll()
    {
        $structure = SalaryStructure::create([
            'structure_name' => 'Structure B',
            'basic_percentage' => 50.00,
            'hra_percentage' => 20.00,
            'da_percentage' => 10.00,
            'travel_allowance' => 2000.00,
            'pf_enabled' => true,
            'esic_enabled' => true,
            'professional_tax' => 200.00,
            'status' => 'active'
        ]);

        EmployeeSalary::create([
            'employee_id' => $this->employee->id,
            'salary_structure_id' => $structure->id,
            'gross_salary' => 50000.00,
            'effective_from' => Carbon::now()->subMonths(3)->toDateString(),
            'status' => 'active'
        ]);

        // Create standard mock attendance checkins
        Attendance::create([
            'user_id' => $this->employee->id,
            'attendance_date' => Carbon::now()->subDays(5)->toDateString(),
            'check_in' => Carbon::now()->subDays(5)->setTime(9, 0),
            'check_out' => Carbon::now()->subDays(5)->setTime(17, 0),
            'status' => 'present',
            'working_hours' => 8.00
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.payroll.generate'), [
            'month' => Carbon::now()->month,
            'year' => Carbon::now()->year,
            'employee_id' => $this->employee->id
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('payrolls', [
            'employee_id' => $this->employee->id,
            'month' => Carbon::now()->month,
            'year' => Carbon::now()->year,
            'status' => 'Draft'
        ]);
    }

    /**
     * Test payroll transitions and notifications triggers.
     */
    public function test_payroll_transitions()
    {
        $payroll = Payroll::create([
            'employee_id' => $this->employee->id,
            'month' => 4,
            'year' => 2026,
            'gross_salary' => 50000.00,
            'basic_salary' => 25000.00,
            'hra' => 10000.00,
            'da' => 5000.00,
            'travel_allowance' => 2000.00,
            'special_allowance' => 8000.00,
            'total_earnings' => 50000.00,
            'total_deductions' => 3000.00,
            'net_salary' => 47000.00,
            'payable_days' => 30,
            'paid_days' => 30,
            'status' => 'Draft'
        ]);

        // Transition Draft -> Approved
        $response = $this->actingAs($this->admin)->post(route('admin.payroll.transition', $payroll->id), [
            'action' => 'approve'
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('payrolls', [
            'id' => $payroll->id,
            'status' => 'Approved'
        ]);

        // Notification must be triggered for employee
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->employee->id,
            'type' => 'payroll'
        ]);
    }

    /**
     * Test Employee Self-Service views are secure.
     */
    public function test_ess_views_security()
    {
        // Unauthenticated user should be redirected
        $response = $this->get(route('employee.dashboard'));
        $response->assertRedirect('/admin/login');

        // Employee can access ESS dashboard
        $response = $this->actingAs($this->employee)->get(route('employee.dashboard'));
        $response->assertStatus(200);

        // Employee can access their payslips
        $response = $this->actingAs($this->employee)->get(route('employee.payslips'));
        $response->assertStatus(200);
    }
}
