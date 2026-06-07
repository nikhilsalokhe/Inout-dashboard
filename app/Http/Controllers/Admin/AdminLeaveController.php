<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeavePolicy;
use App\Models\LeaveBalance;
use App\Models\LeaveApplication;
use App\Models\User;
use App\Helpers\AuditLogger;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminLeaveController extends Controller
{
    /**
     * Display leave policies configuration workspace.
     */
    public function policiesIndex()
    {
        $policies = LeavePolicy::all();
        return view('admin.leaves.index', compact('policies'));
    }

    /**
     * Create a new leave policy.
     */
    public function policiesStore(Request $request)
    {
        $request->validate([
            'leave_name'         => 'required|string|max:100',
            'leave_code'         => 'required|string|max:10|unique:leave_policies',
            'leave_type'         => 'required|in:paid,unpaid',
            'total_yearly_leave' => 'required|integer|min:0',
            'monthly_credit'     => 'required|numeric|min:0|max:10',
            'carry_forward'      => 'required|boolean',
            'max_carry_forward'  => 'required_if:carry_forward,1|integer|min:0',
            'requires_approval'  => 'required|boolean',
        ]);

        $data = $request->all();
        if ($request->carry_forward == '0') {
            $data['max_carry_forward'] = 0;
        }

        $policy = LeavePolicy::create($data);

        AuditLogger::log('leaves', 'create_policy', null, $policy->toArray());

        return redirect()->route('admin.leaves.policies')->with('success', 'Leave policy created successfully.');
    }

    /**
     * Update an existing leave policy.
     */
    public function policiesUpdate(Request $request, $id)
    {
        $policy = LeavePolicy::findOrFail($id);

        $request->validate([
            'leave_name'         => 'required|string|max:100',
            'leave_code'         => 'required|string|max:10|unique:leave_policies,leave_code,' . $id,
            'leave_type'         => 'required|in:paid,unpaid',
            'total_yearly_leave' => 'required|integer|min:0',
            'monthly_credit'     => 'required|numeric|min:0|max:10',
            'carry_forward'      => 'required|boolean',
            'max_carry_forward'  => 'required_if:carry_forward,1|integer|min:0',
            'requires_approval'  => 'required|boolean',
            'status'             => 'required|in:active,inactive',
        ]);

        $oldData = $policy->toArray();

        $data = $request->all();
        if ($request->carry_forward == '0') {
            $data['max_carry_forward'] = 0;
        }

        $policy->update($data);

        AuditLogger::log('leaves', 'update_policy', $oldData, $policy->toArray());

        return redirect()->route('admin.leaves.policies')->with('success', 'Leave policy updated successfully.');
    }

    /**
     * Display all leave balances across the organization.
     */
    public function balancesIndex()
    {
        $balances = LeaveBalance::with(['employee.department', 'leavePolicy'])
            ->orderBy('year', 'desc')
            ->paginate(20);
        return view('admin.leaves.balances', compact('balances'));
    }

    /**
     * Display manager approval queue.
     */
    public function applicationsIndex()
    {
        $user = auth()->user();

        // Admins can see all, managers can see their subordinates' applications
        if ($user->role === 'admin') {
            $applications = LeaveApplication::with(['employee.department', 'leavePolicy', 'approver'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);
        } else {
            $applications = LeaveApplication::with(['employee.department', 'leavePolicy', 'approver'])
                ->whereHas('employee', function($q) use ($user) {
                    $q->where('reporting_manager_id', $user->id);
                })
                ->orderBy('created_at', 'desc')
                ->paginate(20);
        }

        return view('admin.leaves.applications', compact('applications'));
    }

    /**
     * Web Workflow: Approve leave application.
     */
    public function approveApplication(Request $request, $id)
    {
        $request->validate([
            'remarks' => 'nullable|string|max:250',
        ]);

        $app = LeaveApplication::with(['employee', 'leavePolicy'])->findOrFail($id);

        if ($app->status !== 'pending') {
            return redirect()->back()->with('error', 'This leave request has already been processed.');
        }

        // Balance Check
        $year = Carbon::parse($app->from_date)->year;
        if ($app->leavePolicy->leave_type === 'paid') {
            $balance = LeaveBalance::where('employee_id', $app->employee_id)
                ->where('leave_policy_id', $app->leave_policy_id)
                ->where('year', $year)
                ->first();

            if (!$balance || $balance->remaining_leave < $app->total_days) {
                return redirect()->back()->with('error', 'Cannot approve. Employee has insufficient balance.');
            }

            // Deduct balance
            $balance->used_leave += $app->total_days;
            $balance->remaining_leave -= $app->total_days;
            $balance->save();
        }

        $app->status = 'approved';
        $app->approved_by = auth()->id();
        $app->approved_at = Carbon::now();
        $app->remarks = $request->remarks;
        $app->save();

        AuditLogger::log('leaves', 'approve_leave', ['id' => $id, 'status' => 'pending'], $app->toArray());

        return redirect()->back()->with('success', 'Leave application approved successfully.');
    }

    /**
     * Web Workflow: Reject leave application.
     */
    public function rejectApplication(Request $request, $id)
    {
        $request->validate([
            'remarks' => 'nullable|string|max:250',
        ]);

        $app = LeaveApplication::findOrFail($id);

        if ($app->status !== 'pending') {
            return redirect()->back()->with('error', 'This leave request has already been processed.');
        }

        $app->status = 'rejected';
        $app->approved_by = auth()->id();
        $app->approved_at = Carbon::now();
        $app->remarks = $request->remarks;
        $app->save();

        AuditLogger::log('leaves', 'reject_leave', ['id' => $id, 'status' => 'pending'], $app->toArray());

        return redirect()->back()->with('success', 'Leave application rejected successfully.');
    }
}
