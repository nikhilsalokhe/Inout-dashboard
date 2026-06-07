@extends('layouts.admin')

@section('title', 'Configure Shift Policy')

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.shifts.index') }}" class="inline-flex items-center gap-2 text-slate-500 hover:text-indigo-600 font-semibold text-sm transition-colors mb-2">
        <i class="bi bi-arrow-left"></i>
        <span>Back to Shift Policies</span>
    </a>
    <p class="text-slate-500 text-sm font-medium">Modify corporate shift schedule parameters, grace periods, and weekly off policies.</p>
</div>

<div class="max-w-3xl">
    <form action="{{ route('admin.shifts.update', $shift->id) }}" method="POST" class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden animate-fade-in">
        @csrf
        
        <div class="p-8 space-y-6">
            @if ($errors->any())
                <div class="p-4 bg-rose-50 border border-rose-100 text-rose-800 rounded-2xl flex flex-col gap-1.5 shadow-sm">
                    <span class="font-bold text-xs uppercase tracking-wider text-rose-600">Verification Failure</span>
                    <ul class="list-disc pl-5 text-xs font-semibold space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Grid: Name, Type and Status -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Shift Name -->
                <div class="md:col-span-1">
                    <label for="shift_name" class="block text-slate-700 font-extrabold text-xs uppercase tracking-wider mb-2">Shift Policy Name</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400">
                            <i class="bi bi-clock"></i>
                        </span>
                        <input type="text" name="shift_name" id="shift_name" value="{{ old('shift_name', $shift->shift_name) }}" required
                            placeholder="e.g. Regular Day Shift" 
                            class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 focus:border-indigo-500 focus:bg-white rounded-2xl text-sm font-semibold transition-all outline-none text-slate-800">
                    </div>
                </div>

                <!-- Shift Type -->
                <div>
                    <label for="shift_type" class="block text-slate-700 font-extrabold text-xs uppercase tracking-wider mb-2">Shift Schedule Type</label>
                    <select name="shift_type" id="shift_type" required onchange="toggleShiftTimings(this.value)"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 focus:border-indigo-500 focus:bg-white rounded-2xl text-sm font-semibold transition-all outline-none text-slate-800">
                        <option value="general" {{ old('shift_type', $shift->shift_type) == 'general' ? 'selected' : '' }}>General Day Shift</option>
                        <option value="night" {{ old('shift_type', $shift->shift_type) == 'night' ? 'selected' : '' }}>Night Shift (Cross Midnight)</option>
                        <option value="rotational" {{ old('shift_type', $shift->shift_type) == 'rotational' ? 'selected' : '' }}>Rotational Schedule</option>
                        <option value="flexible" {{ old('shift_type', $shift->shift_type) == 'flexible' ? 'selected' : '' }}>Flexible Hours</option>
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-slate-700 font-extrabold text-xs uppercase tracking-wider mb-2">Policy Status</label>
                    <select name="status" id="status" required
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 focus:border-indigo-500 focus:bg-white rounded-2xl text-sm font-semibold transition-all outline-none text-slate-800">
                        <option value="active" {{ old('status', $shift->status) == 'active' ? 'selected' : '' }}>Active Template</option>
                        <option value="inactive" {{ old('status', $shift->status) == 'inactive' ? 'selected' : '' }}>Inactive Template</option>
                    </select>
                </div>
            </div>

            <!-- Shift Timings (Hide if flexible) -->
            <div id="shift_timings_wrapper" class="grid grid-cols-1 md:grid-cols-2 gap-6 transition-all duration-300">
                <!-- Start Time -->
                <div>
                    <label for="start_time" class="block text-slate-700 font-extrabold text-xs uppercase tracking-wider mb-2">Shift Start Time</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400">
                            <i class="bi bi-hourglass-top"></i>
                        </span>
                        <input type="time" name="start_time" id="start_time" 
                            value="{{ old('start_time', $shift->start_time ? substr($shift->start_time, 0, 5) : '') }}"
                            class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 focus:border-indigo-500 focus:bg-white rounded-2xl text-sm font-semibold transition-all outline-none text-slate-800">
                    </div>
                </div>

                <!-- End Time -->
                <div>
                    <label for="end_time" class="block text-slate-700 font-extrabold text-xs uppercase tracking-wider mb-2">Shift End Time</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400">
                            <i class="bi bi-hourglass-bottom"></i>
                        </span>
                        <input type="time" name="end_time" id="end_time" 
                            value="{{ old('end_time', $shift->end_time ? substr($shift->end_time, 0, 5) : '') }}"
                            class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 focus:border-indigo-500 focus:bg-white rounded-2xl text-sm font-semibold transition-all outline-none text-slate-800">
                    </div>
                </div>
            </div>

            <!-- Grace Time & Hour Thresholds -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Grace Time -->
                <div>
                    <label for="grace_time_minutes" class="block text-slate-700 font-extrabold text-xs uppercase tracking-wider mb-2">Grace Time (Mins)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400">
                            <i class="bi bi-stopwatch"></i>
                        </span>
                        <input type="number" name="grace_time_minutes" id="grace_time_minutes" value="{{ old('grace_time_minutes', $shift->grace_time_minutes) }}" required min="0"
                            class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 focus:border-indigo-500 focus:bg-white rounded-2xl text-sm font-semibold transition-all outline-none text-slate-800">
                    </div>
                    <p class="text-[10px] text-slate-400 font-semibold mt-1.5">Late Mark is applied after Grace Deadline.</p>
                </div>

                <!-- Half Day Minimum Hours -->
                <div>
                    <label for="half_day_time" class="block text-slate-700 font-extrabold text-xs uppercase tracking-wider mb-2">Half Day Limit (Hrs)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400">
                            <i class="bi bi-hourglass-split"></i>
                        </span>
                        <input type="number" step="0.5" name="half_day_time" id="half_day_time" value="{{ old('half_day_time', $shift->half_day_time) }}" required min="0" max="24"
                            class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 focus:border-indigo-500 focus:bg-white rounded-2xl text-sm font-semibold transition-all outline-none text-slate-800">
                    </div>
                    <p class="text-[10px] text-slate-400 font-semibold mt-1.5">Min hours required to prevent Absent status.</p>
                </div>

                <!-- Full Day Minimum Hours -->
                <div>
                    <label for="minimum_working_hours" class="block text-slate-700 font-extrabold text-xs uppercase tracking-wider mb-2">Full Day Limit (Hrs)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400">
                            <i class="bi bi-hourglass"></i>
                        </span>
                        <input type="number" step="0.5" name="minimum_working_hours" id="minimum_working_hours" value="{{ old('minimum_working_hours', $shift->minimum_working_hours) }}" required min="0" max="24"
                            class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 focus:border-indigo-500 focus:bg-white rounded-2xl text-sm font-semibold transition-all outline-none text-slate-800">
                    </div>
                    <p class="text-[10px] text-slate-400 font-semibold mt-1.5">Min hours required for full day presence.</p>
                </div>
            </div>

            <!-- Weekly Off Selection -->
            <div>
                <label class="block text-slate-700 font-extrabold text-xs uppercase tracking-wider mb-3">Weekly Off Days</label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-slate-50 border border-slate-200/60 p-4.5 rounded-2xl">
                    @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                        <label class="flex items-center gap-3.5 px-3 py-2 bg-white border border-slate-200 rounded-xl hover:border-indigo-300 transition-colors shadow-sm cursor-pointer group">
                            <input type="checkbox" name="weekly_off_days[]" value="{{ $day }}" 
                                {{ in_array($day, old('weekly_off_days', $weeklyOffs)) ? 'checked' : '' }}
                                class="w-4.5 h-4.5 text-indigo-600 focus:ring-indigo-500 border-slate-300 rounded transition-all cursor-pointer">
                            <span class="text-xs font-bold text-slate-600 group-hover:text-slate-850 select-none">{{ $day }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="px-8 py-5.5 bg-slate-50/70 border-t border-slate-150 flex items-center justify-end gap-3.5">
            <a href="{{ route('admin.shifts.index') }}" class="px-5 py-3 bg-white hover:bg-slate-100 border border-slate-250 text-slate-500 hover:text-slate-700 font-extrabold rounded-2xl text-xs transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-extrabold rounded-2xl text-xs shadow-md shadow-indigo-500/10 hover:shadow-indigo-500/20 transition-all hover:-translate-y-0.5 active:translate-y-0">
                Update Configuration
            </button>
        </div>
    </form>
</div>

<script>
    function toggleShiftTimings(shiftType) {
        const timingsWrapper = document.getElementById('shift_timings_wrapper');
        const startTime = document.getElementById('start_time');
        const endTime = document.getElementById('end_time');

        if (shiftType === 'flexible') {
            // Hide smooth
            timingsWrapper.style.opacity = '0.4';
            startTime.disabled = true;
            endTime.disabled = true;
            startTime.value = '';
            endTime.value = '';
        } else {
            // Show
            timingsWrapper.style.opacity = '1';
            startTime.disabled = false;
            endTime.disabled = false;
        }
    }

    // Trigger on page load
    document.addEventListener('DOMContentLoaded', function() {
        toggleShiftTimings(document.getElementById('shift_type').value);
    });
</script>
@endsection
