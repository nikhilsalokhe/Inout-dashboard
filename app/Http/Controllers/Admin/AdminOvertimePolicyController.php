<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OvertimePolicy;
use Illuminate\Http\Request;

class AdminOvertimePolicyController extends Controller
{
    public function index()
    {
        $policies = OvertimePolicy::latest()->paginate(15);
        return view('admin.overtime.policies.index', compact('policies'));
    }

    public function create()
    {
        return view('admin.overtime.policies.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'calc_daily' => 'boolean',
            'daily_min_hours' => 'nullable|numeric|min:0',
            'daily_threshold' => 'nullable|numeric|min:0',
            'max_daily' => 'nullable|numeric|min:0',
            'daily_rate_multiplier' => 'nullable|numeric|min:0',
            'calc_weekly' => 'boolean',
            'weekly_threshold' => 'nullable|numeric|min:0',
            'max_weekly' => 'nullable|numeric|min:0',
            'weekly_rate_multiplier' => 'nullable|numeric|min:0',
            'calc_monthly' => 'boolean',
            'monthly_threshold' => 'nullable|numeric|min:0',
            'max_monthly' => 'nullable|numeric|min:0',
            'monthly_rate_multiplier' => 'nullable|numeric|min:0',
            'calc_weekend' => 'boolean',
            'weekend_rate_multiplier' => 'nullable|numeric|min:0',
            'calc_holiday' => 'boolean',
            'holiday_rate_multiplier' => 'nullable|numeric|min:0',
            'rate_type' => 'required|in:fixed,salary_based',
            'fixed_rate' => 'nullable|numeric|min:0',
            'max_payable_hours_per_month' => 'nullable|numeric|min:0',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['calc_daily'] = $request->has('calc_daily');
        $validated['calc_weekly'] = $request->has('calc_weekly');
        $validated['calc_monthly'] = $request->has('calc_monthly');
        $validated['calc_weekend'] = $request->has('calc_weekend');
        $validated['calc_holiday'] = $request->has('calc_holiday');

        OvertimePolicy::create($validated);

        return redirect()->route('admin.overtime.policies.index')->with('success', 'Overtime Policy created successfully.');
    }

    public function edit(OvertimePolicy $policy)
    {
        return view('admin.overtime.policies.edit', compact('policy'));
    }

    public function update(Request $request, OvertimePolicy $policy)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'daily_min_hours' => 'nullable|numeric|min:0',
            'daily_threshold' => 'nullable|numeric|min:0',
            'max_daily' => 'nullable|numeric|min:0',
            'daily_rate_multiplier' => 'nullable|numeric|min:0',
            'weekly_threshold' => 'nullable|numeric|min:0',
            'max_weekly' => 'nullable|numeric|min:0',
            'weekly_rate_multiplier' => 'nullable|numeric|min:0',
            'monthly_threshold' => 'nullable|numeric|min:0',
            'max_monthly' => 'nullable|numeric|min:0',
            'monthly_rate_multiplier' => 'nullable|numeric|min:0',
            'weekend_rate_multiplier' => 'nullable|numeric|min:0',
            'holiday_rate_multiplier' => 'nullable|numeric|min:0',
            'rate_type' => 'required|in:fixed,salary_based',
            'fixed_rate' => 'nullable|numeric|min:0',
            'max_payable_hours_per_month' => 'nullable|numeric|min:0',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['calc_daily'] = $request->has('calc_daily');
        $validated['calc_weekly'] = $request->has('calc_weekly');
        $validated['calc_monthly'] = $request->has('calc_monthly');
        $validated['calc_weekend'] = $request->has('calc_weekend');
        $validated['calc_holiday'] = $request->has('calc_holiday');

        $policy->update($validated);

        return redirect()->route('admin.overtime.policies.index')->with('success', 'Overtime Policy updated successfully.');
    }

    public function destroy(OvertimePolicy $policy)
    {
        $policy->delete();
        return redirect()->route('admin.overtime.policies.index')->with('success', 'Overtime Policy deleted successfully.');
    }
}
