<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\AuditLog;
use App\Helpers\AuditLogger;
use Illuminate\Http\Request;

class AdminSettingController extends Controller
{
    /**
     * Display the settings dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key');
        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Update the system settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
        ]);

        $newSettings = $validated['settings'];
        $updatedCount = 0;

        foreach ($newSettings as $key => $value) {
            // Handle array values (like weekly_off_days)
            if (is_array($value)) {
                $value = json_encode($value);
            } else {
                // Treat null/empty as empty string or '0' if it is a toggle
                $value = is_null($value) ? '' : (string)$value;
            }

            $setting = Setting::where('key', $key)->first();
            if ($setting) {
                if ($setting->value !== $value) {
                    $oldValue = $setting->value;
                    $setting->value = $value;
                    $setting->save();

                    AuditLogger::log(
                        'settings',
                        'update_setting',
                        ['key' => $key, 'value' => $oldValue],
                        ['key' => $key, 'value' => $value]
                    );
                    $updatedCount++;
                }
            } else {
                // Create setting if it does not exist
                $group = $this->determineGroup($key);
                Setting::create([
                    'key' => $key,
                    'value' => $value,
                    'group' => $group
                ]);

                AuditLogger::log(
                    'settings',
                    'update_setting',
                    null,
                    ['key' => $key, 'value' => $value]
                );
                $updatedCount++;
            }
        }

        return redirect()->route('admin.settings.index')
            ->with('success', "Settings updated successfully! ({$updatedCount} changes audited.)");
    }

    /**
     * Determine group based on setting key.
     *
     * @param  string  $key
     * @return string
     */
    private function determineGroup($key)
    {
        $attendanceKeys = ['checkout_mandatory', 'auto_checkout_enabled', 'auto_checkout_time', 'prevent_multiple_checkin'];
        $faceKeys = ['face_recognition_enabled', 'live_face_detection_enabled', 'face_match_threshold', 'reject_multiple_faces', 'require_admin_approval_face_reset'];
        $geoKeys = ['geo_restriction_enabled', 'capture_selfie_enabled'];
        $securityKeys = ['allow_single_device', 'max_failed_attempts', 'failed_attempts_lock_duration', 'session_timeout'];
        $overtimeKeys = ['overtime_module_enabled', 'weekly_off_days', 'overtime_approval_levels', 'allow_employee_overtime_request', 'overtime_rate_per_hour'];

        $attendanceMethodKeys = ['global_attendance_method', 'require_gps_validation'];

        if (in_array($key, $attendanceMethodKeys)) return 'attendance_method';
        if (in_array($key, $attendanceKeys)) return 'attendance';
        if (in_array($key, $faceKeys)) return 'face';
        if (in_array($key, $geoKeys)) return 'geo';
        if (in_array($key, $securityKeys)) return 'security';
        if (in_array($key, $overtimeKeys)) return 'overtime';
        return 'general';
    }

    /**
     * Display historical change logs (Audit Trail).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function auditLogsIndex(Request $request)
    {
        $query = AuditLog::with('user')->orderBy('created_at', 'desc');

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        $auditLogs = $query->paginate(25);
        return view('admin.audit-logs.index', compact('auditLogs'));
    }
}
