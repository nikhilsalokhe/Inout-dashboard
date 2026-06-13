<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OvertimePolicy;
use App\Models\OvertimePolicyAssignment;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;

class AdminOvertimeAssignmentController extends Controller
{
    public function index()
    {
        $assignments = OvertimePolicyAssignment::with(['policy', 'assignable'])->latest()->paginate(20);
        $policies = OvertimePolicy::where('is_active', true)->get();
        $departments = Department::where('status', 'active')->get();
        $users = User::active()->get();
        
        return view('admin.overtime.assignments.index', compact('assignments', 'policies', 'departments', 'users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'policy_id' => 'required|exists:overtime_policies,id',
            'assignable_type' => 'required|in:Department,User',
            'assignable_id' => 'required|integer',
        ]);

        $type = $request->assignable_type === 'Department' ? Department::class : User::class;

        // Check if assignment already exists
        $exists = OvertimePolicyAssignment::where('assignable_type', $type)
            ->where('assignable_id', $request->assignable_id)
            ->first();

        if ($exists) {
            $exists->update(['policy_id' => $request->policy_id]);
            return back()->with('success', 'Policy assignment updated successfully.');
        }

        OvertimePolicyAssignment::create([
            'policy_id' => $request->policy_id,
            'assignable_type' => $type,
            'assignable_id' => $request->assignable_id,
        ]);

        return back()->with('success', 'Policy assigned successfully.');
    }

    public function destroy(OvertimePolicyAssignment $assignment)
    {
        $assignment->delete();
        return back()->with('success', 'Policy assignment removed successfully.');
    }
}
