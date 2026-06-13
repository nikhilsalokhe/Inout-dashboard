<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $settings = [
            // Attendance Method group
            ['key' => 'global_attendance_method', 'value' => 'face', 'group' => 'attendance_method'],
            ['key' => 'require_gps_validation', 'value' => '1', 'group' => 'attendance_method'],

            // Attendance group
            ['key' => 'checkout_mandatory', 'value' => '1', 'group' => 'attendance'],
            ['key' => 'auto_checkout_enabled', 'value' => '0', 'group' => 'attendance'],
            ['key' => 'auto_checkout_time', 'value' => '18:00:00', 'group' => 'attendance'],
            ['key' => 'prevent_multiple_checkin', 'value' => '1', 'group' => 'attendance'],

            // Face recognition group
            ['key' => 'face_recognition_enabled', 'value' => '1', 'group' => 'face'],
            ['key' => 'live_face_detection_enabled', 'value' => '1', 'group' => 'face'],
            ['key' => 'face_match_threshold', 'value' => '85', 'group' => 'face'],
            ['key' => 'reject_multiple_faces', 'value' => '1', 'group' => 'face'],
            ['key' => 'require_admin_approval_face_reset', 'value' => '1', 'group' => 'face'],

            // Geolocation group
            ['key' => 'geo_restriction_enabled', 'value' => '1', 'group' => 'geo'],
            ['key' => 'capture_selfie_enabled', 'value' => '1', 'group' => 'geo'],

            // Security group
            ['key' => 'allow_single_device', 'value' => '0', 'group' => 'security'],
            ['key' => 'max_failed_attempts', 'value' => '3', 'group' => 'security'],
            ['key' => 'failed_attempts_lock_duration', 'value' => '15', 'group' => 'security'],
            ['key' => 'session_timeout', 'value' => '120', 'group' => 'security'],

            // Overtime group
            ['key' => 'overtime_module_enabled', 'value' => '0', 'group' => 'overtime'],
            ['key' => 'weekly_off_days', 'value' => '["Saturday","Sunday"]', 'group' => 'overtime'],
            ['key' => 'overtime_approval_levels', 'value' => '1', 'group' => 'overtime'],
            ['key' => 'allow_employee_overtime_request', 'value' => '0', 'group' => 'overtime'],
            ['key' => 'overtime_rate_per_hour', 'value' => '150', 'group' => 'overtime'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value'], 'group' => $setting['group']]
            );
        }
    }
}
