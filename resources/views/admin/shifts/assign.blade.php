@extends('layouts.admin')

@section('title', 'Assign Shifts')

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.shifts.index') }}" class="inline-flex items-center gap-2 text-slate-500 hover:text-indigo-600 font-semibold text-sm transition-colors mb-2">
        <i class="bi bi-arrow-left"></i>
        <span>Back to Shifts & Assignments</span>
    </a>
    <p class="text-slate-500 text-sm font-medium">Map employees or complete departments to specific shift policies with custom effective timelines.</p>
</div>

<div class="max-w-3xl">
    <form action="{{ route('admin.shifts.assign.store') }}" method="POST" class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden animate-fade-in">
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

            @if (session('error'))
                <div class="p-4 bg-rose-50 border border-rose-100 text-rose-800 rounded-2xl flex items-center gap-3 shadow-sm animate-fade-in">
                    <div class="w-8 h-8 rounded-lg bg-rose-500 flex items-center justify-center text-white text-sm">
                        <i class="bi bi-exclamation-circle-fill"></i>
                    </div>
                    <span class="font-semibold text-sm">{{ session('error') }}</span>
                </div>
            @endif

            <!-- Shift Selection -->
            <div>
                <label for="shift_id" class="block text-slate-700 font-extrabold text-xs uppercase tracking-wider mb-2">Select Shift Policy</label>
                <select name="shift_id" id="shift_id" required
                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 focus:border-indigo-500 focus:bg-white rounded-2xl text-sm font-semibold transition-all outline-none text-slate-800">
                    <option value="" disabled selected>-- Select Policy Template --</option>
                    @foreach($shifts as $shift)
                        <option value="{{ $shift->id }}" {{ old('shift_id') == $shift->id ? 'selected' : '' }}>
                            {{ $shift->shift_name }} ({{ ucfirst($shift->shift_type) }} Shift: 
                            @if($shift->shift_type === 'flexible')
                                Flexible Hours
                            @else
                                {{ \Carbon\Carbon::createFromFormat('H:i:s', $shift->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::createFromFormat('H:i:s', $shift->end_time)->format('h:i A') }}
                            @endif
                            )
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Assignment Type selection -->
            <div>
                <label for="assignment_type" class="block text-slate-700 font-extrabold text-xs uppercase tracking-wider mb-2">Assignment Range</label>
                <select name="assignment_type" id="assignment_type" required onchange="handleAssignmentTypeChange(this.value)"
                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 focus:border-indigo-500 focus:bg-white rounded-2xl text-sm font-semibold transition-all outline-none text-slate-800">
                    <option value="individual" {{ old('assignment_type', 'individual') == 'individual' ? 'selected' : '' }}>Individual Employee</option>
                    <option value="department" {{ old('assignment_type') == 'department' ? 'selected' : '' }}>Entire Corporate Department</option>
                    <option value="multiple" {{ old('assignment_type') == 'multiple' ? 'selected' : '' }}>Multiple Select (Checklist)</option>
                </select>
            </div>

            <!-- Individual Employee Selector -->
            <div id="wrapper_individual" class="assignment-target-wrapper transition-all duration-300">
                <label for="employee_id" class="block text-slate-700 font-extrabold text-xs uppercase tracking-wider mb-2">Select Employee</label>
                <select name="employee_id" id="employee_id"
                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 focus:border-indigo-500 focus:bg-white rounded-2xl text-sm font-semibold transition-all outline-none text-slate-800">
                    <option value="" disabled selected>-- Select Employee --</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                            {{ $emp->name }} (Code: {{ $emp->employee_code ?? '-' }} | Position: {{ $emp->position ? $emp->position->position_name : 'No Position' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Entire Department Selector -->
            <div id="wrapper_department" class="assignment-target-wrapper hidden transition-all duration-300">
                <label for="department_id" class="block text-slate-700 font-extrabold text-xs uppercase tracking-wider mb-2">Select Corporate Department</label>
                <select name="department_id" id="department_id"
                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 focus:border-indigo-500 focus:bg-white rounded-2xl text-sm font-semibold transition-all outline-none text-slate-800">
                    <option value="" disabled selected>-- Select Department --</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                            {{ $dept->department_name }}
                        </option>
                    @endforeach
                </select>
                <p class="text-[10px] text-indigo-500 font-semibold mt-2"><i class="bi bi-info-circle mr-1"></i>Note: This maps the shift to all active staff currently working in the chosen department.</p>
            </div>

            <!-- Multiple Selection Checklist -->
            <div id="wrapper_multiple" class="assignment-target-wrapper hidden transition-all duration-300">
                <label class="block text-slate-700 font-extrabold text-xs uppercase tracking-wider mb-2">Check All Applicable Employees</label>
                <div class="bg-slate-50 border border-slate-200 p-4.5 rounded-2xl max-h-72 overflow-y-auto space-y-2">
                    @foreach($employees as $emp)
                        <label class="flex items-center gap-3.5 px-3 py-2.5 bg-white border border-slate-150 rounded-xl hover:border-indigo-300 transition-colors shadow-sm cursor-pointer group">
                            <input type="checkbox" name="employee_ids[]" value="{{ $emp->id }}" 
                                {{ is_array(old('employee_ids')) && in_array($emp->id, old('employee_ids')) ? 'checked' : '' }}
                                class="w-4.5 h-4.5 text-indigo-600 focus:ring-indigo-500 border-slate-350 rounded cursor-pointer transition-all">
                            <div class="flex flex-col select-none">
                                <span class="text-xs font-bold text-slate-755 group-hover:text-slate-900 leading-tight">{{ $emp->name }}</span>
                                <span class="text-[9px] text-slate-400 font-semibold mt-0.5">Code: {{ $emp->employee_code ?? '-' }} | {{ $emp->position ? $emp->position->position_name : 'No Position' }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Timelines picker -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Effective From -->
                <div>
                    <label for="effective_from" class="block text-slate-700 font-extrabold text-xs uppercase tracking-wider mb-2">Effective From</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400">
                            <i class="bi bi-calendar-date"></i>
                        </span>
                        <input type="date" name="effective_from" id="effective_from" 
                            value="{{ old('effective_from', \Carbon\Carbon::today()->toDateString()) }}" required
                            class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 focus:border-indigo-500 focus:bg-white rounded-2xl text-sm font-semibold transition-all outline-none text-slate-800">
                    </div>
                </div>

                <!-- Effective To -->
                <div>
                    <label for="effective_to" class="block text-slate-700 font-extrabold text-xs uppercase tracking-wider mb-2">Effective To (Optional)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400">
                            <i class="bi bi-calendar-check"></i>
                        </span>
                        <input type="date" name="effective_to" id="effective_to" value="{{ old('effective_to') }}"
                            class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 focus:border-indigo-500 focus:bg-white rounded-2xl text-sm font-semibold transition-all outline-none text-slate-800">
                    </div>
                    <p class="text-[10px] text-slate-400 font-semibold mt-1.5">Leave blank for permanent/ongoing assignment.</p>
                </div>
            </div>
        </div>

        <div class="px-8 py-5.5 bg-slate-50/70 border-t border-slate-150 flex items-center justify-end gap-3.5">
            <a href="{{ route('admin.shifts.index') }}" class="px-5 py-3 bg-white hover:bg-slate-100 border border-slate-250 text-slate-500 hover:text-slate-700 font-extrabold rounded-2xl text-xs transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-extrabold rounded-2xl text-xs shadow-md shadow-indigo-500/10 hover:shadow-indigo-500/20 transition-all hover:-translate-y-0.5 active:translate-y-0">
                Deploy Shift Assignment
            </button>
        </div>
    </form>
</div>

<script>
    function handleAssignmentTypeChange(type) {
        // Hide all targeting selectors first
        document.querySelectorAll('.assignment-target-wrapper').forEach(el => {
            el.classList.add('hidden');
        });

        // Show appropriate container
        const activeWrapper = document.getElementById('wrapper_' + type);
        if (activeWrapper) {
            activeWrapper.classList.remove('hidden');
        }

        // Handle required status configurations
        const employeeSelect = document.getElementById('employee_id');
        const departmentSelect = document.getElementById('department_id');

        if (type === 'individual') {
            employeeSelect.required = true;
            departmentSelect.required = false;
            departmentSelect.value = '';
        } else if (type === 'department') {
            employeeSelect.required = false;
            departmentSelect.required = true;
            employeeSelect.value = '';
        } else {
            employeeSelect.required = false;
            departmentSelect.required = false;
            employeeSelect.value = '';
            departmentSelect.value = '';
        }
    }

    // Run on page load
    document.addEventListener('DOMContentLoaded', function() {
        handleAssignmentTypeChange(document.getElementById('assignment_type').value);
    });
</script>
@endsection
