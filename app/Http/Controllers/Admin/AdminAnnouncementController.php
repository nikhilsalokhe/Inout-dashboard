<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Department;
use App\Models\Location;
use App\Models\User;
use App\Models\Notification;
use App\Services\FirebaseService;
use App\Helpers\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminAnnouncementController extends Controller
{
    /**
     * Display a listing of announcements.
     */
    public function index(Request $request)
    {
        $announcements = Announcement::with(['department', 'location', 'creator'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.announcements.index', compact('announcements'));
    }

    /**
     * Show the form for creating a new announcement.
     */
    public function create()
    {
        $departments = Department::where('status', 'active')->orderBy('department_name')->get();
        $locations = Location::where('status', 'active')->orderBy('location_name')->get();

        return view('admin.announcements.create', compact('departments', 'locations'));
    }

    /**
     * Store a newly created announcement in storage, dispatch push notifications,
     * and log the action.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'target_type' => 'required|string|in:all,department,location',
            'target_department_id' => 'required_if:target_type,department|nullable|exists:departments,id',
            'target_location_id' => 'required_if:target_type,location|nullable|exists:locations,id',
        ]);

        $announcementData = [
            'title' => $validated['title'],
            'content' => $validated['content'],
            'status' => 'published',
            'created_by' => auth()->id(),
        ];

        if ($validated['target_type'] === 'department') {
            $announcementData['target_department_id'] = $validated['target_department_id'];
        } elseif ($validated['target_type'] === 'location') {
            $announcementData['target_location_id'] = $validated['target_location_id'];
        }

        $announcement = Announcement::create($announcementData);

        // Fetch targeted employees
        $query = User::where('role', 'employee')->where('status', 'active');
        if ($validated['target_type'] === 'department') {
            $query->where('department_id', $validated['target_department_id']);
        } elseif ($validated['target_type'] === 'location') {
            $query->where('location_id', $validated['target_location_id']);
        }
        $employees = $query->get();

        $firebaseService = new FirebaseService();

        // Broadcast to target employees
        foreach ($employees as $employee) {
            // Create database notification record
            Notification::create([
                'user_id' => $employee->id,
                'title' => 'Announcement: ' . $validated['title'],
                'description' => Str::limit($validated['content'], 150),
                'type' => 'announcement',
                'unread' => true,
            ]);

            // Dispatch Firebase Cloud Message
            if (!empty($employee->fcm_token)) {
                $firebaseService->sendNotification(
                    $employee->fcm_token,
                    'New Announcement: ' . $validated['title'],
                    Str::limit($validated['content'], 100),
                    [
                        'type' => 'announcement',
                        'announcement_id' => $announcement->id
                    ]
                );
            }
        }

        // Log the action
        AuditLogger::log(
            'announcement',
            'create_announcement',
            null,
            $announcement->toArray()
        );

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Announcement published and broadcasted successfully to ' . $employees->count() . ' employees.');
    }

    /**
     * Remove the specified announcement from storage.
     */
    public function destroy($id)
    {
        $announcement = Announcement::findOrFail($id);
        $oldData = $announcement->toArray();
        $announcement->delete();

        // Log the action
        AuditLogger::log(
            'announcement',
            'delete_announcement',
            $oldData,
            null
        );

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Announcement deleted successfully.');
    }
}
