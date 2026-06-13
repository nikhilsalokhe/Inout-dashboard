<?php

namespace App\Services;

use App\Models\User;
use App\Models\Setting;

class AttendanceMethodService
{
    /**
     * Determine the applicable attendance method for a user.
     * Hierarchy: User-specific -> Department-specific -> Global Setting.
     *
     * @param User $user
     * @return string (e.g., 'face', 'qr', 'face_and_qr', 'face_or_qr', 'manual', 'gps_only')
     */
    public function getApplicableMethod(User $user): string
    {
        // 1. Check User-specific override
        if (!empty($user->attendance_method)) {
            return $user->attendance_method;
        }

        // 2. Check Department-specific override
        if ($user->department && !empty($user->department->attendance_method)) {
            return $user->department->attendance_method;
        }

        // 3. Fallback to Global Setting
        return Setting::get('global_attendance_method', 'face');
    }

    /**
     * Check if GPS validation is strictly required globally.
     *
     * @return bool
     */
    public function isGpsValidationRequired(): bool
    {
        return Setting::get('require_gps_validation', '1') == '1';
    }

    /**
     * Validate if the given latitude/longitude is within the assigned location's radius.
     *
     * @param User $user
     * @param float $lat
     * @param float $lng
     * @return array ['success' => bool, 'message' => string]
     */
    public function validateGps(User $user, $lat, $lng): array
    {
        if (!$this->isGpsValidationRequired()) {
            return ['success' => true, 'message' => 'GPS validation not required.'];
        }

        $location = $user->location; // Assuming user belongs to a primary location
        if (!$location) {
            // If user has no location assigned, but GPS is required, we could either pass or fail.
            // Let's assume they must have a location assigned if GPS is required.
            return ['success' => false, 'message' => 'No assigned location found for GPS validation.'];
        }

        if (!$lat || !$lng) {
            return ['success' => false, 'message' => 'GPS coordinates are missing.'];
        }

        // Calculate distance using Haversine formula
        $earthRadius = 6371000; // meters
        $latFrom = deg2rad($location->latitude);
        $lonFrom = deg2rad($location->longitude);
        $latTo = deg2rad($lat);
        $lonTo = deg2rad($lng);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($latFrom) * cos($latTo) *
             sin($lonDelta / 2) * sin($lonDelta / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = $earthRadius * $c;

        if ($distance <= $location->allowed_radius_meter) {
            return ['success' => true, 'message' => 'GPS validation successful.'];
        }

        return [
            'success' => false, 
            'message' => "You are outside the allowed radius. Distance: " . round($distance) . "m, Allowed: " . $location->allowed_radius_meter . "m."
        ];
    }
}
