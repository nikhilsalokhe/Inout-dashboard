<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\LeavePolicy;
use App\Models\LeaveBalance;
use App\Models\LeaveApplication;
use App\Models\Holiday;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $employee;
    protected User $manager;
    protected LeavePolicy $paidPolicy;
    protected LeavePolicy $unpaidPolicy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->create(['role' => 'employee', 'name' => 'Manager User']);
        $this->employee = User::factory()->create([
            'role'                 => 'employee',
            'reporting_manager_id' => $this->manager->id,
        ]);

        $this->paidPolicy = LeavePolicy::create([
            'leave_name'         => 'Annual Leave',
            'leave_code'         => 'AL',
            'leave_type'         => 'paid',
            'total_yearly_leave' => 12,
            'monthly_credit'     => 1,
            'carry_forward'      => false,
            'max_carry_forward'  => 0,
            'requires_approval'  => true,
            'status'             => 'active',
        ]);

        $this->unpaidPolicy = LeavePolicy::create([
            'leave_name'         => 'Unpaid Leave',
            'leave_code'         => 'UL',
            'leave_type'         => 'unpaid',
            'total_yearly_leave' => 0,
            'monthly_credit'     => 0,
            'carry_forward'      => false,
            'max_carry_forward'  => 0,
            'requires_approval'  => true,
            'status'             => 'active',
        ]);
    }

    /** @test */
    public function employee_can_fetch_active_leave_policies()
    {
        $response = $this->actingAs($this->employee)
            ->getJson('/api/leaves/policies');

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonFragment(['leave_code' => 'AL'])
            ->assertJsonFragment(['leave_code' => 'UL']);
    }

    /** @test */
    public function balances_are_auto_initialized_on_first_fetch()
    {
        $response = $this->actingAs($this->employee)
            ->getJson('/api/leaves/balances');

        $response->assertStatus(200);

        $this->assertDatabaseHas('leave_balances', [
            'employee_id'     => $this->employee->id,
            'leave_policy_id' => $this->paidPolicy->id,
            'total_leave'     => 12,
            'used_leave'      => 0,
            'remaining_leave' => 12,
        ]);
    }

    /** @test */
    public function employee_can_apply_for_unpaid_leave()
    {
        $response = $this->actingAs($this->employee)
            ->postJson('/api/leaves/apply', [
                'leave_policy_id' => $this->unpaidPolicy->id,
                'from_date'       => now()->addWeekdays(2)->toDateString(),
                'to_date'         => now()->addWeekdays(2)->toDateString(),
                'reason'          => 'Personal work',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Leave application submitted successfully.']);

        $this->assertDatabaseHas('leave_applications', [
            'employee_id'     => $this->employee->id,
            'leave_policy_id' => $this->unpaidPolicy->id,
            'status'          => 'pending',
        ]);
    }

    /** @test */
    public function employee_cannot_apply_paid_leave_with_zero_balance()
    {
        // Give employee 0 balance for paid leave
        LeaveBalance::create([
            'employee_id'     => $this->employee->id,
            'leave_policy_id' => $this->paidPolicy->id,
            'year'            => now()->year,
            'total_leave'     => 12,
            'used_leave'      => 12,
            'remaining_leave' => 0,
        ]);

        $response = $this->actingAs($this->employee)
            ->postJson('/api/leaves/apply', [
                'leave_policy_id' => $this->paidPolicy->id,
                'from_date'       => now()->addWeekdays(2)->toDateString(),
                'to_date'         => now()->addWeekdays(3)->toDateString(),
                'reason'          => 'Vacation',
            ]);

        $response->assertStatus(400)
            ->assertJsonFragment(['message' => 'Insufficient leave balance. Requested 2 days, available: 0.00 days.']);
    }

    /** @test */
    public function manager_can_approve_leave_application()
    {
        // Seed balance for employee
        LeaveBalance::create([
            'employee_id'     => $this->employee->id,
            'leave_policy_id' => $this->paidPolicy->id,
            'year'            => now()->year,
            'total_leave'     => 12,
            'used_leave'      => 0,
            'remaining_leave' => 12,
        ]);

        $app = LeaveApplication::create([
            'employee_id'     => $this->employee->id,
            'leave_policy_id' => $this->paidPolicy->id,
            'from_date'       => now()->addWeekdays(2)->toDateString(),
            'to_date'         => now()->addWeekdays(2)->toDateString(),
            'total_days'      => 1,
            'reason'          => 'Annual leave',
            'status'          => 'pending',
        ]);

        $response = $this->actingAs($this->manager)
            ->postJson("/api/leaves/{$app->id}/action", [
                'action'  => 'approve',
                'remarks' => 'Enjoy your leave!',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Leave application successfully approved.']);

        $this->assertDatabaseHas('leave_applications', [
            'id'          => $app->id,
            'status'      => 'approved',
            'approved_by' => $this->manager->id,
        ]);

        // Balance should be deducted
        $this->assertDatabaseHas('leave_balances', [
            'employee_id'     => $this->employee->id,
            'leave_policy_id' => $this->paidPolicy->id,
            'used_leave'      => 1,
            'remaining_leave' => 11,
        ]);
    }

    /** @test */
    public function manager_can_reject_leave_application()
    {
        $app = LeaveApplication::create([
            'employee_id'     => $this->employee->id,
            'leave_policy_id' => $this->unpaidPolicy->id,
            'from_date'       => now()->addWeekdays(5)->toDateString(),
            'to_date'         => now()->addWeekdays(5)->toDateString(),
            'total_days'      => 1,
            'reason'          => 'Personal',
            'status'          => 'pending',
        ]);

        $response = $this->actingAs($this->manager)
            ->postJson("/api/leaves/{$app->id}/action", [
                'action'  => 'reject',
                'remarks' => 'Insufficient manpower.',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Leave application successfully rejected.']);

        $this->assertDatabaseHas('leave_applications', [
            'id'     => $app->id,
            'status' => 'rejected',
        ]);
    }

    /** @test */
    public function non_manager_cannot_approve_other_employee_leave()
    {
        $stranger = User::factory()->create(['role' => 'employee']);

        $app = LeaveApplication::create([
            'employee_id'     => $this->employee->id,
            'leave_policy_id' => $this->unpaidPolicy->id,
            'from_date'       => now()->addWeekdays(2)->toDateString(),
            'to_date'         => now()->addWeekdays(2)->toDateString(),
            'total_days'      => 1,
            'reason'          => 'Personal',
            'status'          => 'pending',
        ]);

        $response = $this->actingAs($stranger)
            ->postJson("/api/leaves/{$app->id}/action", [
                'action' => 'approve',
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function holidays_are_excluded_from_leave_day_count()
    {
        // Create a holiday on a future weekday
        $holidayDate = now()->addWeekdays(2)->toDateString();
        Holiday::create([
            'holiday_name' => 'Test Holiday',
            'holiday_date' => $holidayDate,
            'holiday_type' => 'national',
            'location_id'  => null,
        ]);

        // Apply for leave covering only the holiday
        $response = $this->actingAs($this->employee)
            ->postJson('/api/leaves/apply', [
                'leave_policy_id' => $this->unpaidPolicy->id,
                'from_date'       => $holidayDate,
                'to_date'         => $holidayDate,
                'reason'          => 'Personal',
            ]);

        $response->assertStatus(400)
            ->assertJsonFragment(['message' => 'Requested leave period consists only of weekends/holidays.']);
    }

    /** @test */
    public function employee_can_view_leave_history()
    {
        LeaveApplication::create([
            'employee_id'     => $this->employee->id,
            'leave_policy_id' => $this->paidPolicy->id,
            'from_date'       => now()->subDays(10)->toDateString(),
            'to_date'         => now()->subDays(10)->toDateString(),
            'total_days'      => 1,
            'reason'          => 'Previous leave',
            'status'          => 'approved',
        ]);

        $response = $this->actingAs($this->employee)
            ->getJson('/api/leaves/history');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['status' => 'approved']);
    }
}
