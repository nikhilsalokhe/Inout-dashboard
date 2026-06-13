@extends('layouts.admin')

@section('title', 'Create Overtime Policy')

@section('content')
<div class="max-w-4xl mx-auto space-y-6 animate-fade-in">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-extrabold text-slate-800">Create Overtime Policy</h2>
            <p class="text-slate-500 text-sm mt-1">Define rules, thresholds, and multiplier rates.</p>
        </div>
        <a href="{{ route('admin.overtime.policies.index') }}" class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-slate-600 font-bold text-sm hover:bg-slate-50 shadow-sm transition-all">
            <i class="bi bi-arrow-left mr-1"></i> Back
        </a>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.overtime.policies.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Basic Details -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm">
            <h3 class="text-lg font-bold text-slate-800 mb-4 border-b border-slate-100 pb-2">Basic Details</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Policy Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required placeholder="e.g. Standard Developer Policy" class="w-full px-4 py-2 border border-slate-200 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none text-slate-700">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Description</label>
                    <textarea name="description" rows="2" class="w-full px-4 py-2 border border-slate-200 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none text-slate-700"></textarea>
                </div>
                <div class="flex items-center">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" checked class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                        <span class="ml-3 text-sm font-bold text-slate-700">Policy is Active</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Rate Configuration -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm">
            <h3 class="text-lg font-bold text-slate-800 mb-4 border-b border-slate-100 pb-2">Rate Configuration</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Base Rate Type <span class="text-rose-500">*</span></label>
                    <select name="rate_type" id="rateTypeSelect" class="w-full px-4 py-2 border border-slate-200 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none text-slate-700" onchange="toggleFixedRate()">
                        <option value="salary_based">Salary Based (Hourly calculated from salary)</option>
                        <option value="fixed">Fixed Flat Rate</option>
                    </select>
                </div>
                <div id="fixedRateContainer" class="hidden">
                    <label class="block text-sm font-bold text-slate-700 mb-1">Fixed Hourly Rate (₹)</label>
                    <input type="number" step="0.01" name="fixed_rate" placeholder="e.g. 150.00" class="w-full px-4 py-2 border border-slate-200 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none text-slate-700">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Max Payable Hours / Month</label>
                    <input type="number" step="0.5" name="max_payable_hours_per_month" placeholder="Leave empty for unlimited" class="w-full px-4 py-2 border border-slate-200 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none text-slate-700">
                </div>
            </div>
        </div>

        <!-- Daily Rules -->
        <div class="bg-indigo-50/50 rounded-3xl p-6 border border-indigo-100 shadow-sm">
            <div class="flex items-center justify-between mb-4 border-b border-indigo-100 pb-2">
                <h3 class="text-lg font-bold text-indigo-900">Daily Overtime Rules</h3>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="calc_daily" value="1" class="sr-only peer" onchange="document.getElementById('dailyOpts').classList.toggle('opacity-50')">
                    <div class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                </label>
            </div>
            <div id="dailyOpts" class="grid grid-cols-1 md:grid-cols-4 gap-4 opacity-50 transition-opacity">
                <div>
                    <label class="block text-[11px] uppercase tracking-wider font-bold text-indigo-800 mb-1">Min Hours (Shift)</label>
                    <input type="number" step="0.5" name="daily_min_hours" value="9" class="w-full px-3 py-1.5 text-sm border border-indigo-200 rounded-lg">
                </div>
                <div>
                    <label class="block text-[11px] uppercase tracking-wider font-bold text-indigo-800 mb-1">OT Threshold</label>
                    <input type="number" step="0.5" name="daily_threshold" value="0" class="w-full px-3 py-1.5 text-sm border border-indigo-200 rounded-lg" title="Hours above min hours before OT counts">
                </div>
                <div>
                    <label class="block text-[11px] uppercase tracking-wider font-bold text-indigo-800 mb-1">Max OT / Day</label>
                    <input type="number" step="0.5" name="max_daily" class="w-full px-3 py-1.5 text-sm border border-indigo-200 rounded-lg" placeholder="No limit">
                </div>
                <div>
                    <label class="block text-[11px] uppercase tracking-wider font-bold text-indigo-800 mb-1">Rate Multiplier</label>
                    <input type="number" step="0.01" name="daily_rate_multiplier" value="1.0" class="w-full px-3 py-1.5 text-sm border border-indigo-200 rounded-lg">
                </div>
            </div>
        </div>

        <!-- Weekly & Monthly Rules -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-blue-50/50 rounded-3xl p-6 border border-blue-100 shadow-sm">
                <div class="flex items-center justify-between mb-4 border-b border-blue-100 pb-2">
                    <h3 class="text-md font-bold text-blue-900">Weekly Rules</h3>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="calc_weekly" value="1" class="sr-only peer" onchange="document.getElementById('weeklyOpts').classList.toggle('opacity-50')">
                        <div class="w-9 h-5 bg-slate-200 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-500"></div>
                    </label>
                </div>
                <div id="weeklyOpts" class="space-y-3 opacity-50">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-blue-800">Weekly Threshold</span>
                        <input type="number" step="0.5" name="weekly_threshold" value="48" class="w-24 px-2 py-1 text-sm border border-blue-200 rounded-lg">
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-blue-800">Max OT / Week</span>
                        <input type="number" step="0.5" name="max_weekly" placeholder="No limit" class="w-24 px-2 py-1 text-sm border border-blue-200 rounded-lg">
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-blue-800">Multiplier</span>
                        <input type="number" step="0.01" name="weekly_rate_multiplier" value="1.0" class="w-24 px-2 py-1 text-sm border border-blue-200 rounded-lg">
                    </div>
                </div>
            </div>

            <div class="bg-cyan-50/50 rounded-3xl p-6 border border-cyan-100 shadow-sm">
                <div class="flex items-center justify-between mb-4 border-b border-cyan-100 pb-2">
                    <h3 class="text-md font-bold text-cyan-900">Monthly Rules</h3>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="calc_monthly" value="1" class="sr-only peer" onchange="document.getElementById('monthlyOpts').classList.toggle('opacity-50')">
                        <div class="w-9 h-5 bg-slate-200 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-cyan-500"></div>
                    </label>
                </div>
                <div id="monthlyOpts" class="space-y-3 opacity-50">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-cyan-800">Monthly Threshold</span>
                        <input type="number" step="0.5" name="monthly_threshold" value="208" class="w-24 px-2 py-1 text-sm border border-cyan-200 rounded-lg">
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-cyan-800">Max OT / Month</span>
                        <input type="number" step="0.5" name="max_monthly" placeholder="No limit" class="w-24 px-2 py-1 text-sm border border-cyan-200 rounded-lg">
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-cyan-800">Multiplier</span>
                        <input type="number" step="0.01" name="monthly_rate_multiplier" value="1.0" class="w-24 px-2 py-1 text-sm border border-cyan-200 rounded-lg">
                    </div>
                </div>
            </div>
        </div>

        <!-- Special Days -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-rose-50/50 rounded-3xl p-6 border border-rose-100 shadow-sm">
                <div class="flex items-center justify-between mb-4 border-b border-rose-100 pb-2">
                    <h3 class="text-md font-bold text-rose-900">Weekend Rules</h3>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="calc_weekend" value="1" class="sr-only peer" onchange="document.getElementById('weekendOpts').classList.toggle('opacity-50')">
                        <div class="w-9 h-5 bg-slate-200 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-rose-500"></div>
                    </label>
                </div>
                <div id="weekendOpts" class="opacity-50">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-rose-800">Multiplier (e.g. 2 for 2x)</span>
                        <input type="number" step="0.01" name="weekend_rate_multiplier" value="2.0" class="w-24 px-2 py-1 text-sm border border-rose-200 rounded-lg">
                    </div>
                </div>
            </div>

            <div class="bg-amber-50/50 rounded-3xl p-6 border border-amber-100 shadow-sm">
                <div class="flex items-center justify-between mb-4 border-b border-amber-100 pb-2">
                    <h3 class="text-md font-bold text-amber-900">Holiday Rules</h3>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="calc_holiday" value="1" class="sr-only peer" onchange="document.getElementById('holidayOpts').classList.toggle('opacity-50')">
                        <div class="w-9 h-5 bg-slate-200 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-amber-500"></div>
                    </label>
                </div>
                <div id="holidayOpts" class="opacity-50">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-amber-800">Multiplier (e.g. 2 for 2x)</span>
                        <input type="number" step="0.01" name="holiday_rate_multiplier" value="2.0" class="w-24 px-2 py-1 text-sm border border-amber-200 rounded-lg">
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-4 pb-12">
            <button type="submit" class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all">
                Save Policy
            </button>
        </div>
    </form>
</div>

<script>
    function toggleFixedRate() {
        const type = document.getElementById('rateTypeSelect').value;
        const fixedContainer = document.getElementById('fixedRateContainer');
        if (type === 'fixed') {
            fixedContainer.classList.remove('hidden');
        } else {
            fixedContainer.classList.add('hidden');
        }
    }
</script>
@endsection
