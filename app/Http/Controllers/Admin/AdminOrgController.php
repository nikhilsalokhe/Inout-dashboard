<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Position;
use App\Models\Location;
use App\Helpers\AuditLogger;
use Illuminate\Http\Request;

class AdminOrgController extends Controller
{
    /**
     * Display the combined organization management page.
     */
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'departments');

        $departments = Department::withCount('employees')->orderBy('department_name')->get();
        $positions = Position::with('department')->withCount('employees')->orderBy('position_name')->get();
        $locations = Location::withCount('employees')->orderBy('location_name')->get();

        // For the positions form – need department list
        $activeDepartments = Department::where('status', 'active')->orderBy('department_name')->get();

        return view('admin.organization.index', compact('departments', 'positions', 'locations', 'activeDepartments', 'tab'));
    }

    // ─── DEPARTMENTS ─────────────────────────────────────────────

    /**
     * Store a new department.
     */
    public function storeDepartment(Request $request)
    {
        $request->validate([
            'department_name' => 'required|string|max:100|unique:departments',
            'attendance_method' => 'nullable|string|in:face,qr,face_or_qr,face_and_qr,manual,gps_only',
        ]);

        $department = Department::create([
            'department_name' => $request->department_name,
            'attendance_method' => $request->attendance_method,
            'status' => 'active',
        ]);

        AuditLogger::log('organization', 'create_department', null, $department->toArray());

        return redirect()->route('admin.organization.index', ['tab' => 'departments'])->with('success', 'Department created successfully.');
    }

    /**
     * Update an existing department.
     */
    public function updateDepartment(Request $request, $id)
    {
        $department = Department::findOrFail($id);

        $request->validate([
            'department_name' => 'required|string|max:100|unique:departments,department_name,' . $id,
            'status' => 'required|in:active,inactive',
            'attendance_method' => 'nullable|string|in:face,qr,face_or_qr,face_and_qr,manual,gps_only',
        ]);

        $oldData = $department->toArray();
        $department->update($request->only('department_name', 'status', 'attendance_method'));

        AuditLogger::log('organization', 'update_department', $oldData, $department->toArray());

        return redirect()->route('admin.organization.index', ['tab' => 'departments'])->with('success', 'Department updated successfully.');
    }

    /**
     * Delete a department.
     */
    public function destroyDepartment($id)
    {
        $department = Department::withCount('employees')->findOrFail($id);

        if ($department->employees_count > 0) {
            return redirect()->route('admin.organization.index', ['tab' => 'departments'])
                ->with('error', "Cannot delete \"{$department->department_name}\". {$department->employees_count} employee(s) are currently assigned to this department.");
        }

        $oldData = $department->toArray();
        $department->delete();

        AuditLogger::log('organization', 'delete_department', $oldData, null);

        return redirect()->route('admin.organization.index', ['tab' => 'departments'])->with('success', 'Department deleted successfully.');
    }

    // ─── POSITIONS (DESIGNATIONS) ────────────────────────────────

    /**
     * Store a new position/designation.
     */
    public function storePosition(Request $request)
    {
        $request->validate([
            'position_name' => 'required|string|max:100',
            'department_id' => 'required|exists:departments,id',
        ]);

        $position = Position::create([
            'position_name' => $request->position_name,
            'department_id' => $request->department_id,
            'status' => 'active',
        ]);

        AuditLogger::log('organization', 'create_position', null, $position->toArray());

        return redirect()->route('admin.organization.index', ['tab' => 'designations'])->with('success', 'Designation created successfully.');
    }

    /**
     * Update an existing position/designation.
     */
    public function updatePosition(Request $request, $id)
    {
        $position = Position::findOrFail($id);

        $request->validate([
            'position_name' => 'required|string|max:100',
            'department_id' => 'required|exists:departments,id',
            'status' => 'required|in:active,inactive',
        ]);

        $oldData = $position->toArray();
        $position->update($request->only('position_name', 'department_id', 'status'));

        AuditLogger::log('organization', 'update_position', $oldData, $position->toArray());

        return redirect()->route('admin.organization.index', ['tab' => 'designations'])->with('success', 'Designation updated successfully.');
    }

    /**
     * Delete a position/designation.
     */
    public function destroyPosition($id)
    {
        $position = Position::withCount('employees')->findOrFail($id);

        if ($position->employees_count > 0) {
            return redirect()->route('admin.organization.index', ['tab' => 'designations'])
                ->with('error', "Cannot delete \"{$position->position_name}\". {$position->employees_count} employee(s) hold this designation.");
        }

        $oldData = $position->toArray();
        $position->delete();

        AuditLogger::log('organization', 'delete_position', $oldData, null);

        return redirect()->route('admin.organization.index', ['tab' => 'designations'])->with('success', 'Designation deleted successfully.');
    }

    // ─── LOCATIONS ───────────────────────────────────────────────

    /**
     * Store a new location.
     */
    public function storeLocation(Request $request)
    {
        $request->validate([
            'location_name' => 'required|string|max:150|unique:locations',
            'address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'allowed_radius_meter' => 'nullable|integer|min:0',
        ]);

        $location = Location::create([
            'location_name' => $request->location_name,
            'address' => $request->address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'allowed_radius_meter' => $request->allowed_radius_meter ?? 200,
            'status' => 'active',
        ]);

        AuditLogger::log('organization', 'create_location', null, $location->toArray());

        return redirect()->route('admin.organization.index', ['tab' => 'locations'])->with('success', 'Location created successfully.');
    }

    /**
     * Update an existing location.
     */
    public function updateLocation(Request $request, $id)
    {
        $location = Location::findOrFail($id);

        $request->validate([
            'location_name' => 'required|string|max:150|unique:locations,location_name,' . $id,
            'address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'allowed_radius_meter' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $oldData = $location->toArray();
        $data = $request->only('location_name', 'address', 'latitude', 'longitude', 'allowed_radius_meter', 'status');
        $data['allowed_radius_meter'] = $data['allowed_radius_meter'] ?? 200;
        $location->update($data);

        AuditLogger::log('organization', 'update_location', $oldData, $location->toArray());

        return redirect()->route('admin.organization.index', ['tab' => 'locations'])->with('success', 'Location updated successfully.');
    }

    public function destroyLocation($id)
    {
        $location = Location::withCount('employees')->findOrFail($id);

        if ($location->employees_count > 0) {
            return redirect()->route('admin.organization.index', ['tab' => 'locations'])
                ->with('error', "Cannot delete \"{$location->location_name}\". {$location->employees_count} employee(s) are assigned to this location.");
        }

        $oldData = $location->toArray();
        $location->delete();

        AuditLogger::log('organization', 'delete_location', $oldData, null);

        return redirect()->route('admin.organization.index', ['tab' => 'locations'])->with('success', 'Location deleted successfully.');
    }

    /**
     * Show QR Code page before downloading.
     */
    public function showQr($id)
    {
        $location = Location::findOrFail($id);

        $payload = json_encode([
            'type' => 'static_location_qr',
            'location_id' => $location->id,
            'name' => $location->location_name,
            'lat' => $location->latitude,
            'lng' => $location->longitude,
            'timestamp' => now()->timestamp
        ]);

        $options = new \chillerlan\QRCode\QROptions([
            'outputType' => \chillerlan\QRCode\Output\QRMarkupSVG::class,
            'eccLevel' => \chillerlan\QRCode\Common\EccLevel::H,
            'svgViewBoxSize' => 500,
            'addQuietzone' => true,
            'imageBase64' => true,
        ]);

        $qrCodeBase64 = (new \chillerlan\QRCode\QRCode($options))->render($payload);

        return view('admin.organization.show_qr', compact('location', 'qrCodeBase64'));
    }

    /**
     * Download Static QR Code for Location Attendance.
     */
    public function downloadQr($id)
    {
        $location = Location::findOrFail($id);

        // Define the payload for the QR code
        $payload = json_encode([
            'type' => 'static_location_qr',
            'location_id' => $location->id,
            'name' => $location->location_name,
            'lat' => $location->latitude,
            'lng' => $location->longitude,
            'timestamp' => now()->timestamp
        ]);

        $options = new \chillerlan\QRCode\QROptions([
            'outputType' => \chillerlan\QRCode\Output\QRMarkupSVG::class,
            'eccLevel' => \chillerlan\QRCode\Common\EccLevel::H,
            'svgViewBoxSize' => 500,
            'addQuietzone' => true,
            'imageBase64' => false,
        ]);

        $qrCode = (new \chillerlan\QRCode\QRCode($options))->render($payload);

        $filename = 'location_qr_' . str_replace(' ', '_', strtolower($location->location_name)) . '.svg';

        return response($qrCode)
            ->header('Content-type', 'image/svg+xml')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
