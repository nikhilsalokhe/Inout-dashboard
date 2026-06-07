<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Location;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GeolocationTest extends TestCase
{
    use RefreshDatabase;

    protected User $employee;
    protected Location $location;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        // Disable face recognition so geofence is the only gating factor
        Setting::create(['key' => 'face_recognition_enabled',  'value' => '0',   'group' => 'face',       'label' => 'Face Recog',   'type' => 'boolean']);
        Setting::create(['key' => 'capture_selfie_enabled',    'value' => '0',   'group' => 'face',       'label' => 'Selfie',       'type' => 'boolean']);
        Setting::create(['key' => 'geo_restriction_enabled',   'value' => '1',   'group' => 'attendance', 'label' => 'Geofencing',   'type' => 'boolean']);
        Setting::create(['key' => 'prevent_multiple_checkin',  'value' => '0',   'group' => 'attendance', 'label' => 'Multi-In',     'type' => 'boolean']);
        Setting::create(['key' => 'allow_single_device',       'value' => '0',   'group' => 'security',   'label' => 'Device',       'type' => 'boolean']);
        Setting::create(['key' => 'max_failed_attempts',       'value' => '3',   'group' => 'security',   'label' => 'Max Fails',    'type' => 'number']);
        Setting::create(['key' => 'failed_attempts_lock_duration', 'value' => '15', 'group' => 'security', 'label' => 'Lockout',     'type' => 'number']);

        // Create a location at a known lat/lng (New Delhi: 28.6139, 77.2090)
        $this->location = Location::create([
            'location_name'        => 'Head Office',
            'latitude'             => 28.6139,
            'longitude'            => 77.2090,
            'allowed_radius_meter' => 200,
            'status'               => 'active',
        ]);

        $this->employee = User::factory()->create([
            'role'        => 'employee',
            'location_id' => $this->location->id,
        ]);
    }

    /** @test */
    public function employee_can_check_in_when_inside_geofence()
    {
        // ~11 m from office — within 200 m radius
        $response = $this->actingAs($this->employee)
            ->postJson('/api/check-in', [
                'location' => 'Lat: 28.6140, Long: 77.2091',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'attendance']);
    }

    /** @test */
    public function employee_cannot_check_in_when_outside_geofence()
    {
        // Noida — ~15 km away, far outside 200 m radius
        $response = $this->actingAs($this->employee)
            ->postJson('/api/check-in', [
                'location' => 'Lat: 28.5355, Long: 77.3910',
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function geofence_is_bypassed_when_setting_disabled()
    {
        Setting::where('key', 'geo_restriction_enabled')->update(['value' => '0']);

        // Mumbai — thousands of km away
        $response = $this->actingAs($this->employee)
            ->postJson('/api/check-in', [
                'location' => 'Lat: 19.0760, Long: 72.8777',
            ]);

        // Geo disabled → should succeed
        $response->assertStatus(200);
    }

    /** @test */
    public function check_in_fails_without_location_when_geofencing_enabled()
    {
        // No location provided, geo restriction is ON
        $response = $this->actingAs($this->employee)
            ->postJson('/api/check-in', []);

        $response->assertStatus(403)
            ->assertJsonFragment(['message' => 'Location / GPS coordinates are required for attendance.']);
    }

    /** @test */
    public function haversine_distance_calculation_is_accurate()
    {
        // Direct unit assertion of the Haversine formula values
        // New Delhi (28.6139, 77.2090) → Noida (28.5355, 77.3910) ≈ 15 km
        $R  = 6371000; // metres
        $lat1 = deg2rad(28.6139); $lon1 = deg2rad(77.2090);
        $lat2 = deg2rad(28.5355); $lon2 = deg2rad(77.3910);

        $Δlat = $lat2 - $lat1;
        $Δlon = $lon2 - $lon1;
        $a = sin($Δlat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($Δlon / 2) ** 2;
        $dist = $R * 2 * atan2(sqrt($a), sqrt(1 - $a));

        $this->assertGreaterThan(10000, $dist, 'Distance should be > 10 km');
        $this->assertLessThan(20000, $dist, 'Distance should be < 20 km');
    }
}
