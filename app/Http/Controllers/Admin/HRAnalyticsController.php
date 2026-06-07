<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use App\Models\LeaveApplication;
use App\Models\Payroll;
use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HRAnalyticsController extends Controller
{
    /**
     * Show HR Analytics Dashboard with beautiful stats and charts.
     */
    public function dashboard(Request $request)
    {
        $year = $request->get('year', date('Y'));

        // 1. Monthly Payroll Spend Trend (Current Year)
        $monthlySpend = Payroll::select('month', DB::raw('SUM(net_salary) as total_spent'))
            ->where('year', $year)
            ->where('status', 'Paid')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total_spent', 'month')
            ->toArray();

        $payrollTrendData = [];
        for ($m = 1; $m <= 12; $m++) {
            $payrollTrendData[] = $monthlySpend[$m] ?? 0.00;
        }

        // 2. Departmental Salary Distribution
        $deptPayroll = Payroll::with(['employee.department'])
            ->where('year', $year)
            ->where('status', 'Paid')
            ->get()
            ->groupBy(function($pr) {
                return $pr->employee->department ? $pr->employee->department->department_name : 'Unassigned';
            })->map(function($rows) {
                return $rows->sum('net_salary');
            });

        $deptNames = $deptPayroll->keys()->toArray();
        $deptSpends = $deptPayroll->values()->toArray();

        // 3. Monthly Attendance Trends (Last 6 Months)
        $sixMonthsAgo = Carbon::today()->subMonths(5)->startOfMonth()->toDateString();
        $today = Carbon::today()->toDateString();

        $attendanceLogs = Attendance::select('status', 'attendance_date')
            ->whereBetween('attendance_date', [$sixMonthsAgo, $today])
            ->get();

        $attendanceTrendLabels = [];
        $presentCounts = [];
        $lateCounts = [];
        $absentCounts = [];

        for ($i = 5; $i >= 0; $i--) {
            $monthDate = Carbon::today()->subMonths($i);
            $attendanceTrendLabels[] = $monthDate->format('M Y');
            
            $start = $monthDate->copy()->startOfMonth()->toDateString();
            $end = $monthDate->copy()->endOfMonth()->toDateString();
            
            $monthLogs = $attendanceLogs->filter(function($log) use ($start, $end) {
                return $log->attendance_date >= $start && $log->attendance_date <= $end;
            });

            $presentCounts[] = $monthLogs->whereIn('status', ['present'])->count();
            $lateCounts[] = $monthLogs->where('status', 'late')->count();
            $absentCounts[] = $monthLogs->where('status', 'absent')->count();
        }

        // 4. Leave Utilization by Policy (Current Year)
        $leaveUtil = LeaveApplication::where('status', 'approved')
            ->whereYear('from_date', $year)
            ->with('leavePolicy')
            ->get()
            ->groupBy(function($leave) {
                return $leave->leavePolicy ? $leave->leavePolicy->leave_name : 'General Leave';
            })->map(function($rows) {
                return $rows->sum('total_days');
            });

        $leaveLabels = $leaveUtil->keys()->toArray();
        $leaveDays = $leaveUtil->values()->toArray();

        // Combined KPIs
        $totalAnnualSpend = Payroll::where('year', $year)->where('status', 'Paid')->sum('net_salary');
        $averageAttendanceRate = 0;
        
        $totalExpected = User::where('role', 'employee')->where('status', 'active')->count() * 26; // Approx. 26 working days
        if ($totalExpected > 0) {
            $totalPresent = Attendance::where('attendance_date', '>=', Carbon::today()->startOfMonth()->toDateString())
                ->whereIn('status', ['present', 'late', 'half_day'])
                ->count();
            $averageAttendanceRate = round(($totalPresent / max(1, $totalExpected)) * 100, 1);
        }

        return view('admin.analytics.dashboard', compact(
            'year', 'payrollTrendData', 'deptNames', 'deptSpends',
            'attendanceTrendLabels', 'presentCounts', 'lateCounts', 'absentCounts',
            'leaveLabels', 'leaveDays', 'totalAnnualSpend', 'averageAttendanceRate'
        ));
    }
}
