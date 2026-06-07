<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FaceRecognitionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_user_can_register_face_successfully()
    {
        // Mock the AI Service /get-encoding call
        Http::fake([
            'http://127.0.0.1:8001/get-encoding' => Http::response([
                'encoding' => array_fill(0, 128, 0.1)
            ], 200),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/register-face', [
                'face_image' => UploadedFile::fake()->create('face.jpg', 100, 'image/jpeg'),
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'face_path',
            ]);

        $user->refresh();
        $this->assertNotNull($user->face_image);
        $this->assertNotNull($user->face_encoding);
        $this->assertEquals(array_fill(0, 128, 0.1), $user->face_encoding);
    }

    public function test_face_registration_fails_if_no_face_detected()
    {
        Http::fake([
            'http://127.0.0.1:8001/get-encoding' => Http::response([
                'detail' => 'No face detected in image'
            ], 400),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/register-face', [
                'face_image' => UploadedFile::fake()->create('non_face.jpg', 100, 'image/jpeg'),
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'No face detected in image'
            ]);
    }

    public function test_user_can_clock_in_successfully_with_matching_face()
    {
        Http::fake([
            'http://127.0.0.1:8001/verify-face' => Http::response([
                'match' => true,
                'distance' => 0.15,
                'confidence' => 90.0,
                'liveness_passed' => true,
            ], 200),
        ]);

        $user = User::factory()->create([
            'face_encoding' => array_fill(0, 128, 0.1),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/check-in', [
                'image' => UploadedFile::fake()->create('clock_in.jpg', 100, 'image/jpeg'),
                'location' => 'Lat: 40.7128, Long: -74.0060',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'attendance' => [
                    'id',
                    'user_id',
                    'check_in',
                    'location',
                    'image',
                ]
            ]);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'location' => 'Lat: 40.7128, Long: -74.0060',
        ]);
    }

    public function test_user_cannot_clock_in_with_non_matching_face()
    {
        Http::fake([
            'http://127.0.0.1:8001/verify-face' => Http::response([
                'match' => false,
                'distance' => 0.65,
                'confidence' => 45.0,
                'liveness_passed' => true,
            ], 200),
        ]);

        $user = User::factory()->create([
            'face_encoding' => array_fill(0, 128, 0.1),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/check-in', [
                'image' => UploadedFile::fake()->create('clock_in.jpg', 100, 'image/jpeg'),
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Unauthorized Face Detected. Only the registered employee can mark attendance.'
            ]);

        $this->assertDatabaseMissing('attendances', [
            'user_id' => $user->id,
        ]);
    }
}
