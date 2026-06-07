<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\Location;
use App\Models\Department;
use App\Models\User;
use App\Helpers\AuditLogger;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    /**
     * Display a listing of the holidays and forms for management.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $holidays = Holiday::with(['location', 'department', 'employee'])
            ->orderBy('holiday_date', 'asc')
            ->get();

        $locations = Location::where('status', 'active')->orderBy('location_name')->get();
        $departments = Department::where('status', 'active')->orderBy('department_name')->get();
        $employees = User::active()->where('role', 'employee')->orderBy('name')->get();

        return view('admin.holidays.index', compact('holidays', 'locations', 'departments', 'employees'));
    }

    /**
     * Store a newly created holiday in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'holiday_name' => 'required|string|max:150',
            'holiday_date' => 'required|date',
            'holiday_type' => 'required|string|in:gazetted,optional',
            'assignment_target' => 'required|string|in:all,location,department,employee',
            'location_id' => 'required_if:assignment_target,location|nullable|exists:locations,id',
            'department_id' => 'required_if:assignment_target,department|nullable|exists:departments,id',
            'employee_id' => 'required_if:assignment_target,employee|nullable|exists:users,id',
        ]);

        $holidayData = [
            'holiday_name' => $validated['holiday_name'],
            'holiday_date' => $validated['holiday_date'],
            'holiday_type' => $validated['holiday_type'],
        ];

        // Assign target columns
        if ($validated['assignment_target'] === 'location') {
            $holidayData['location_id'] = $validated['location_id'];
        } elseif ($validated['assignment_target'] === 'department') {
            $holidayData['department_id'] = $validated['department_id'];
        } elseif ($validated['assignment_target'] === 'employee') {
            $holidayData['employee_id'] = $validated['employee_id'];
        }

        $holiday = Holiday::create($holidayData);

        AuditLogger::log(
            'holiday',
            'create_holiday',
            null,
            $holiday->toArray()
        );

        return redirect()->route('admin.holidays.index')
            ->with('success', 'Holiday created and assigned successfully.');
    }

    /**
     * Remove the specified holiday from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $holiday = Holiday::findOrFail($id);
        $oldData = $holiday->toArray();
        $holiday->delete();

        AuditLogger::log(
            'holiday',
            'delete_holiday',
            $oldData,
            null
        );

        return redirect()->route('admin.holidays.index')
            ->with('success', 'Holiday deleted successfully.');
    }
}
