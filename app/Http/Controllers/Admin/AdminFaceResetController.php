<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FaceResetRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AdminFaceResetController extends Controller
{
    public function index()
    {
        // Paginate requests showing pending first, then latest
        $requests = FaceResetRequest::with('employee')
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected') ASC")
            ->orderBy('requested_at', 'desc')
            ->paginate(10);

        return view('admin.face-resets.index', compact('requests'));
    }

    public function show($id)
    {
        $request = FaceResetRequest::with('employee')->findOrFail($id);
        return view('admin.face-resets.show', compact('request'));
    }

    public function approve(Request $request, $id)
    {
        $resetRequest = FaceResetRequest::findOrFail($id);

        if ($resetRequest->status !== 'pending') {
            return redirect()->route('admin.face-resets.show', $id)
                ->with('error', 'This request has already been processed.');
        }

        $employee = $resetRequest->employee;

        // Path to the new face image
        $newImagePath = $resetRequest->new_face_image;
        if (!Storage::disk('public')->exists($newImagePath)) {
            return redirect()->route('admin.face-resets.show', $id)
                ->with('error', 'The uploaded face image file could not be found on storage.');
        }

        // Get base64 string
        $imageFileContents = Storage::disk('public')->get($newImagePath);
        $imageBase64 = base64_encode($imageFileContents);

        // Call AI Service to generate face encoding
        $aiUrl = config('services.ai.url', 'http://127.0.0.1:8000');

        try {
            $response = Http::asForm()->post($aiUrl . '/get-encoding', [
                'image' => $imageBase64,
            ]);
        } catch (\Exception $e) {
            return redirect()->route('admin.face-resets.show', $id)
                ->with('error', 'Failed to connect to the AI face recognition service: ' . $e->getMessage());
        }

        if ($response->failed()) {
            $detail = $response->json('detail') ?? 'Failed to parse face encoding from the image.';
            return redirect()->route('admin.face-resets.show', $id)
                ->with('error', 'AI Service Error: ' . $detail);
        }

        $encoding = $response->json('encoding');
        if (empty($encoding)) {
            return redirect()->route('admin.face-resets.show', $id)
                ->with('error', 'No face was detected in the new image by the AI service.');
        }

        // Move the file from face_resets to faces directory
        $newFilename = basename($newImagePath);
        $destinationPath = 'faces/' . $newFilename;
        
        Storage::disk('public')->copy($newImagePath, $destinationPath);

        // Update employee's active biometrics
        $employee->face_image = $destinationPath;
        $employee->face_encoding = $encoding;
        $employee->save();

        // Update face reset request
        $resetRequest->status = 'approved';
        $resetRequest->approved_by = auth()->id();
        $resetRequest->approved_at = Carbon::now();
        $resetRequest->remarks = $request->input('remarks');
        $resetRequest->save();

        return redirect()->route('admin.face-resets.index')
            ->with('success', 'Face reset request approved successfully. Employee\'s biometrics profile has been updated.');
    }

    public function reject(Request $request, $id)
    {
        $resetRequest = FaceResetRequest::findOrFail($id);

        if ($resetRequest->status !== 'pending') {
            return redirect()->route('admin.face-resets.show', $id)
                ->with('error', 'This request has already been processed.');
        }

        // Update request status to rejected
        $resetRequest->status = 'rejected';
        $resetRequest->approved_by = auth()->id();
        $resetRequest->approved_at = Carbon::now();
        $resetRequest->remarks = $request->input('remarks');
        $resetRequest->save();

        return redirect()->route('admin.face-resets.index')
            ->with('success', 'Face reset request rejected. Employee\'s previous biometrics profile remains active.');
    }
}
