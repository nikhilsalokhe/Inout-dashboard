<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OvertimeRecord;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminOvertimeRequestController extends Controller
{
    /**
     * List all overtime records with filters.
     */
    public function index(Request $request)
    {
        $approvalLevels = (int) Setting::get('overtime_approval_levels', '1');

        $query = OvertimeRecord::with('user:id,name,employee_code')
            ->latest('date');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('overtime_type', $request->type);
        }
        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        $records = $query->paginate(25)->withQueryString();

        return view('admin.overtime.requests.index', compact('records', 'approvalLevels'));
    }

    /**
     * Manager-level approval (Level 1).
     */
    public function managerApprove(OvertimeRecord $record)
    {
        if ($record->status !== 'pending') {
            return back()->with('error', 'Record is not in a pending state.');
        }

        $approvalLevels = (int) Setting::get('overtime_approval_levels', '1');

        if ($approvalLevels >= 2) {
            // Multi-level: go to manager_approved → awaiting HR
            $record->update([
                'status'     => 'manager_approved',
                'manager_id' => Auth::id(),
            ]);
            $message = 'Approved at Manager level. Awaiting HR final approval.';
        } else {
            // Single-level: direct HR approve
            $record->update([
                'status' => 'hr_approved',
                'hr_id'  => Auth::id(),
            ]);
            $message = 'Overtime record approved successfully.';
        }

        return back()->with('success', $message);
    }

    /**
     * HR-level final approval (Level 2).
     */
    public function hrApprove(OvertimeRecord $record)
    {
        if (!in_array($record->status, ['pending', 'manager_approved'])) {
            return back()->with('error', 'Record cannot be approved at this stage.');
        }

        $record->update([
            'status' => 'hr_approved',
            'hr_id'  => Auth::id(),
        ]);

        return back()->with('success', 'Overtime record has been HR-approved and queued for payroll.');
    }

    /**
     * Reject an overtime record.
     */
    public function reject(Request $request, OvertimeRecord $record)
    {
        $request->validate(['notes' => 'nullable|string|max:500']);

        if (in_array($record->status, ['paid', 'processed'])) {
            return back()->with('error', 'Cannot reject a paid or processed record.');
        }

        $record->update([
            'status' => 'rejected',
            'notes'  => $request->notes ?? 'Rejected by admin.',
        ]);

        return back()->with('success', 'Overtime record rejected.');
    }

    /**
     * Bulk approve all pending records.
     */
    public function bulkApprove(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return back()->with('error', 'No records selected.');
        }

        $approvalLevels = (int) Setting::get('overtime_approval_levels', '1');
        $newStatus = $approvalLevels >= 2 ? 'manager_approved' : 'hr_approved';

        OvertimeRecord::whereIn('id', $ids)
            ->where('status', 'pending')
            ->update([
                'status'     => $newStatus,
                'manager_id' => Auth::id(),
            ]);

        return back()->with('success', count($ids) . ' record(s) approved.');
    }
}
