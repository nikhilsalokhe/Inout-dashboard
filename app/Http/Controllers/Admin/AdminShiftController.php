<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\User;
use App\Models\Department;
use App\Helpers\AuditLogger;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminShiftController extends Controller
{
    public function index()
    {
        $shifts = Shift::orderBy('id', 'desc')->get();
        
        $assignments = ShiftAssignment::with(['employee', 'shift'])
            ->orderBy('effective_from', 'desc')
            ->paginate(15);

        return view('admin.shifts.index', compact('shifts', 'assignments'));
    }

    public function create()
    {
        return view('admin.shifts.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'shift_name' => 'required|string|max:255',
            'shift_type' => 'required|string|in:general,night,rotational,flexible',
            'start_time' => 'nullable|required_unless:shift_type,flexible|date_format:H:i',
            'end_time' => 'nullable|required_unless:shift_type,flexible|date_format:H:i',
            'grace_time_minutes' => 'required|integer|min:0',
            'half_day_time' => 'required|numeric|min:0|max:24',
            'minimum_working_hours' => 'required|numeric|min:0|max:24',
            'weekly_off_days' => 'required|array',
            'weekly_off_days.*' => 'string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
        ]);

        $shift = Shift::create([
            'shift_name' => $request->shift_name,
            'shift_type' => $request->shift_type,
            'start_time' => $request->start_time ? $request->start_time . ':00' : null,
            'end_time' => $request->end_time ? $request->end_time . ':00' : null,
            'grace_time_minutes' => $request->grace_time_minutes,
            'half_day_time' => $request->half_day_time,
            'minimum_working_hours' => $request->minimum_working_hours,
            'weekly_off_days' => implode(',', $request->weekly_off_days),
            'status' => 'active',
        ]);

        AuditLogger::log('shifts', 'create_shift', null, $shift->toArray());

        return redirect()->route('admin.shifts.index')->with('success', 'Shift policy created successfully.');
    }

    public function edit($id)
    {
        $shift = Shift::findOrFail($id);
        $weeklyOffs = explode(',', $shift->weekly_off_days);

        return view('admin.shifts.edit', compact('shift', 'weeklyOffs'));
    }

    public function update(Request $request, $id)
    {
        $shift = Shift::findOrFail($id);

        $request->validate([
            'shift_name' => 'required|string|max:255',
            'shift_type' => 'required|string|in:general,night,rotational,flexible',
            'start_time' => 'nullable|required_unless:shift_type,flexible|date_format:H:i',
            'end_time' => 'nullable|required_unless:shift_type,flexible|date_format:H:i',
            'grace_time_minutes' => 'required|integer|min:0',
            'half_day_time' => 'required|numeric|min:0|max:24',
            'minimum_working_hours' => 'required|numeric|min:0|max:24',
            'weekly_off_days' => 'required|array',
            'weekly_off_days.*' => 'string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'status' => 'required|string|in:active,inactive',
        ]);

        $oldData = $shift->toArray();
        $shift->update([
            'shift_name' => $request->shift_name,
            'shift_type' => $request->shift_type,
            'start_time' => $request->start_time ? (strlen($request->start_time) == 5 ? $request->start_time . ':00' : $request->start_time) : null,
            'end_time' => $request->end_time ? (strlen($request->end_time) == 5 ? $request->end_time . ':00' : $request->end_time) : null,
            'grace_time_minutes' => $request->grace_time_minutes,
            'half_day_time' => $request->half_day_time,
            'minimum_working_hours' => $request->minimum_working_hours,
            'weekly_off_days' => implode(',', $request->weekly_off_days),
            'status' => $request->status,
        ]);

        AuditLogger::log('shifts', 'update_shift', $oldData, $shift->fresh()->toArray());

        return redirect()->route('admin.shifts.index')->with('success', 'Shift policy configuration updated successfully.');
    }

    public function deactivate($id)
    {
        $shift = Shift::findOrFail($id);
        $oldData = $shift->toArray();
        $shift->update(['status' => 'inactive']);

        AuditLogger::log('shifts', 'deactivate_shift', $oldData, $shift->fresh()->toArray());

        return redirect()->route('admin.shifts.index')->with('success', 'Shift deactivated successfully.');
    }

    public function assignView()
    {
        $shifts = Shift::where('status', 'active')->orderBy('shift_name')->get();
        $departments = Department::where('status', 'active')->orderBy('department_name')->get();
        $employees = User::where('role', 'employee')->where('status', 'active')->orderBy('name')->get();

        return view('admin.shifts.assign', compact('shifts', 'departments', 'employees'));
    }

    public function assignStore(Request $request)
    {
        $request->validate([
            'shift_id' => 'required|exists:shifts,id',
            'assignment_type' => 'required|string|in:individual,department,multiple',
            'employee_id' => 'required_if:assignment_type,individual|nullable|exists:users,id',
            'department_id' => 'required_if:assignment_type,department|nullable|exists:departments,id',
            'employee_ids' => 'required_if:assignment_type,multiple|nullable|array',
            'employee_ids.*' => 'exists:users,id',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
        ]);

        $employeeIds = [];

        if ($request->assignment_type === 'individual') {
            $employeeIds[] = $request->employee_id;
        } elseif ($request->assignment_type === 'department') {
            $employeeIds = User::where('department_id', $request->department_id)
                ->where('status', 'active')
                ->pluck('id')
                ->toArray();
                
            if (empty($employeeIds)) {
                return redirect()->back()->withInput()->with('error', 'No active employees were discovered inside the selected department.');
            }
        } elseif ($request->assignment_type === 'multiple') {
            $employeeIds = $request->employee_ids;
        }

        $effectiveFrom = $request->effective_from;
        $effectiveTo = $request->effective_to;
        $shiftId = $request->shift_id;

        foreach ($employeeIds as $empId) {
            // End any current active shift assignment for this employee
            ShiftAssignment::where('employee_id', $empId)
                ->whereNull('effective_to')
                ->update(['effective_to' => Carbon::parse($effectiveFrom)->subDay()->toDateString()]);

            // Save the new assignment
            $assignment = ShiftAssignment::create([
                'employee_id' => $empId,
                'shift_id' => $shiftId,
                'effective_from' => $effectiveFrom,
                'effective_to' => $effectiveTo,
            ]);

            AuditLogger::log('shifts', 'assign_shift', null, $assignment->toArray());
        }

        return redirect()->route('admin.shifts.index')->with('success', 'Shifts assigned successfully to the chosen employees.');
    }
}
