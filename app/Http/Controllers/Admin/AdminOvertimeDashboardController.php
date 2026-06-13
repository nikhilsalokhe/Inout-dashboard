<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OvertimeRecord;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminOvertimeDashboardController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->get('month', Carbon::now()->format('Y-m'));
        $startOfMonth = Carbon::parse($month . '-01')->startOfMonth();
        $endOfMonth   = Carbon::parse($month . '-01')->endOfMonth();

        // KPI counts
        $pending      = OvertimeRecord::where('status', 'pending')->count();
        $managerApproved = OvertimeRecord::where('status', 'manager_approved')->count();
        $hrApproved   = OvertimeRecord::where('status', 'hr_approved')->count();
        $rejected     = OvertimeRecord::where('status', 'rejected')->count();

        // This month totals
        $monthRecords = OvertimeRecord::whereBetween('date', [$startOfMonth, $endOfMonth])->get();
        $monthHours   = $monthRecords->sum('hours');
        $monthAmount  = $monthRecords->sum('amount');
        $monthPaid    = $monthRecords->where('status', 'paid')->sum('amount');

        // Approval level setting
        $approvalLevels = (int) Setting::get('overtime_approval_levels', '1');

        // Pending queue — last 50 records
        $pendingRecords = OvertimeRecord::with('user:id,name,employee_code,department_id')
            ->where('status', 'pending')
            ->latest('date')
            ->paginate(20);

        // Recent activity feed
        $recentActivity = OvertimeRecord::with('user:id,name,employee_code')
            ->whereIn('status', ['hr_approved', 'rejected', 'manager_approved', 'paid'])
            ->latest('updated_at')
            ->limit(10)
            ->get();

        return view('admin.overtime.dashboard', compact(
            'pending', 'managerApproved', 'hrApproved', 'rejected',
            'monthHours', 'monthAmount', 'monthPaid',
            'approvalLevels', 'pendingRecords', 'recentActivity', 'month'
        ));
    }
}
