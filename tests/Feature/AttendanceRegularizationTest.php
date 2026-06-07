<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceRegularization;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceRegularizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $employee;
    protected User $admin;
    protected Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        // Base Settings Setup
        Setting::create(['key' => 'face_recognition_enabled',  'value' => '0',   'group' => 'face',       'label' => 'Face Recog',   'type' => 'boolean']);
        Setting::create(['key' => 'capture_selfie_enabled',    'value' => '0',   'group' => 'face',       'label' => 'Selfie',       'type' => 'boolean']);
        Setting::create(['key' => 'geo_restriction_enabled',   'value' => '0',   'group' => 'attendance', 'label' => 'Geofencing',   'type' => 'boolean']);
        Setting::create(['key' => 'prevent_multiple_checkin',  'value' => '0',   'group' => 'attendance', 'label' => 'Multi-In',     'type' => 'boolean']);

        // Create Roles
        $this->employee = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        // Create shift
        $this->shift = Shift::create([
            'shift_name' => 'General Day Shift',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'half_day_time' => 4.00,
            'minimum_working_hours' => 8.00,
            'status' => 'active',
        ]);

        // Assign shift
        ShiftAssignment::create([
            'employee_id' => $this->employee->id,
            'shift_id' => $this->shift->id,
            'effective_from' => '2026-01-01',
        ]);
    }

    /** @test */
    public function employee_can_submit_regularization_request()
    {
        $response = $this->actingAs($this->employee)
            ->postJson('/api/regularization/apply', [
                'attendance_date' => '2026-05-26',
                'check_in' => '2026-05-26 09:00:00',
                'check_out' => '2026-05-26 17:00:00',
                'reason' => 'Forgot to check in due to biometric scanner issue.',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'regularization']);

        $this->assertDatabaseHas('attendance_regularizations', [
            'employee_id' => $this->employee->id,
            'attendance_date' => '2026-05-26',
            'status' => 'pending',
            'reason' => 'Forgot to check in due to biometric scanner issue.',
        ]);
    }

    /** @test */
    public function employee_cannot_submit_duplicate_pending_regularization_request_on_same_date()
    {
        // First submission
        AttendanceRegularization::create([
            'employee_id' => $this->employee->id,
            'attendance_date' => '2026-05-26',
            'check_in' => '2026-05-26 09:00:00',
            'check_out' => '2026-05-26 17:00:00',
            'reason' => 'First try',
            'status' => 'pending',
        ]);

        // Second submission attempt
        $response = $this->actingAs($this->employee)
            ->postJson('/api/regularization/apply', [
                'attendance_date' => '2026-05-26',
                'check_in' => '2026-05-26 09:00:00',
                'check_out' => '2026-05-26 17:00:00',
                'reason' => 'Second try',
            ]);

        $response->assertStatus(400)
            ->assertJsonFragment(['message' => 'You already have a pending regularization request for this date.']);
    }

    /** @test */
    public function employee_can_view_regularization_history()
    {
        AttendanceRegularization::create([
            'employee_id' => $this->employee->id,
            'attendance_date' => '2026-05-26',
            'check_in' => '2026-05-26 09:00:00',
            'check_out' => '2026-05-26 17:00:00',
            'reason' => 'Regularization',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->employee)
            ->getJson('/api/regularization/history');

        $response->assertStatus(200)
            ->assertJsonStructure(['history'])
            ->assertJsonCount(1, 'history');
    }

    /** @test */
    public function admin_can_view_pending_regularization_requests()
    {
        AttendanceRegularization::create([
            'employee_id' => $this->employee->id,
            'attendance_date' => '2026-05-26',
            'check_in' => '2026-05-26 09:00:00',
            'check_out' => '2026-05-26 17:00:00',
            'reason' => 'Regularization test',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)
            ->get('/admin/regularizations');

        $response->assertStatus(200)
            ->assertViewHas('regularizations');
    }

    /** @test */
    public function admin_can_approve_regularization_request_and_update_attendance()
    {
        $reg = AttendanceRegularization::create([
            'employee_id' => $this->employee->id,
            'attendance_date' => '2026-05-26',
            'check_in' => '2026-05-26 09:00:00',
            'check_out' => '2026-05-26 17:00:00',
            'reason' => 'Regularization approval',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)
            ->post("/admin/regularizations/{$reg->id}/action", [
                'action' => 'approve',
                'remarks' => 'Approved on verification',
            ]);

        $response->assertStatus(302); // Redirect back on success

        $this->assertDatabaseHas('attendance_regularizations', [
            'id' => $reg->id,
            'status' => 'approved',
            'remarks' => 'Approved on verification',
        ]);

        // Check if Attendance was created/updated correctly
        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->employee->id,
            'attendance_date' => '2026-05-26',
            'status' => 'present',
            'working_hours' => 8.00,
        ]);
    }

    /** @test */
    public function admin_can_reject_regularization_request()
    {
        $reg = AttendanceRegularization::create([
            'employee_id' => $this->employee->id,
            'attendance_date' => '2026-05-26',
            'check_in' => '2026-05-26 09:00:00',
            'check_out' => '2026-05-26 17:00:00',
            'reason' => 'Regularization rejection',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)
            ->post("/admin/regularizations/{$reg->id}/action", [
                'action' => 'reject',
                'remarks' => 'Rejected as invalid details',
            ]);

        $response->assertStatus(302); // Redirect back on success

        $this->assertDatabaseHas('attendance_regularizations', [
            'id' => $reg->id,
            'status' => 'rejected',
            'remarks' => 'Rejected as invalid details',
        ]);

        // Attendance should NOT have been created
        $this->assertDatabaseMissing('attendances', [
            'user_id' => $this->employee->id,
            'attendance_date' => '2026-05-26',
        ]);
    }
}
