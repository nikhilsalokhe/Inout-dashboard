@extends('layouts.admin')

@section('title', 'Holiday Management')

@section('content')
<div class="space-y-8">
    <!-- Stat Counter Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-5">
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl font-bold shadow-inner">
                <i class="bi bi-calendar3"></i>
            </div>
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Holidays</span>
                <span class="text-2xl font-black text-slate-900 leading-none mt-1 block">{{ $holidays->count() }}</span>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-5">
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl font-bold shadow-inner">
                <i class="bi bi-award-fill"></i>
            </div>
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Gazetted Holidays</span>
                <span class="text-2xl font-black text-slate-900 leading-none mt-1 block">{{ $holidays->where('holiday_type', 'gazetted')->count() }}</span>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-5">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl font-bold shadow-inner">
                <i class="bi bi-bookmark-star-fill"></i>
            </div>
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Optional Holidays</span>
                <span class="text-2xl font-black text-slate-900 leading-none mt-1 block">{{ $holidays->where('holiday_type', 'optional')->count() }}</span>
            </div>
        </div>
    </div>

    <!-- Main Workspace Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left Pane: Holidays List Table (2 cols width) -->
        <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden flex flex-col">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div>
                    <h3 class="font-extrabold text-slate-900 tracking-tight text-base">Annual Holiday Calendar</h3>
                    <p class="text-xs font-medium text-slate-500 mt-1">Listed and active assignments for employees</p>
                </div>
            </div>
            
            <div class="overflow-x-auto flex-1">
                @if($holidays->isEmpty())
                    <div class="p-12 text-center flex flex-col items-center justify-center">
                        <div class="w-16 h-16 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 mb-4 border border-dashed border-slate-200">
                            <i class="bi bi-calendar-x text-2xl"></i>
                        </div>
                        <span class="font-bold text-slate-800 text-sm block">No Holidays Created Yet</span>
                        <span class="text-xs text-slate-400 mt-1 block max-w-xs">Use the assign form to start populating this year's company calendar.</span>
                    </div>
                @else
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100">
                                <th class="p-4 px-6 text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Holiday Name</th>
                                <th class="p-4 px-6 text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Date</th>
                                <th class="p-4 px-6 text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Type</th>
                                <th class="p-4 px-6 text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Assigned To</th>
                                <th class="p-4 px-6 text-[10px] font-extrabold text-slate-400 uppercase tracking-widest text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach($holidays as $holiday)
                                <tr class="hover:bg-slate-50/40 transition-colors duration-150">
                                    <td class="p-4 px-6">
                                        <div class="flex items-center gap-3">
                                            <span class="font-bold text-slate-800 text-sm">{{ $holiday->holiday_name }}</span>
                                        </div>
                                    </td>
                                    <td class="p-4 px-6">
                                        <div class="flex flex-col">
                                            <span class="font-semibold text-slate-700 text-sm">{{ $holiday->holiday_date->format('d M, Y') }}</span>
                                            <span class="text-[10px] text-indigo-500 font-bold tracking-wider mt-0.5">{{ $holiday->holiday_date->format('l') }}</span>
                                        </div>
                                    </td>
                                    <td class="p-4 px-6">
                                        @if($holiday->holiday_type === 'gazetted')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-600 border border-rose-100">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                                Gazetted
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-600 border border-amber-100">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                Optional
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-4 px-6">
                                        @if($holiday->employee)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-purple-50 text-purple-600 border border-purple-100">
                                                <i class="bi bi-person text-xs"></i>
                                                {{ $holiday->employee->name }}
                                            </span>
                                        @elseif($holiday->department)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-600 border border-blue-100">
                                                <i class="bi bi-building text-xs"></i>
                                                Dept: {{ $holiday->department->department_name }}
                                            </span>
                                        @elseif($holiday->location)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-teal-50 text-teal-600 border border-teal-100">
                                                <i class="bi bi-geo-alt text-xs"></i>
                                                Loc: {{ $holiday->location->location_name }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-slate-50 text-slate-600 border border-slate-200">
                                                <i class="bi bi-globe text-xs"></i>
                                                All Employees
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-4 px-6 text-right">
                                        <form action="{{ route('admin.holidays.destroy', $holiday->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this holiday record?');" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="w-8 h-8 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-500 hover:text-rose-600 flex items-center justify-center transition-colors duration-150">
                                                <i class="bi bi-trash-fill text-xs"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        <!-- Right Pane: Creation Form Card (1 col width) -->
        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 flex flex-col h-fit">
            <h3 class="font-extrabold text-slate-900 tracking-tight text-base">Assign Holiday</h3>
            <p class="text-xs font-medium text-slate-400 mt-1">Configure and target a new calendar event</p>
            
            <form action="{{ route('admin.holidays.store') }}" method="POST" class="space-y-5 mt-6">
                @csrf
                
                <div>
                    <label for="holiday_name" class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-2">Holiday Title</label>
                    <input type="text" name="holiday_name" id="holiday_name" required placeholder="e.g. Independence Day" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-semibold">
                </div>

                <div>
                    <label for="holiday_date" class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-2">Date</label>
                    <input type="date" name="holiday_date" id="holiday_date" required class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-semibold">
                </div>

                <div>
                    <label for="holiday_type" class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-2">Holiday Type</label>
                    <select name="holiday_type" id="holiday_type" required class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-semibold">
                        <option value="gazetted">Gazetted (Mandatory)</option>
                        <option value="optional">Optional (Elective)</option>
                    </select>
                </div>

                <div>
                    <label for="assignment_target" class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-2">Assignment Scope</label>
                    <select name="assignment_target" id="assignment_target" required class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-semibold">
                        <option value="all">All Employees</option>
                        <option value="location">Specific Location</option>
                        <option value="department">Specific Department</option>
                        <option value="employee">Specific Employee</option>
                    </select>
                </div>

                <!-- Location Select (Conditional) -->
                <div id="target_location_wrapper" class="hidden">
                    <label for="location_id" class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-2">Target Location</label>
                    <select name="location_id" id="location_id" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-semibold">
                        <option value="">Select Location...</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->location_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Department Select (Conditional) -->
                <div id="target_department_wrapper" class="hidden">
                    <label for="department_id" class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-2">Target Department</label>
                    <select name="department_id" id="department_id" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-semibold">
                        <option value="">Select Department...</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->department_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Employee Select (Conditional) -->
                <div id="target_employee_wrapper" class="hidden">
                    <label for="employee_id" class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-2">Target Employee</label>
                    <select name="employee_id" id="employee_id" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-semibold">
                        <option value="">Select Employee...</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }} ({{ $employee->employee_code }})</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="w-full bg-gradient-to-tr from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-bold py-3.5 px-4 rounded-xl shadow-lg shadow-indigo-500/10 hover:shadow-indigo-500/20 hover:-translate-y-0.5 transition-all duration-150 flex items-center justify-center gap-2 mt-4">
                    <i class="bi bi-plus-lg"></i>
                    <span>Add to Calendar</span>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const assignmentTarget = document.getElementById('assignment_target');
        const locationWrapper = document.getElementById('target_location_wrapper');
        const departmentWrapper = document.getElementById('target_department_wrapper');
        const employeeWrapper = document.getElementById('target_employee_wrapper');

        const locationInput = document.getElementById('location_id');
        const departmentInput = document.getElementById('department_id');
        const employeeInput = document.getElementById('employee_id');

        function toggleWrappers() {
            const value = assignmentTarget.value;
            
            // Hide all
            locationWrapper.classList.add('hidden');
            departmentWrapper.classList.add('hidden');
            employeeWrapper.classList.add('hidden');
            
            // Remove required/clear values if hidden
            locationInput.required = false;
            departmentInput.required = false;
            employeeInput.required = false;

            if (value === 'location') {
                locationWrapper.classList.remove('hidden');
                locationInput.required = true;
            } else if (value === 'department') {
                departmentWrapper.classList.remove('hidden');
                departmentInput.required = true;
            } else if (value === 'employee') {
                employeeWrapper.classList.remove('hidden');
                employeeInput.required = true;
            }
        }

        assignmentTarget.addEventListener('change', toggleWrappers);
        
        // Run on initial load
        toggleWrappers();
    });
</script>
@endsection
