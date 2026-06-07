<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Setting;
use App\Models\AuditLog;
use App\Models\LeavePolicy;
use App\Models\LeaveApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityControlsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        // Base settings — correct keys matching the controller
        Setting::create(['key' => 'face_recognition_enabled',     'value' => '1', 'group' => 'face',       'label' => 'Face Recog',  'type' => 'boolean']);
        Setting::create(['key' => 'capture_selfie_enabled',       'value' => '1', 'group' => 'face',       'label' => 'Selfie',      'type' => 'boolean']);
        Setting::create(['key' => 'live_face_detection_enabled',  'value' => '0', 'group' => 'face',       'label' => 'Liveness',    'type' => 'boolean']);
        Setting::create(['key' => 'reject_multiple_faces',        'value' => '0', 'group' => 'face',       'label' => 'Multi-face',  'type' => 'boolean']);
        Setting::create(['key' => 'face_match_threshold',         'value' => '85','group' => 'face',       'label' => 'Threshold',   'type' => 'number']);
        Setting::create(['key' => 'geo_restriction_enabled',      'value' => '0', 'group' => 'attendance', 'label' => 'Geofencing',  'type' => 'boolean']);
        Setting::create(['key' => 'prevent_multiple_checkin',     'value' => '0', 'group' => 'attendance', 'label' => 'Multi-In',    'type' => 'boolean']);
        Setting::create(['key' => 'allow_single_device',          'value' => '1', 'group' => 'security',   'label' => 'Device',      'type' => 'boolean']);
        Setting::create(['key' => 'max_failed_attempts',          'value' => '3', 'group' => 'security',   'label' => 'Max Fails',   'type' => 'number']);
        Setting::create(['key' => 'failed_attempts_lock_duration','value' => '15','group' => 'security',   'label' => 'Lockout',     'type' => 'number']);
    }

    /** @test */
    public function device_binding_blocks_unknown_device_on_check_in()
    {
        // allow_single_device = 1 (already set in setUp)
        $user = User::factory()->create([
            'face_encoding' => array_fill(0, 128, 0.1),
            'device_id'     => 'DEVICE-REGISTERED-001',  // controller column name is device_id
        ]);

        Http::fake([
            'http://127.0.0.1:8001/verify-face' => Http::response([
                'match'           => true,
                'distance'        => 0.20,
                'confidence'      => 92.0,
                'liveness_passed' => true,
            ], 200),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/check-in', [
                'image'     => UploadedFile::fake()->create('face.jpg', 100, 'image/jpeg'),
                'device_id' => 'DEVICE-UNKNOWN-999',
            ]);

        // Controller returns 403 for unregistered device
        $response->assertStatus(403)
            ->assertJsonFragment(['message' => 'Unregistered device. Please contact your HR department for device registration reset.']);
    }

    /** @test */
    public function device_binding_allows_registered_device()
    {
        $user = User::factory()->create([
            'face_encoding' => array_fill(0, 128, 0.1),
            'device_id'     => 'DEVICE-REGISTERED-001',
        ]);

        Http::fake([
            'http://127.0.0.1:8001/verify-face' => Http::response([
                'match'           => true,
                'distance'        => 0.20,
                'confidence'      => 92.0,
                'liveness_passed' => true,
            ], 200),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/check-in', [
                'image'     => UploadedFile::fake()->create('face.jpg', 100, 'image/jpeg'),
                'device_id' => 'DEVICE-REGISTERED-001',
            ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function liveness_check_blocks_spoofed_face_when_enabled()
    {
        // Enable live face detection
        Setting::where('key', 'live_face_detection_enabled')->update(['value' => '1']);
        // Disable device binding for this test
        Setting::where('key', 'allow_single_device')->update(['value' => '0']);

        $user = User::factory()->create([
            'face_encoding' => array_fill(0, 128, 0.1),
        ]);

        Http::fake([
            'http://127.0.0.1:8001/verify-face' => Http::response([
                'match'           => true,
                'distance'        => 0.20,
                'confidence'      => 92.0,
                'liveness_passed' => false,  // Anti-spoofing triggered
            ], 200),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/check-in', [
                'image' => UploadedFile::fake()->create('face.jpg', 100, 'image/jpeg'),
            ]);

        $response->assertStatus(400)
            ->assertJsonFragment([
                'message' => 'Liveness check failed. Please use a live camera — photos and videos are not permitted.'
            ]);
    }

    /** @test */
    public function audit_log_is_created_when_settings_are_updated()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Setting::create([
            'key'   => 'test_setting',
            'value' => 'old_val',
            'group' => 'attendance',
            'label' => 'Test Setting',
            'type'  => 'text',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.settings.update'), [
                'settings' => ['test_setting' => 'new_val'],
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'module'  => 'settings',
            'action'  => 'update_setting',
        ]);
    }

    /** @test */
    public function audit_log_is_created_on_leave_approval()
    {
        $manager  = User::factory()->create(['role' => 'employee']);
        $employee = User::factory()->create([
            'role'                 => 'employee',
            'reporting_manager_id' => $manager->id,
        ]);

        $policy = LeavePolicy::create([
            'leave_name'         => 'Casual',
            'leave_code'         => 'CL',
            'leave_type'         => 'unpaid',
            'total_yearly_leave' => 0,
            'monthly_credit'     => 0,
            'carry_forward'      => false,
            'max_carry_forward'  => 0,
            'requires_approval'  => true,
            'status'             => 'active',
        ]);

        $app = LeaveApplication::create([
            'employee_id'     => $employee->id,
            'leave_policy_id' => $policy->id,
            'from_date'       => now()->addWeekdays(3)->toDateString(),
            'to_date'         => now()->addWeekdays(3)->toDateString(),
            'total_days'      => 1,
            'reason'          => 'Personal',
            'status'          => 'pending',
        ]);

        $this->actingAs($manager)
            ->postJson("/api/leaves/{$app->id}/action", [
                'action'  => 'reject',
                'remarks' => 'No leave granted.',
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $manager->id,
            'module'  => 'leaves',
            'action'  => 'reject_leave',
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_api_endpoints()
    {
        $this->getJson('/api/leaves/balances')->assertStatus(401);
        $this->getJson('/api/dashboard-status')->assertStatus(401);
        $this->postJson('/api/check-in', [])->assertStatus(401);
    }

    /** @test */
    public function admin_panel_requires_authentication()
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.audit-logs.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.leaves.policies'))->assertRedirect(route('admin.login'));
    }
}
