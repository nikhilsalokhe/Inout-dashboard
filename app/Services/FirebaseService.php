<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class FirebaseService
{
    protected $serviceAccountFile;

    public function __construct()
    {
        $publicPath = public_path('inout-208ce-f2a1874446ea.json');
        $storagePath = storage_path('app/inout-208ce-f2a1874446ea.json');
        
        if (file_exists($storagePath)) {
            $this->serviceAccountFile = $storagePath;
        } else {
            $this->serviceAccountFile = $publicPath;
        }
    }

    /**
     * Get OAuth2 Access Token for Firebase Messaging scope.
     */
    public function getAccessToken()
    {
        return Cache::remember('fcm_access_token', 3500, function () {
            if (!file_exists($this->serviceAccountFile)) {
                throw new \Exception("Firebase service account credentials file not found at " . $this->serviceAccountFile);
            }

            $credentials = json_decode(file_get_contents($this->serviceAccountFile), true);
            if (!$credentials) {
                throw new \Exception("Invalid Firebase service account credentials JSON.");
            }

            $privateKey = $credentials['private_key'];
            $clientEmail = $credentials['client_email'];

            $now = time();
            $payload = [
                'iss' => $clientEmail,
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ];

            $header = [
                'alg' => 'RS256',
                'typ' => 'JWT',
            ];

            $base64UrlHeader = $this->base64UrlEncode(json_encode($header));
            $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));

            $signature = '';
            $success = openssl_sign(
                $base64UrlHeader . "." . $base64UrlPayload,
                $signature,
                $privateKey,
                'SHA256'
            );

            if (!$success) {
                throw new \Exception("Failed to sign JWT with private key.");
            }

            $base64UrlSignature = $this->base64UrlEncode($signature);
            $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if ($response->failed()) {
                throw new \Exception("Failed to exchange JWT for Google OAuth2 token: " . $response->body());
            }

            return $response->json('access_token');
        });
    }

    /**
     * Send Push Notification to a specific device FCM token.
     */
    public function sendNotification($token, $title, $body, $data = [])
    {
        if (empty($token)) {
            return false;
        }

        try {
            $accessToken = $this->getAccessToken();
            $credentials = json_decode(file_get_contents($this->serviceAccountFile), true);
            $projectId = $credentials['project_id'];

            // Build payload for FCM v1 API
            $message = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                ]
            ];

            if (!empty($data)) {
                $stringData = [];
                foreach ($data as $key => $value) {
                    $stringData[(string)$key] = (string)$value;
                }
                $message['message']['data'] = $stringData;
            }

            $response = Http::withToken($accessToken)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $message);

            if ($response->failed()) {
                \Log::error("FCM Send Error: " . $response->body());
                return false;
            }

            return true;
        } catch (\Exception $e) {
            \Log::error("Firebase Notification Exception: " . $e->getMessage());
            return false;
        }
    }

    private function base64UrlEncode($data)
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }
}
