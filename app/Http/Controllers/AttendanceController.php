<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Attendance;
use App\Models\User;
use App\Models\FaceResetRequest;
use App\Models\FaceRecognitionLog;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\Department;
use App\Models\Location;
use App\Models\Position;
use App\Models\AttendanceRegularization;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    /**
     * Minimum confidence percentage required for face verification to pass.
     */
    private const FACE_CONFIDENCE_THRESHOLD = 75.0;

    /**
     * Resize image if too large and encode to base64.
     * Shrinks payload sent to Python AI service, cutting latency from ~4 seconds to ~0.5 seconds.
     */
    private function resizeAndEncodeBase64($filePath, $maxDim = 360): string
    {
        if (!extension_loaded('gd') || !function_exists('imagecreatefromjpeg')) {
            return base64_encode(file_get_contents($filePath));
        }

        list($width, $height, $type) = @getimagesize($filePath);
        if ($width <= 0 || $height <= 0) {
            return base64_encode(file_get_contents($filePath));
        }

        // Load image based on type
        switch ($type) {
            case IMAGETYPE_JPEG:
                $src = @imagecreatefromjpeg($filePath);
                break;
            case IMAGETYPE_PNG:
                $src = @imagecreatefrompng($filePath);
                break;
            case IMAGETYPE_GIF:
                $src = @imagecreatefromgif($filePath);
                break;
            default:
                return base64_encode(file_get_contents($filePath));
        }

        if (!$src) {
            return base64_encode(file_get_contents($filePath));
        }

        // Calculate new dimensions
        if (max($width, $height) > $maxDim) {
            $scale = $maxDim / max($width, $height);
            $newWidth = (int)($width * $scale);
            $newHeight = (int)($height * $scale);
            
            $dst = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preserve transparency if PNG
            if ($type == IMAGETYPE_PNG) {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
            }
            
            imagecopyresized($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($src);
            $src = $dst;
        }

        // Output to buffer as JPEG with 75% quality to minimize payload size
        ob_start();
        imagejpeg($src, null, 75);
        $imageData = ob_get_clean();
        imagedestroy($src);

        return base64_encode($imageData);
    }

    public function registerFace(Request $request)
    {
        $request->validate([
            'face_image' => 'required|file|image|max:10240', // Max 10MB image file
        ]);

        $user = $request->user();

        // Get image base64
        $file = $request->file('face_image');
        $imageBase64 = $this->resizeAndEncodeBase64($file->getRealPath());

        // Call the AI Service to generate face encoding
        $aiUrl = config('services.ai.url', 'http://127.0.0.1:8000');

        try {
            $response = Http::asForm()->post($aiUrl . '/get-encoding', [
                'image' => $imageBase64,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to connect to the AI face recognition service.',
                'error' => $e->getMessage(),
            ], 500);
        }

        if ($response->failed()) {
            $detail = $response->json('detail') ?? 'Failed to parse face encoding from the image.';
            
            // Log failed registration
            $failPath = $file->store('face_recognition_logs/register_failures', 'public');
            FaceRecognitionLog::create([
                'user_id' => $user->id,
                'captured_image' => $failPath,
                'confidence_score' => 0.00,
                'liveness_passed' => false,
                'status' => 'failed_match',
                'action_type' => 'register',
                'remarks' => 'Registration failed: ' . $detail,
            ]);
            
            return response()->json(['message' => $detail], 400);
        }

        $encoding = $response->json('encoding');
        if (empty($encoding)) {
            $failPath = $file->store('face_recognition_logs/register_failures', 'public');
            FaceRecognitionLog::create([
                'user_id' => $user->id,
                'captured_image' => $failPath,
                'confidence_score' => 0.00,
                'liveness_passed' => false,
                'status' => 'failed_match',
                'action_type' => 'register',
                'remarks' => 'Registration failed: No face detected in the uploaded image.',
            ]);
            return response()->json(['message' => 'No face detected in the uploaded image.'], 400);
        }

        // Store uploaded face image
        $path = $file->store('faces', 'public');
        $user->face_image = $path;
        $user->face_encoding = $encoding;
        $user->save();

        // Log successful registration
        FaceRecognitionLog::create([
            'user_id' => $user->id,
            'captured_image' => $path,
            'confidence_score' => 100.00,
            'liveness_passed' => true,
            'status' => 'success',
            'action_type' => 'register',
            'remarks' => 'Face profile registered successfully.',
        ]);

        return response()->json([
            'message'   => 'Face registered successfully',
            'face_path' => Storage::url($path),
        ]);
    }

    /**
     * Verify captured face against the user's stored face encoding.
     *
     * Calls the AI service and validates:
     *  - Face match (strict tolerance)
     *  - Confidence score >= threshold
     *  - Single face in frame
     *  - Liveness / anti-spoofing check
     *
     * @return array Verification results [success => bool, status => string, message => string, code => int, confidence => float, liveness_passed => bool]
     */
    private function verifyFace(User $user, $imageBase64): array
    {
        // Check if face recognition is enabled globally
        $faceRecognitionEnabled = \App\Models\Setting::get('face_recognition_enabled', '1') == '1';
        if (!$faceRecognitionEnabled) {
            return [
                'success' => true,
                'status' => 'success',
                'confidence' => 100.00,
                'liveness_passed' => true,
            ];
        }

        // Ensure user has a registered face
        if (!$user->face_encoding) {
            return [
                'success' => false,
                'status' => 'failed_match',
                'message' => 'Please register your face profile first.',
                'code' => 400,
                'confidence' => 0.00,
                'liveness_passed' => false,
            ];
        }

        $aiUrl = config('services.ai.url', 'http://127.0.0.1:8000');

        try {
            $response = Http::asJson()->post($aiUrl . '/verify-face', [
                'captured_image'  => $imageBase64,
                'stored_encoding' => $user->face_encoding,
            ]);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status' => 'failed_match',
                'message' => 'Failed to connect to the AI face recognition service.',
                'code' => 500,
                'confidence' => 0.00,
                'liveness_passed' => false,
            ];
        }

        if ($response->failed()) {
            $detail = $response->json('detail') ?? 'Face verification failed.';
            return [
                'success' => false,
                'status' => 'failed_match',
                'message' => $detail,
                'code' => 400,
                'confidence' => 0.00,
                'liveness_passed' => false,
            ];
        }

        $result = $response->json();
        $confidence = $result['confidence'] ?? 0;
        $livenessPassed = $result['liveness_passed'] ?? false;

        // Check: multiple faces detected (if restricted)
        $rejectMultipleFaces = \App\Models\Setting::get('reject_multiple_faces', '1') == '1';
        if ($rejectMultipleFaces && isset($result['faces_detected']) && $result['faces_detected'] > 1) {
            return [
                'success' => false,
                'status' => 'multiple_faces',
                'message' => 'Multiple faces detected. Only one person should be in the frame.',
                'code' => 400,
                'confidence' => $confidence,
                'liveness_passed' => $livenessPassed,
            ];
        }

        // Check: liveness / anti-spoofing (if enabled)
        $liveFaceDetectionEnabled = \App\Models\Setting::get('live_face_detection_enabled', '1') == '1';
        if ($liveFaceDetectionEnabled && isset($result['liveness_passed']) && !$result['liveness_passed']) {
            return [
                'success' => false,
                'status' => 'liveness_failed',
                'message' => 'Liveness check failed. Please use a live camera — photos and videos are not permitted.',
                'code' => 400,
                'confidence' => $confidence,
                'liveness_passed' => $livenessPassed,
            ];
        }

        // Check: face match
        if (empty($result['match'])) {
            return [
                'success' => false,
                'status' => 'failed_match',
                'message' => 'Unauthorized Face Detected. Only the registered employee can mark attendance.',
                'code' => 403,
                'confidence' => $confidence,
                'liveness_passed' => $livenessPassed,
            ];
        }

        // Check: confidence threshold
        $matchThreshold = (float)\App\Models\Setting::get('face_match_threshold', '85');
        if ($confidence < $matchThreshold) {
            return [
                'success' => false,
                'status' => 'low_confidence',
                'message' => 'Face verification confidence too low (' . round($confidence, 1) . '%). Please ensure good lighting and face the camera directly.',
                'code' => 400,
                'confidence' => $confidence,
                'liveness_passed' => $livenessPassed,
            ];
        }

        // All checks passed
        return [
            'success' => true,
            'status' => 'success',
            'confidence' => $confidence,
            'liveness_passed' => $livenessPassed,
        ];
    }



    public function checkIn(Request $request)
    {
        $user = User::with(['location', 'permittedLocations'])->find($request->user()->id);
        $today = Carbon::today()->toDateString();

        $existing = Attendance::where('user_id', $user->id)
            ->where('attendance_date', $today)
            ->first();

        // 1. Prevent Multiple Checkins setting
        $preventMultiple = \App\Models\Setting::get('prevent_multiple_checkin', '1') == '1';
        if ($preventMultiple && $existing && $existing->check_in) {
            return response()->json(['message' => 'Already checked in for today'], 400);
        }

        // 2. Device Binding restriction
        $allowSingleDevice = \App\Models\Setting::get('allow_single_device', '0') == '1';
        if ($allowSingleDevice) {
            $incomingDeviceId = $request->input('device_id') ?: $request->header('X-Device-ID');
            if (empty($incomingDeviceId)) {
                return response()->json(['message' => 'Device identification is required.'], 400);
            }
            if (empty($user->device_id)) {
                $user->device_id = $incomingDeviceId;
                $user->save();
            } elseif ($user->device_id !== $incomingDeviceId) {
                return response()->json(['message' => 'Unregistered device. Please contact your HR department for device registration reset.'], 403);
            }
        }

        // 3. Determine Attendance Method
        $methodService = app(\App\Services\AttendanceMethodService::class);
        $method = $methodService->getApplicableMethod($user);
        
        $faceRequired = in_array($method, ['face', 'face_and_qr']);
        $qrRequired = in_array($method, ['qr', 'face_and_qr']);
        $faceOrQr = $method === 'face_or_qr';
        
        $usingFace = false;
        $usingQr = false;
        $qrPayload = $request->input('qr_payload');
        $hasImage = $request->hasFile('image');

        if ($faceOrQr) {
            if ($hasImage) $usingFace = true;
            elseif (!empty($qrPayload)) $usingQr = true;
            else return response()->json(['message' => 'Please provide either a face scan or scan a QR code to clock in.'], 400);
        } else {
            $usingFace = $faceRequired;
            $usingQr = $qrRequired;
        }

        $methodUsed = $method;
        if ($method === 'face_or_qr') {
            $methodUsed = $usingFace ? 'face' : 'qr';
        }

        // Validate QR if required
        if ($usingQr) {
            if (empty($qrPayload)) {
                return response()->json(['message' => 'QR Code scan is required to clock in.'], 400);
            }
            $qrData = json_decode($qrPayload, true);
            if (!$qrData || !isset($qrData['location_id']) || $qrData['type'] !== 'static_location_qr') {
                return response()->json(['message' => 'Invalid QR Code format.'], 400);
            }
            // Ensure QR location matches assigned location
            if ($qrData['location_id'] != $user->location_id && !$user->permittedLocations->contains('id', $qrData['location_id'])) {
                return response()->json(['message' => 'This QR Code does not match your permitted office locations.'], 403);
            }
        }

        // Failed Biometric Attempts lockout check
        if ($usingFace) {
            $maxFailed = (int)\App\Models\Setting::get('max_failed_attempts', '3');
            $lockDuration = (int)\App\Models\Setting::get('failed_attempts_lock_duration', '15');
            $lockoutTime = Carbon::now()->subMinutes($lockDuration);

            $failedCount = FaceRecognitionLog::where('user_id', $user->id)
                ->where('created_at', '>=', $lockoutTime)
                ->whereIn('status', ['failed_match', 'low_confidence', 'liveness_failed'])
                ->count();

            $lastSuccess = FaceRecognitionLog::where('user_id', $user->id)
                ->where('created_at', '>=', $lockoutTime)
                ->where('status', 'success')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($lastSuccess) {
                $failedCount = FaceRecognitionLog::where('user_id', $user->id)
                    ->where('created_at', '>', $lastSuccess->created_at)
                    ->whereIn('status', ['failed_match', 'low_confidence', 'liveness_failed'])
                    ->count();
            }

            if ($failedCount >= $maxFailed) {
                $lastFail = FaceRecognitionLog::where('user_id', $user->id)
                    ->whereIn('status', ['failed_match', 'low_confidence', 'liveness_failed'])
                    ->latest()
                    ->first();
                $timeLeft = $lockDuration - Carbon::parse($lastFail->created_at)->diffInMinutes(Carbon::now());
                $timeLeft = max(1, (int)$timeLeft);
                return response()->json([
                    'message' => "Profile locked due to too many failed biometric attempts. Please try again in {$timeLeft} minutes or contact HR."
                ], 403);
            }
        }

        // 4. Capture selfie requirements
        $selfieRequired = \App\Models\Setting::get('capture_selfie_enabled', '1') == '1' || $usingFace;
        
        // If using strictly QR (and not Face), override selfie requirement to false because frontend does not send image for QR-only
        if ($usingQr && !$usingFace) {
            $selfieRequired = false;
        }

        if ($selfieRequired && !$hasImage) {
            return response()->json(['message' => 'Verification photo is required to clock in.'], 400);
        }

        $imagePath = null;
        $verificationResult = ['success' => true, 'status' => 'success', 'confidence' => 100.00, 'liveness_passed' => true];

        if ($hasImage) {
            $file = $request->file('image');
            $imageBase64 = $this->resizeAndEncodeBase64($file->getRealPath());

            if ($usingFace) {
                // Verify face with all security checks
                $verificationResult = $this->verifyFace($user, $imageBase64);
                
                if (!$verificationResult['success']) {
                    // Save failure image
                    $failurePath = $file->store('face_recognition_logs/failures', 'public');
                    
                    // Create biometric log entry
                    FaceRecognitionLog::create([
                        'user_id' => $user->id,
                        'captured_image' => $failurePath,
                        'confidence_score' => $verificationResult['confidence'] ?? 0.00,
                        'liveness_passed' => $verificationResult['liveness_passed'] ?? false,
                        'status' => $verificationResult['status'] ?? 'failed_match',
                        'action_type' => 'check_in',
                        'remarks' => $verificationResult['message'] ?? 'Verification failed',
                    ]);

                    return response()->json(
                        ['message' => $verificationResult['message']],
                        $verificationResult['code']
                    );
                }
            }

            // Store check-in image
            $imagePath = $file->store('attendance', 'public');

            if ($usingFace) {
                // Log successful biometrics attempt
                FaceRecognitionLog::create([
                    'user_id' => $user->id,
                    'captured_image' => $imagePath,
                    'confidence_score' => $verificationResult['confidence'] ?? 100.00,
                    'liveness_passed' => $verificationResult['liveness_passed'] ?? true,
                    'status' => 'success',
                    'action_type' => 'check_in',
                    'remarks' => 'Verification successful',
                ]);
            }
        }

        // Get Active Shift Assignment for the employee
        $assignment = ShiftAssignment::with('shift')
            ->where('employee_id', $user->id)
            ->where('effective_from', '<=', $today)
            ->where(function($q) use ($today) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $today);
            })
            ->first();

        $shift = $assignment ? $assignment->shift : null;
        $status = 'present';
        $remarks = null;

        if ($shift) {
            $result = $this->calculateCheckInStatus($shift);
            $status = $result['status'];
            $remarks = $result['remarks'];
        } else {
            $remarks = "Checked in with default policy (No Shift Assigned)";
        }

        // 5. Proximity / Geofencing check
        $distanceKm = null;
        $officeLocation = null;
        $locationRemark = "";
        $loginType = 'office'; // Default to office login

        if ($request->location) {
            $parsedCoords = $this->parseCoordinates($request->location);
            if ($parsedCoords) {
                $geoResult = $this->checkGeofence($user, $parsedCoords['latitude'], $parsedCoords['longitude']);
                if (!$geoResult['allowed']) {
                    return response()->json(['message' => $geoResult['message']], 403);
                }
                $distanceKm = $geoResult['distance_km'];
                $officeLocation = $geoResult['matched_location'];
                
                if ($officeLocation && $distanceKm !== null) {
                    $radiusKm = ($officeLocation->allowed_radius_meter ?? 200) / 1000;
                    if ($distanceKm > $radiusKm) {
                        $locationRemark = "Location Exception: Clocked in " . round($distanceKm, 2) . " km away from assigned office (" . $officeLocation->location_name . ")";
                    }
                }
            }
        } else {
            // Location coordinate is missing but restriction is enabled
            if ($methodService->isGpsValidationRequired()) {
                return response()->json(['message' => 'Location / GPS coordinates are required for attendance.'], 403);
            }
            // No location provided — mark as remote login
            $loginType = 'remote';
            $locationRemark = "Remote Login: GPS location not available at check-in";
        }

        if (!empty($locationRemark)) {
            $remarks = ($remarks ? $remarks . " | " : "") . $locationRemark;
        }

        $attendance = Attendance::updateOrCreate(
            ['user_id' => $user->id, 'attendance_date' => $today],
            [
                'check_in'   => Carbon::now(),
                'location'   => $request->location,
                'login_type' => $loginType,
                'distance_km'=> $distanceKm,
                'shift_id'   => $shift ? $shift->id : null,
                'status'     => $status,
                'remarks'    => $remarks,
                'image'      => $imagePath,
                'method_used'=> $methodUsed,
            ]
        );

        $message = $loginType === 'remote'
            ? 'Checked in successfully (Remote Login — GPS unavailable)'
            : 'Checked in successfully';

        return response()->json(['message' => $message, 'attendance' => $attendance]);
    }

    public function checkOut(Request $request)
    {
        $user = User::with(['location', 'permittedLocations'])->find($request->user()->id);
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->where('attendance_date', $today)
            ->first();

        if (!$attendance || !$attendance->check_in) {
            return response()->json(['message' => 'No check-in record found for today'], 400);
        }

        if ($attendance->check_out) {
            return response()->json(['message' => 'Already checked out for today'], 400);
        }

        // 1. Device Binding restriction
        $allowSingleDevice = \App\Models\Setting::get('allow_single_device', '0') == '1';
        if ($allowSingleDevice) {
            $incomingDeviceId = $request->input('device_id') ?: $request->header('X-Device-ID');
            if (empty($incomingDeviceId)) {
                return response()->json(['message' => 'Device identification is required.'], 400);
            }
            if (empty($user->device_id)) {
                $user->device_id = $incomingDeviceId;
                $user->save();
            } elseif ($user->device_id !== $incomingDeviceId) {
                return response()->json(['message' => 'Unregistered device. Please contact your HR department for device registration reset.'], 403);
            }
        }

        // 2. Determine Attendance Method
        $methodService = app(\App\Services\AttendanceMethodService::class);
        $method = $methodService->getApplicableMethod($user);
        
        $faceRequired = in_array($method, ['face', 'face_and_qr']);
        $qrRequired = in_array($method, ['qr', 'face_and_qr']);
        $faceOrQr = $method === 'face_or_qr';
        
        $usingFace = false;
        $usingQr = false;
        $qrPayload = $request->input('qr_payload');
        $hasImage = $request->hasFile('image');

        if ($faceOrQr) {
            if ($hasImage) $usingFace = true;
            elseif (!empty($qrPayload)) $usingQr = true;
            else return response()->json(['message' => 'Please provide either a face scan or scan a QR code to clock out.'], 400);
        } else {
            $usingFace = $faceRequired;
            $usingQr = $qrRequired;
        }

        // Validate QR if required
        if ($usingQr) {
            if (empty($qrPayload)) {
                return response()->json(['message' => 'QR Code scan is required to clock out.'], 400);
            }
            $qrData = json_decode($qrPayload, true);
            if (!$qrData || !isset($qrData['location_id']) || $qrData['type'] !== 'static_location_qr') {
                return response()->json(['message' => 'Invalid QR Code format.'], 400);
            }
            // Ensure QR location matches assigned location
            if ($qrData['location_id'] != $user->location_id && !$user->permittedLocations->contains('id', $qrData['location_id'])) {
                return response()->json(['message' => 'This QR Code does not match your permitted office locations.'], 403);
            }
        }

        // Failed Biometric Attempts lockout check
        if ($usingFace) {
            $maxFailed = (int)\App\Models\Setting::get('max_failed_attempts', '3');
            $lockDuration = (int)\App\Models\Setting::get('failed_attempts_lock_duration', '15');
            $lockoutTime = Carbon::now()->subMinutes($lockDuration);

            $failedCount = FaceRecognitionLog::where('user_id', $user->id)
                ->where('created_at', '>=', $lockoutTime)
                ->whereIn('status', ['failed_match', 'low_confidence', 'liveness_failed'])
                ->count();

            $lastSuccess = FaceRecognitionLog::where('user_id', $user->id)
                ->where('created_at', '>=', $lockoutTime)
                ->where('status', 'success')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($lastSuccess) {
                $failedCount = FaceRecognitionLog::where('user_id', $user->id)
                    ->where('created_at', '>', $lastSuccess->created_at)
                    ->whereIn('status', ['failed_match', 'low_confidence', 'liveness_failed'])
                    ->count();
            }

            if ($failedCount >= $maxFailed) {
                $lastFail = FaceRecognitionLog::where('user_id', $user->id)
                    ->whereIn('status', ['failed_match', 'low_confidence', 'liveness_failed'])
                    ->latest()
                    ->first();
                $timeLeft = $lockDuration - Carbon::parse($lastFail->created_at)->diffInMinutes(Carbon::now());
                $timeLeft = max(1, (int)$timeLeft);
                return response()->json([
                    'message' => "Profile locked due to too many failed biometric attempts. Please try again in {$timeLeft} minutes or contact HR."
                ], 403);
            }
        }

        // 3. Capture selfie requirements
        $selfieRequired = \App\Models\Setting::get('capture_selfie_enabled', '1') == '1' || $usingFace;
        
        // If using strictly QR (and not Face), override selfie requirement to false because frontend does not send image for QR-only
        if ($usingQr && !$usingFace) {
            $selfieRequired = false;
        }

        if ($selfieRequired && !$hasImage) {
            return response()->json(['message' => 'Verification photo is required to clock out.'], 400);
        }

        $imagePath = null;
        $verificationResult = ['success' => true, 'status' => 'success', 'confidence' => 100.00, 'liveness_passed' => true];

        if ($hasImage) {
            $file = $request->file('image');
            $imageBase64 = $this->resizeAndEncodeBase64($file->getRealPath());

            if ($usingFace) {
                // Verify face with all security checks
                $verificationResult = $this->verifyFace($user, $imageBase64);
                
                if (!$verificationResult['success']) {
                    // Save failure image
                    $failurePath = $file->store('face_recognition_logs/failures', 'public');
                    
                    // Create biometric log entry
                    FaceRecognitionLog::create([
                        'user_id' => $user->id,
                        'captured_image' => $failurePath,
                        'confidence_score' => $verificationResult['confidence'] ?? 0.00,
                        'liveness_passed' => $verificationResult['liveness_passed'] ?? false,
                        'status' => $verificationResult['status'] ?? 'failed_match',
                        'action_type' => 'check_out',
                        'remarks' => $verificationResult['message'] ?? 'Verification failed',
                    ]);

                    return response()->json(
                        ['message' => $verificationResult['message']],
                        $verificationResult['code']
                    );
                }
            }

            // Store check-out image
            $imagePath = $file->store('attendance', 'public');

            if ($usingFace) {
                // Log successful biometrics attempt
                FaceRecognitionLog::create([
                    'user_id' => $user->id,
                    'captured_image' => $imagePath,
                    'confidence_score' => $verificationResult['confidence'] ?? 100.00,
                    'liveness_passed' => $verificationResult['liveness_passed'] ?? true,
                    'status' => 'success',
                    'action_type' => 'check_out',
                    'remarks' => 'Verification successful',
                ]);
            }
        }



        $checkOutTime = Carbon::now();
        $attendance->check_out = $checkOutTime;

        // Calculate working hours in decimal format
        $checkIn  = Carbon::parse($attendance->check_in);
        $workingHours = $checkIn->diffInMinutes($checkOutTime) / 60;
        $attendance->working_hours = round($workingHours, 2);

        // Fetch the assigned shift associated with this attendance record or lookup
        $shift = $attendance->shift;
        if (!$shift) {
            $assignment = ShiftAssignment::with('shift')
                ->where('employee_id', $user->id)
                ->where('effective_from', '<=', $today)
                ->where(function($q) use ($today) {
                    $q->whereNull('effective_to')
                      ->orWhere('effective_to', '>=', $today);
                })
                ->first();
            $shift = $assignment ? $assignment->shift : null;
        }

        // 4. Proximity / Geofencing check
        $distanceKm = null;
        $officeLocation = null;
        $locationRemark = "";
        $loginType = $attendance->login_type ?? 'office'; // Preserve check-in login_type by default

        if ($request->location) {
            $parsedCoords = $this->parseCoordinates($request->location);
            if ($parsedCoords) {
                $geoResult = $this->checkGeofence($user, $parsedCoords['latitude'], $parsedCoords['longitude']);
                if (!$geoResult['allowed']) {
                    return response()->json(['message' => $geoResult['message']], 403);
                }
                $distanceKm = $geoResult['distance_km'];
                $officeLocation = $geoResult['matched_location'];
                
                if ($officeLocation && $distanceKm !== null) {
                    $radiusKm = ($officeLocation->allowed_radius_meter ?? 200) / 1000;
                    if ($distanceKm > $radiusKm) {
                        $locationRemark = "Location Exception: Clocked out " . round($distanceKm, 2) . " km away from assigned office (" . $officeLocation->location_name . ")";
                    }
                }
            }
        } else {
            // Location coordinate is missing but restriction is enabled
            $geoRestrictionEnabled = \App\Models\Setting::get('geo_restriction_enabled', '0') == '1';
            if ($geoRestrictionEnabled) {
                return response()->json(['message' => 'Location / GPS coordinates are required for attendance.'], 403);
            }
            // No location provided — mark as remote login if not already set
            $loginType = 'remote';
            $locationRemark = "Remote Login: GPS location not available at check-out";
        }

        if ($distanceKm !== null) {
            $attendance->distance_km = $distanceKm;
        }

        $statusRemarks = "";

        if ($shift) {
            $attendance->shift_id = $shift->id;
            
            // Under-time metrics
            $undertime = 0.00;

            if ($workingHours < $shift->minimum_working_hours && $workingHours > $shift->half_day_time) {
                $undertime = round($shift->minimum_working_hours - $workingHours, 2);
                $statusRemarks .= " Under-time: {$undertime} hrs.";
            }

            // Trigger new Overtime Calculator Engine
            try {
                $calculator = new \App\Services\OvertimeCalculatorService();
                $calculator->processAttendance($attendance);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Overtime Calculation Failed: ' . $e->getMessage());
            }

            // Check Working Hours Policies
            if ($workingHours < $shift->half_day_time) {
                // Not worked enough to even qualify for Half Day
                $attendance->status = 'absent';
                $attendance->remarks = "Absent: Worked only " . round($workingHours, 2) . " hrs. (Required " . $shift->half_day_time . " hrs for Half-Day)";
            } elseif ($workingHours < $shift->minimum_working_hours) {
                // Qualified for Half Day
                $attendance->status = 'half_day';
                $attendance->remarks = "Half-Day: Worked " . round($workingHours, 2) . " hrs. (Required " . $shift->minimum_working_hours . " hrs for Full Day)" . $statusRemarks;
            } else {
                // Full day - status remains present or late depending on check-in
                if ($attendance->status !== 'late') {
                    $attendance->status = 'present';
                }
                $attendance->remarks = ($attendance->remarks ? $attendance->remarks . " | " : "") . "Clocked out successfully. Total shift: " . round($workingHours, 2) . " hrs." . $statusRemarks;
            }
        } else {
            // Fallback default policy (8 hrs present, else half_day/absent)
            if ($workingHours < 4) {
                $attendance->status = 'absent';
                $attendance->remarks = "Absent: Default threshold not met (Worked < 4 hrs)";
            } elseif ($workingHours < 8) {
                $attendance->status = 'half_day';
                $attendance->remarks = "Half-Day: Worked < 8 hrs | Under-time: " . round(8 - $workingHours, 2) . " hrs.";
            } else {
                $attendance->status = 'present';
                $attendance->remarks = "On-time default shift completion";
                
                // Trigger new Overtime Calculator Engine for default shift
                try {
                    $calculator = new \App\Services\OvertimeCalculatorService();
                    $calculator->processAttendance($attendance);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Overtime Calculation Failed: ' . $e->getMessage());
                }
            }
        }

        if (!empty($locationRemark)) {
            $attendance->remarks = ($attendance->remarks ? $attendance->remarks . " | " : "") . $locationRemark;
        }

        // Update login_type if checkout is remote (or if check-in was already remote)
        $attendance->login_type = $loginType;
        $attendance->save();

        $message = $loginType === 'remote'
            ? 'Checked out successfully (Remote Login — GPS unavailable)'
            : 'Checked out successfully';

        return response()->json(['message' => $message, 'attendance' => $attendance]);
    }
    /**
     * Calculate check-in status based on shift type.
     *
     * Handles General, Night, Rotational, and Flexible shift types
     * with their specific timing logic.
     */
    private function calculateCheckInStatus(Shift $shift): array
    {
        $now = Carbon::now();

        switch ($shift->shift_type) {
            case 'general':
                return $this->calculateGeneralShiftStatus($shift, $now);

            case 'night':
                return $this->calculateNightShiftStatus($shift, $now);

            case 'rotational':
                return $this->calculateRotationalShiftStatus($shift, $now);

            case 'flexible':
                return [
                    'status'  => 'present',
                    'remarks' => 'Flexible shift: No fixed timing policy. Checked in at ' . $now->format('h:i A'),
                ];

            default:
                return [
                    'status'  => 'present',
                    'remarks' => 'Unknown shift type. Default check-in applied.',
                ];
        }
    }

    /**
     * General (day) shift: standard start_time + grace_time check.
     */
    private function calculateGeneralShiftStatus(Shift $shift, Carbon $now): array
    {
        if (!$shift->start_time) {
            return ['status' => 'present', 'remarks' => 'General shift (no start time configured)'];
        }

        $shiftStart = Carbon::createFromFormat('H:i:s', $shift->start_time);
        $shiftStartToday = Carbon::today()->setTime($shiftStart->hour, $shiftStart->minute, $shiftStart->second);
        $graceDeadline = $shiftStartToday->copy()->addMinutes($shift->grace_time_minutes);

        if ($now->greaterThan($graceDeadline)) {
            $minutesLate = $now->diffInMinutes($shiftStartToday);
            return [
                'status'  => 'late',
                'remarks' => "Late Check-in by {$minutesLate} minutes (Grace: {$shift->grace_time_minutes} mins)",
            ];
        }

        return ['status' => 'present', 'remarks' => 'On-time Check-in'];
    }

    /**
     * Night shift: handles cross-midnight timing.
     *
     * Night shifts typically start in the evening (e.g. 22:00)
     * and end next morning (e.g. 06:00). The grace period applies
     * relative to the evening start time.
     */
    private function calculateNightShiftStatus(Shift $shift, Carbon $now): array
    {
        if (!$shift->start_time) {
            return ['status' => 'present', 'remarks' => 'Night shift (no start time configured)'];
        }

        $shiftStart = Carbon::createFromFormat('H:i:s', $shift->start_time);

        // Determine the correct date context for the shift start
        // If current time is early morning (before noon), the shift started yesterday evening
        if ($now->hour < 12 && $shiftStart->hour >= 12) {
            $shiftStartDate = Carbon::yesterday()->setTime($shiftStart->hour, $shiftStart->minute, $shiftStart->second);
        } else {
            $shiftStartDate = Carbon::today()->setTime($shiftStart->hour, $shiftStart->minute, $shiftStart->second);
        }

        $graceDeadline = $shiftStartDate->copy()->addMinutes($shift->grace_time_minutes);

        if ($now->greaterThan($graceDeadline)) {
            $minutesLate = $now->diffInMinutes($shiftStartDate);
            return [
                'status'  => 'late',
                'remarks' => "Night Shift: Late Check-in by {$minutesLate} minutes (Grace: {$shift->grace_time_minutes} mins)",
            ];
        }

        return ['status' => 'present', 'remarks' => 'Night Shift: On-time Check-in'];
    }

    /**
     * Rotational shift: same logic as general for timing,
     * but acknowledged as a rotating schedule.
     */
    private function calculateRotationalShiftStatus(Shift $shift, Carbon $now): array
    {
        if (!$shift->start_time) {
            return ['status' => 'present', 'remarks' => 'Rotational shift (no start time configured)'];
        }

        $shiftStart = Carbon::createFromFormat('H:i:s', $shift->start_time);

        // Rotational shifts can span across midnight like night shifts
        if ($shift->end_time) {
            $shiftEnd = Carbon::createFromFormat('H:i:s', $shift->end_time);
            $crossesMidnight = $shiftEnd->lessThan($shiftStart);

            if ($crossesMidnight && $now->hour < 12 && $shiftStart->hour >= 12) {
                $shiftStartDate = Carbon::yesterday()->setTime($shiftStart->hour, $shiftStart->minute, $shiftStart->second);
            } else {
                $shiftStartDate = Carbon::today()->setTime($shiftStart->hour, $shiftStart->minute, $shiftStart->second);
            }
        } else {
            $shiftStartDate = Carbon::today()->setTime($shiftStart->hour, $shiftStart->minute, $shiftStart->second);
        }

        $graceDeadline = $shiftStartDate->copy()->addMinutes($shift->grace_time_minutes);

        if ($now->greaterThan($graceDeadline)) {
            $minutesLate = $now->diffInMinutes($shiftStartDate);
            return [
                'status'  => 'late',
                'remarks' => "Rotational Shift: Late Check-in by {$minutesLate} minutes (Grace: {$shift->grace_time_minutes} mins)",
            ];
        }

        return ['status' => 'present', 'remarks' => 'Rotational Shift: On-time Check-in'];
    }

    public function history(Request $request)
    {
        $user = $request->user();
        
        $history = Attendance::with('shift')
            ->where('user_id', $user->id)
            ->orderBy('attendance_date', 'desc')
            ->get()
            ->map(function ($record) {
                return [
                    'id' => $record->id,
                    'attendance_date' => $record->attendance_date,
                    'check_in' => $record->check_in ? \Carbon\Carbon::parse($record->check_in)->toIso8601String() : null,
                    'check_out' => $record->check_out ? \Carbon\Carbon::parse($record->check_out)->toIso8601String() : null,
                    'location' => $record->location,
                    'distance_km' => $record->distance_km !== null ? (float)$record->distance_km : null,
                    'status' => $record->status,
                    'remarks' => $record->remarks,
                    'working_hours' => $record->working_hours !== null ? (float)$record->working_hours : null,
                    'image' => $record->image,
                    'shift' => $record->shift ? [
                        'name' => $record->shift->shift_name,
                        'start_time' => $record->shift->start_time,
                        'end_time' => $record->shift->end_time,
                        'shift_type' => $record->shift->shift_type,
                    ] : null,
                ];
            });

        return response()->json($history);
    }

    /**
     * Get real-time dashboard status for the authenticated employee.
     *
     * Returns today's attendance status, current shift info,
     * weekly summary, and streak data.
     */
    public function getDashboardStatus(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        // Today's attendance
        $todayAttendance = Attendance::with('shift')
            ->where('user_id', $user->id)
            ->where('attendance_date', $today)
            ->first();

        // Active Shift
        $assignment = ShiftAssignment::with('shift')
            ->where('employee_id', $user->id)
            ->where('effective_from', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $today);
            })
            ->first();

        $shift = $assignment ? $assignment->shift : null;

        // Weekly summary (current week Mon-Sun)
        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekEnd = Carbon::now()->endOfWeek(Carbon::SUNDAY)->toDateString();

        $weeklyAttendances = Attendance::where('user_id', $user->id)
            ->whereBetween('attendance_date', [$weekStart, $weekEnd])
            ->get();

        $weeklySummary = [
            'present'    => $weeklyAttendances->where('status', 'present')->count(),
            'late'       => $weeklyAttendances->where('status', 'late')->count(),
            'half_day'   => $weeklyAttendances->where('status', 'half_day')->count(),
            'absent'     => $weeklyAttendances->where('status', 'absent')->count(),
            'weekly_off' => $weeklyAttendances->where('status', 'weekly_off')->count(),
        ];

        // Monthly summary
        $monthStart = Carbon::now()->startOfMonth()->toDateString();
        $monthEnd = Carbon::now()->endOfMonth()->toDateString();

        $monthlyAttendances = Attendance::where('user_id', $user->id)
            ->whereBetween('attendance_date', [$monthStart, $monthEnd])
            ->get();

        $monthlySummary = [
            'present'    => $monthlyAttendances->where('status', 'present')->count(),
            'late'       => $monthlyAttendances->where('status', 'late')->count(),
            'half_day'   => $monthlyAttendances->where('status', 'half_day')->count(),
            'absent'     => $monthlyAttendances->where('status', 'absent')->count(),
            'weekly_off' => $monthlyAttendances->where('status', 'weekly_off')->count(),
            'total_working_hours' => round($monthlyAttendances->sum('working_hours'), 2),
        ];

        // Attendance streak
        $streak = 0;
        $checkDate = Carbon::yesterday();
        while (true) {
            $record = Attendance::where('user_id', $user->id)
                ->where('attendance_date', $checkDate->toDateString())
                ->first();

            if ($record && in_array($record->status, ['present', 'late', 'weekly_off'])) {
                $streak++;
                $checkDate->subDay();
            } else {
                break;
            }

            // Safety limit to 365 days
            if ($streak > 365) break;
        }

        // Build today status
        $todayStatus = 'not_checked_in';
        $checkInTime = null;
        $checkOutTime = null;
        $workedHours = null;

        if ($todayAttendance) {
            $todayStatus = $todayAttendance->status;
            $checkInTime = $todayAttendance->check_in ? Carbon::parse($todayAttendance->check_in)->format('h:i A') : null;
            $checkOutTime = $todayAttendance->check_out ? Carbon::parse($todayAttendance->check_out)->format('h:i A') : null;
            $workedHours = $todayAttendance->working_hours;

            // If still working (checked in, no checkout), compute live hours
            if ($todayAttendance->check_in && !$todayAttendance->check_out) {
                $workedHours = round(Carbon::parse($todayAttendance->check_in)->diffInMinutes($now) / 60, 2);
            }
        }

        $methodService = app(\App\Services\AttendanceMethodService::class);

        return response()->json([
            'today' => [
                'status'       => $todayStatus,
                'check_in'     => $checkInTime,
                'check_in_raw' => ($todayAttendance && $todayAttendance->check_in) ? Carbon::parse($todayAttendance->check_in)->toIso8601String() : null,
                'check_out'    => $checkOutTime,
                'working_hours' => $workedHours,
                'remarks'      => $todayAttendance->remarks ?? null,
            ],
            'shift' => $shift ? [
                'name'            => $shift->shift_name,
                'type'            => $shift->shift_type,
                'start_time'      => $shift->start_time ? Carbon::createFromFormat('H:i:s', $shift->start_time)->format('h:i A') : 'Flexible',
                'end_time'        => $shift->end_time ? Carbon::createFromFormat('H:i:s', $shift->end_time)->format('h:i A') : 'Flexible',
                'grace_mins'      => $shift->grace_time_minutes,
                'weekly_off_days' => $shift->weekly_off_days,
            ] : null,
            'weekly_summary'  => $weeklySummary,
            'monthly_summary' => $monthlySummary,
            'streak'          => $streak,
            'face_recognition_enabled' => \App\Models\Setting::get('face_recognition_enabled', '1') == '1',
            'attendance_method' => $methodService->getApplicableMethod($user),
            'require_gps_validation' => $methodService->isGpsValidationRequired(),
        ]);
    }

    /**
     * Admin API: Get attendance statistics for the dashboard.
     *
     * Returns real-time counts of present, absent, late, half-day,
     * on-leave, and not-checked-in employees for today.
     */
    public function getAdminDashboardStats()
    {
        $today = Carbon::today()->toDateString();
        $dayName = Carbon::today()->format('l');

        $totalActive = User::where('role', 'employee')->where('status', 'active')->count();

        $todayAttendances = Attendance::where('attendance_date', $today)->get();

        $present    = $todayAttendances->where('status', 'present')->count();
        $late       = $todayAttendances->where('status', 'late')->count();
        $halfDay    = $todayAttendances->where('status', 'half_day')->count();
        $absent     = $todayAttendances->where('status', 'absent')->count();
        $weeklyOff  = $todayAttendances->where('status', 'weekly_off')->count();

        // Employees who have checked in (any status) today
        $checkedIn = $todayAttendances->whereNotNull('check_in')->count();
        $notCheckedIn = $totalActive - $checkedIn - $weeklyOff;
        if ($notCheckedIn < 0) $notCheckedIn = 0;

        // Still working (checked in but not out)
        $stillWorking = $todayAttendances->whereNotNull('check_in')->whereNull('check_out')->count();

        // Recently checked in (last 30 minutes)
        $recentThreshold = Carbon::now()->subMinutes(30);
        $recentCheckIns = Attendance::where('attendance_date', $today)
            ->where('check_in', '>=', $recentThreshold)
            ->with('user:id,name,employee_code')
            ->orderBy('check_in', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($a) {
                return [
                    'employee' => $a->user->name ?? 'Unknown',
                    'code'     => $a->user->employee_code ?? '-',
                    'time'     => Carbon::parse($a->check_in)->format('h:i A'),
                    'status'   => $a->status,
                ];
            });

        return response()->json([
            'date'           => $today,
            'day'            => $dayName,
            'total_employees' => $totalActive,
            'present'        => $present,
            'late'           => $late,
            'half_day'       => $halfDay,
            'absent'         => $absent,
            'weekly_off'     => $weeklyOff,
            'not_checked_in' => $notCheckedIn,
            'still_working'  => $stillWorking,
            'recent_check_ins' => $recentCheckIns,
            'attendance_rate' => $totalActive > 0 ? round((($present + $late) / $totalActive) * 100, 1) : 0,
        ]);
    }

    public function requestFaceReset(Request $request)
    {
        $request->validate([
            'face_image' => 'required|file|image|max:10240', // Max 10MB image file
        ]);

        $user = $request->user();

        // Check if there is an existing pending request
        $pending = FaceResetRequest::where('employee_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($pending) {
            return response()->json([
                'message' => 'You already have a pending face reset request awaiting admin approval.'
            ], 400);
        }

        // Store the uploaded image
        $file = $request->file('face_image');
        $newFacePath = $file->store('face_resets', 'public');

        // Create the reset request record
        $resetRequest = FaceResetRequest::create([
            'employee_id'    => $user->id,
            'old_face_image' => $user->face_image,
            'new_face_image' => $newFacePath,
            'status'         => 'pending',
            'requested_at'   => Carbon::now(),
        ]);

        return response()->json([
            'message' => 'Face reset request submitted successfully. Awaiting admin approval.',
            'request' => $resetRequest
        ]);
    }

    public function checkPendingFaceReset(Request $request)
    {
        $user = $request->user();

        $pending = FaceResetRequest::where('employee_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($pending) {
            return response()->json([
                'has_pending' => true,
                'request' => [
                    'id' => $pending->id,
                    'status' => $pending->status,
                    'new_face_image' => Storage::url($pending->new_face_image),
                    'requested_at' => $pending->requested_at->toIso8601String(),
                ]
            ]);
        }

        return response()->json([
            'has_pending' => false
        ]);
    }

    public function getHierarchy(Request $request)
    {
        $user = $request->user();

        // 1. Get Manager details
        $manager = null;
        if ($user->reporting_manager_id) {
            $mgrUser = User::with(['department', 'position'])->find($user->reporting_manager_id);
            if ($mgrUser) {
                $manager = [
                    'id' => $mgrUser->id,
                    'name' => $mgrUser->name,
                    'email' => $mgrUser->email,
                    'mobile' => $mgrUser->mobile ?? 'Not specified',
                    'position' => $mgrUser->position ? $mgrUser->position->position_name : 'No Position',
                    'department' => $mgrUser->department ? $mgrUser->department->department_name : 'No Department',
                ];
            }
        }

        // 2. Get Teammates (sharing same manager, excluding self)
        $teammates = [];
        if ($user->reporting_manager_id) {
            $teammateUsers = User::with(['department', 'position'])
                ->where('reporting_manager_id', $user->reporting_manager_id)
                ->where('id', '!=', $user->id)
                ->where('status', 'active')
                ->get();

            foreach ($teammateUsers as $t) {
                $teammates[] = [
                    'id' => $t->id,
                    'name' => $t->name,
                    'email' => $t->email,
                    'position' => $t->position ? $t->position->position_name : 'No Position',
                    'department' => $t->department ? $t->department->department_name : 'No Department',
                ];
            }
        }

        // 3. Get Subordinates
        $subordinates = [];
        $subordinateUsers = User::with(['department', 'position'])
            ->where('reporting_manager_id', $user->id)
            ->where('status', 'active')
            ->get();

        foreach ($subordinateUsers as $s) {
            $subordinates[] = [
                'id' => $s->id,
                'name' => $s->name,
                'email' => $s->email,
                'mobile' => $s->mobile ?? 'Not specified',
                'position' => $s->position ? $s->position->position_name : 'No Position',
                'department' => $s->department ? $s->department->department_name : 'No Department',
            ];
        }

        return response()->json([
            'employee' => [
                'name' => $user->name,
                'position' => $user->position ? $user->position->position_name : 'No Position',
                'department' => $user->department ? $user->department->department_name : 'No Department',
            ],
            'manager' => $manager,
            'teammates' => $teammates,
            'subordinates' => $subordinates,
        ]);
    }

    public function applyRegularization(Request $request)
    {
        $request->validate([
            'attendance_date' => 'required|date',
            'check_in' => 'nullable|date_format:Y-m-d H:i:s',
            'check_out' => 'nullable|date_format:Y-m-d H:i:s|after_or_equal:check_in',
            'reason' => 'required|string|max:500',
        ]);

        $user = $request->user();
        $date = $request->input('attendance_date');

        // Check if there is already a pending regularization for this date
        $existsPending = AttendanceRegularization::where('employee_id', $user->id)
            ->where('attendance_date', $date)
            ->where('status', 'pending')
            ->exists();

        if ($existsPending) {
            return response()->json([
                'message' => 'You already have a pending regularization request for this date.'
            ], 400);
        }

        // Find existing attendance
        $attendance = Attendance::where('user_id', $user->id)
            ->where('attendance_date', $date)
            ->first();

        $regularization = AttendanceRegularization::create([
            'employee_id' => $user->id,
            'attendance_id' => $attendance ? $attendance->id : null,
            'attendance_date' => $date,
            'check_in' => $request->input('check_in'),
            'check_out' => $request->input('check_out'),
            'reason' => $request->input('reason'),
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Regularization request submitted successfully.',
            'regularization' => $regularization,
        ], 201);
    }

    public function regularizationHistory(Request $request)
    {
        $user = $request->user();
        $history = AttendanceRegularization::with(['attendance'])
            ->where('employee_id', $user->id)
            ->orderBy('attendance_date', 'desc')
            ->get();

        return response()->json([
            'history' => $history
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Geofence / GPS Helper Methods
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Parse the location string sent by the mobile app.
     *
     * Accepts formats like:
     *   "Lat: 28.613940, Long: 77.209023"
     *   "28.613940, 77.209023"
     *
     * @param  string $locationString
     * @return array|null  ['latitude' => float, 'longitude' => float] or null on failure
     */
    private function parseCoordinates(?string $locationString): ?array
    {
        if (empty($locationString)) {
            return null;
        }

        // Try "Lat: xx.xxx, Long: yy.yyy" format first
        if (preg_match('/Lat:\s*([-\d.]+),\s*Long:\s*([-\d.]+)/i', $locationString, $matches)) {
            $lat = (float) $matches[1];
            $lon = (float) $matches[2];

            if ($lat >= -90 && $lat <= 90 && $lon >= -180 && $lon <= 180) {
                return ['latitude' => $lat, 'longitude' => $lon];
            }
        }

        // Fallback: plain "lat, lng" format
        $parts = array_map('trim', explode(',', $locationString));
        if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
            $lat = (float) $parts[0];
            $lon = (float) $parts[1];

            if ($lat >= -90 && $lat <= 90 && $lon >= -180 && $lon <= 180) {
                return ['latitude' => $lat, 'longitude' => $lon];
            }
        }

        return null;
    }

    /**
     * Calculate the great-circle distance between two GPS coordinates
     * using the Haversine formula.
     *
     * @param  float $lat1  Latitude of point 1  (degrees)
     * @param  float $lon1  Longitude of point 1 (degrees)
     * @param  float $lat2  Latitude of point 2  (degrees)
     * @param  float $lon2  Longitude of point 2 (degrees)
     * @return float Distance in kilometres
     */
    private function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371.0;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2)
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
           * sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadiusKm * $c, 4);
    }

    /**
     * Check whether an employee's GPS coordinates fall within the allowed
     * radius of any of their assigned office locations.
     *
     * Checks the user's primary location (location_id) and all entries
     * from the employee_geo_locations pivot table.
     *
     * @param  \App\Models\User $user
     * @param  float            $latitude   Employee's current latitude
     * @param  float            $longitude  Employee's current longitude
     * @return array  [
     *     'allowed'          => bool,
     *     'distance_km'      => float|null,
     *     'matched_location' => \App\Models\Location|null,
     *     'message'          => string,
     * ]
     */
    private function checkGeofence(User $user, float $latitude, float $longitude): array
    {
        $geoRestrictionEnabled = app(\App\Services\AttendanceMethodService::class)->isGpsValidationRequired();

        // Collect all locations the employee is allowed to clock in from
        $locations = collect();

        // Primary assigned location
        if ($user->location) {
            $locations->push($user->location);
        }

        // Additional permitted locations (pivot table)
        if ($user->relationLoaded('permittedLocations')) {
            $locations = $locations->merge($user->permittedLocations);
        } else {
            $locations = $locations->merge($user->permittedLocations()->get());
        }

        // De-duplicate by ID
        $locations = $locations->unique('id');

        // If the employee has no assigned locations at all
        if ($locations->isEmpty()) {
            if ($geoRestrictionEnabled) {
                return [
                    'allowed'          => false,
                    'distance_km'      => null,
                    'matched_location' => null,
                    'message'          => 'No office location is assigned to your profile. Please contact HR.',
                ];
            }
            // Restriction disabled — allow anyway
            return [
                'allowed'          => true,
                'distance_km'      => null,
                'matched_location' => null,
                'message'          => 'No location assigned; geo-restriction is disabled.',
            ];
        }

        // Find the nearest office location
        $nearestLocation = null;
        $nearestDistance  = PHP_FLOAT_MAX;

        foreach ($locations as $loc) {
            if ($loc->latitude === null || $loc->longitude === null) {
                continue; // Skip locations without coordinates
            }

            $dist = $this->haversineDistance(
                $latitude, $longitude,
                (float) $loc->latitude, (float) $loc->longitude
            );

            if ($dist < $nearestDistance) {
                $nearestDistance  = $dist;
                $nearestLocation = $loc;
            }
        }

        // If none of the locations have GPS coordinates configured
        if ($nearestLocation === null) {
            if ($geoRestrictionEnabled) {
                return [
                    'allowed'          => false,
                    'distance_km'      => null,
                    'matched_location' => null,
                    'message'          => 'Office location coordinates are not configured. Please contact HR.',
                ];
            }
            return [
                'allowed'          => true,
                'distance_km'      => null,
                'matched_location' => null,
                'message'          => 'Office coordinates not configured; geo-restriction is disabled.',
            ];
        }

        $allowedRadiusKm = ($nearestLocation->allowed_radius_meter ?? 200) / 1000;

        // Check if within radius
        if ($nearestDistance <= $allowedRadiusKm) {
            return [
                'allowed'          => true,
                'distance_km'      => $nearestDistance,
                'matched_location' => $nearestLocation,
                'message'          => 'Within office geofence.',
            ];
        }

        // Outside radius
        if ($geoRestrictionEnabled) {
            $distMeters = round($nearestDistance * 1000);
            $radiusMeters = $nearestLocation->allowed_radius_meter ?? 200;
            return [
                'allowed'          => false,
                'distance_km'      => $nearestDistance,
                'matched_location' => $nearestLocation,
                'message'          => "You are {$distMeters}m away from {$nearestLocation->location_name}. Allowed radius is {$radiusMeters}m. Please move closer to the office to mark attendance.",
            ];
        }

        // Restriction disabled — allow but record distance for reporting
        return [
            'allowed'          => true,
            'distance_km'      => $nearestDistance,
            'matched_location' => $nearestLocation,
            'message'          => 'Outside geofence but restriction is disabled.',
        ];
    }
}
