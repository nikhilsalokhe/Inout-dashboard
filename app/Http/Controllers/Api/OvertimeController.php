<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OvertimeRecord;
use App\Models\Setting;
use App\Services\OvertimeCalculatorService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OvertimeController extends Controller
{
    /**
     * GET /api/overtime/summary
     * Returns this month's overtime summary for the authenticated employee.
     */
    public function summary(Request $request)
    {
        $user = $request->user();
        $month = $request->get('month', Carbon::now()->format('Y-m'));

        $startOfMonth = Carbon::parse($month . '-01')->startOfMonth();
        $endOfMonth   = Carbon::parse($month . '-01')->endOfMonth();

        $records = OvertimeRecord::where('user_id', $user->id)
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get();

        $totalHours  = $records->sum('hours');
        $totalAmount = $records->sum('amount');

        $byType = $records->groupBy('overtime_type')->map(function ($group) {
            return [
                'hours'  => round($group->sum('hours'), 2),
                'amount' => round($group->sum('amount'), 2),
                'count'  => $group->count(),
            ];
        });

        $byStatus = $records->groupBy('status')->map->count();

        // Get applicable policy info
        $calculator = new OvertimeCalculatorService();
        $policy = $calculator->getApplicablePolicy($user);

        return response()->json([
            'month'        => $month,
            'total_hours'  => round($totalHours, 2),
            'total_amount' => round($totalAmount, 2),
            'by_type'      => $byType,
            'by_status'    => $byStatus,
            'policy'       => $policy ? [
                'id'          => $policy->id,
                'name'        => $policy->name,
                'rate_type'   => $policy->rate_type,
                'calc_daily'  => $policy->calc_daily,
                'calc_weekly' => $policy->calc_weekly,
                'calc_weekend'=> $policy->calc_weekend,
                'calc_holiday'=> $policy->calc_holiday,
            ] : null,
            'allow_manual_request' => Setting::get('allow_employee_overtime_request', '0') == '1',
        ]);
    }

    /**
     * GET /api/overtime/history
     * Returns overtime record history for the authenticated employee.
     */
    public function history(Request $request)
    {
        $user = $request->user();

        $perPage = min((int)$request->get('per_page', 20), 50);

        $records = OvertimeRecord::where('user_id', $user->id)
            ->orderBy('date', 'desc')
            ->paginate($perPage);

        $data = $records->getCollection()->map(function ($record) {
            return [
                'id'               => $record->id,
                'date'             => $record->date->toDateString(),
                'overtime_type'    => $record->overtime_type,
                'hours'            => (float) $record->hours,
                'amount'           => (float) $record->amount,
                'status'           => $record->status,
                'is_manual_request'=> $record->is_manual_request,
                'notes'            => $record->notes,
            ];
        });

        return response()->json([
            'data'         => $data,
            'current_page' => $records->currentPage(),
            'last_page'    => $records->lastPage(),
            'total'        => $records->total(),
        ]);
    }

    /**
     * POST /api/overtime/request
     * Employee manually requests overtime (requires setting to be enabled).
     */
    public function requestOvertime(Request $request)
    {
        $user = $request->user();

        // Check if manual requests are allowed
        $allowed = Setting::get('allow_employee_overtime_request', '0') == '1';
        if (!$allowed) {
            return response()->json([
                'message' => 'Manual overtime requests are currently disabled by the administrator.'
            ], 403);
        }

        $validated = $request->validate([
            'date'  => 'required|date|before_or_equal:today',
            'hours' => 'required|numeric|min:0.5|max:12',
            'notes' => 'nullable|string|max:500',
        ]);

        $date = Carbon::parse($validated['date'])->toDateString();

        // Prevent duplicate requests for the same date
        $existing = OvertimeRecord::where('user_id', $user->id)
            ->where('date', $date)
            ->where('is_manual_request', true)
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'You already have a manual overtime request for this date.',
                'record'  => [
                    'id'     => $existing->id,
                    'status' => $existing->status,
                ]
            ], 422);
        }

        // Calculate amount using applicable policy
        $calculator = new OvertimeCalculatorService();
        $policy = $calculator->getApplicablePolicy($user);
        $amount = 0;

        if ($policy) {
            $amount = $calculator->calculateHourlyRate($user, $policy, $policy->daily_rate_multiplier ?? 1.0)
                * $validated['hours'];
        }

        $record = OvertimeRecord::create([
            'user_id'           => $user->id,
            'date'              => $date,
            'overtime_type'     => 'daily',
            'hours'             => $validated['hours'],
            'amount'            => round($amount, 2),
            'status'            => 'pending',
            'is_manual_request' => true,
            'notes'             => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Overtime request submitted successfully. Awaiting approval.',
            'record'  => [
                'id'     => $record->id,
                'date'   => $record->date->toDateString(),
                'hours'  => (float) $record->hours,
                'amount' => (float) $record->amount,
                'status' => $record->status,
            ]
        ], 201);
    }
}
