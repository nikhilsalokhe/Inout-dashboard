<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Verify role
        if (!$user->isEmployee()) {
            throw ValidationException::withMessages([
                'email' => ['Access denied. Only employee accounts are permitted to log in via mobile.'],
            ]);
        }

        // Verify active status
        if ($user->status === 'inactive' || $user->isTerminated()) {
            throw ValidationException::withMessages([
                'email' => ['Your employee account has been deactivated or terminated.'],
            ]);
        }

        // Validate device restriction if enabled
        $allowSingleDevice = \App\Models\Setting::get('allow_single_device', '0') == '1';
        if ($allowSingleDevice) {
            $incomingDeviceId = $request->input('device_id') ?: $request->header('X-Device-ID');
            if (empty($incomingDeviceId)) {
                return response()->json(['message' => 'Device identification (device_id) is required.'], 400);
            }
            if (empty($user->device_id)) {
                $user->device_id = $incomingDeviceId;
                $user->save();
            } elseif ($user->device_id !== $incomingDeviceId) {
                return response()->json(['message' => 'Unregistered device. Please contact your HR department for device registration reset.'], 403);
            }
        }

        return response()->json([
            'token' => $user->createToken($request->device_name)->plainTextToken,
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Register/update the FCM device token for push notifications.
     */
    public function registerFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string|max:500',
        ]);

        $user = $request->user();
        $user->fcm_token = $request->fcm_token;
        $user->save();

        return response()->json([
            'message' => 'FCM token registered successfully',
        ]);
    }
}
