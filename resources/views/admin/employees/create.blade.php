@extends('layouts.admin')

@section('title', 'Add New Employee')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Form Card -->
    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
        <!-- Header -->
        <div class="p-8 border-b border-slate-100 bg-slate-50/40">
            <h2 class="text-lg font-extrabold text-slate-800 tracking-tight">Staff Account Credentials & Job Profile</h2>
            <p class="text-slate-400 text-xs font-semibold mt-1">Register a new employee and configure their organizational placement and role relations.</p>
        </div>

        <!-- Form fields -->
        <form action="{{ route('admin.employees.store') }}" method="POST" class="p-8 space-y-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Full Legal Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                        class="w-full px-4 py-3.5 rounded-xl border border-slate-200 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all duration-300 placeholder:text-slate-300 text-sm font-semibold"
                        placeholder="Johnathan Doe">
                    @error('name') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                </div>

                <!-- ID Code -->
                <div>
                    <label for="employee_code" class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Corporate ID Code</label>
                    <input type="text" name="employee_code" id="employee_code" value="{{ old('employee_code') }}" required
                        class="w-full px-4 py-3.5 rounded-xl border border-slate-200 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all duration-300 placeholder:text-slate-300 text-sm font-semibold"
                        placeholder="EMP-012">
                    @error('employee_code') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Email -->
                <div>
                    <label for="email" class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Company Email Address</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required
                        class="w-full px-4 py-3.5 rounded-xl border border-slate-200 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all duration-300 placeholder:text-slate-300 text-sm font-semibold"
                        placeholder="jdoe@inout.com">
                    @error('email') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                </div>

                <!-- Mobile -->
                <div>
                    <label for="mobile" class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Mobile Phone Number</label>
                    <input type="text" name="mobile" id="mobile" value="{{ old('mobile') }}"
                        class="w-full px-4 py-3.5 rounded-xl border border-slate-200 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all duration-300 placeholder:text-slate-300 text-sm font-semibold"
                        placeholder="+1 (555) 019-2834">
                    @error('mobile') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Temporary Password</label>
                <div class="relative group">
                    <input type="password" name="password" id="password" required
                        class="w-full pl-4 pr-12 py-3.5 rounded-xl border border-slate-200 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all duration-300 placeholder:text-slate-300 text-sm font-semibold"
                        placeholder="••••••••">
                    <span onclick="togglePasswordVisibility()" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-indigo-500 transition-colors cursor-pointer">
                        <i class="bi bi-eye-slash-fill" id="eye-icon"></i>
                    </span>
                </div>
                @error('password') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
            </div>

            <!-- Organizational Mapping -->
            <div class="pt-6 border-t border-slate-100">
                <h3 class="text-sm font-bold text-slate-800 tracking-tight mb-4 flex items-center gap-2">
                    <i class="bi bi-diagram-3 text-indigo-500"></i>
                    <span>Organizational Placement & Hierarchy</span>
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Department -->
                    <div>
                        <label for="department_id" class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Department</label>
                        <select name="department_id" id="department_id"
                            class="w-full px-4 py-3.5 rounded-xl border border-slate-200 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all duration-300 text-sm font-semibold bg-white">
                            <option value="">Select Department</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->department_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('department_id') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                    </div>

                    <!-- Designation/Position -->
                    <div>
                        <label for="position_id" class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Designation / Position</label>
                        <select name="position_id" id="position_id"
                            class="w-full px-4 py-3.5 rounded-xl border border-slate-200 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all duration-300 text-sm font-semibold bg-white">
                            <option value="">Select Designation</option>
                            @foreach($positions as $pos)
                                <option value="{{ $pos->id }}" {{ old('position_id') == $pos->id ? 'selected' : '' }}>
                                    {{ $pos->position_name }} ({{ $pos->department->department_name }})
                                </option>
                            @endforeach
                        </select>
                        @error('position_id') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                    <!-- Location -->
                    <div>
                        <label for="location_id" class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Primary Office Location</label>
                        <select name="location_id" id="location_id"
                            class="w-full px-4 py-3.5 rounded-xl border border-slate-200 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all duration-300 text-sm font-semibold bg-white">
                            <option value="">Select Location</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ old('location_id') == $loc->id ? 'selected' : '' }}>
                                    {{ $loc->location_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('location_id') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                    </div>

                    <!-- Reporting Manager -->
                    <div>
                        <label for="reporting_manager_id" class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Reporting Manager</label>
                        <select name="reporting_manager_id" id="reporting_manager_id"
                            class="w-full px-4 py-3.5 rounded-xl border border-slate-200 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all duration-300 text-sm font-semibold bg-white">
                            <option value="">Select Manager</option>
                            @foreach($managers as $mgr)
                                <option value="{{ $mgr->id }}" {{ old('reporting_manager_id') == $mgr->id ? 'selected' : '' }}>
                                    {{ $mgr->name }} ({{ $mgr->position ? $mgr->position->position_name : ($mgr->role == 'admin' ? 'Administrator' : 'Staff') }})
                                </option>
                            @endforeach
                        </select>
                        @error('reporting_manager_id') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                    </div>

                    <!-- Permitted Locations (Multiple Select) -->
                    <div class="md:col-span-2">
                        <label for="permitted_locations" class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Permitted Check-in Locations (Alternative/Pivot)</label>
                        <select name="permitted_locations[]" id="permitted_locations" multiple size="4"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all duration-300 text-sm font-semibold bg-white">
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ is_array(old('permitted_locations')) && in_array($loc->id, old('permitted_locations')) ? 'selected' : '' }}>
                                    {{ $loc->location_name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1">Hold Ctrl (Windows) or Cmd (Mac) to select multiple location checkpoints. Defaults to primary Office Location if empty.</p>
                        @error('permitted_locations') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <!-- Employee Lifecycle & Policy Allocation -->
            <div class="pt-6 border-t border-slate-100">
                <h3 class="text-sm font-bold text-slate-800 tracking-tight mb-4 flex items-center gap-2">
                    <i class="bi bi-clock-history text-indigo-500"></i>
                    <span>Employee Type, Lifecycle & Policy Assignment</span>
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Employee Type -->
                    <div>
                        <label for="employee_type" class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Employee Type</label>
                        <select name="employee_type" id="employee_type" required onchange="handleTypeChange()"
                            class="w-full px-4 py-3.5 rounded-xl border border-slate-200 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all duration-300 text-sm font-semibold bg-white">
                            <option value="permanent" {{ old('employee_type') == 'permanent' ? 'selected' : '' }}>Permanent</option>
                            <option value="contract" {{ old('employee_type') == 'contract' ? 'selected' : '' }}>Contract-Based</option>
                            <option value="temporary" {{ old('employee_type') == 'temporary' ? 'selected' : '' }}>Temporary / Hourly</option>
                            <option value="trainee" {{ old('employee_type') == 'trainee' ? 'selected' : '' }}>Intern / Trainee</option>
                        </select>
                        @error('employee_type') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                    </div>

                    <!-- Joining Date -->
                    <div>
                        <label for="joining_date" class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Joining Date</label>
                        <input type="date" name="joining_date" id="joining_date" value="{{ old('joining_date', date('Y-m-d')) }}" required
                            class="w-full px-4 py-3.5 rounded-xl border border-slate-200 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all duration-300 text-sm font-semibold">
                        @error('joining_date') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- Probation Settings (Permanent only) -->
                <div id="probation_section" class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                    <div>
                        <label for="probation_end_date" class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Probation End Date</label>
                        <input type="date" name="probation_end_date" id="probation_end_date" value="{{ old('probation_end_date') }}"
                            class="w-full px-4 py-3.5 rounded-xl border border-slate-200 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all duration-300 text-sm font-semibold">
                        @error('probation_end_date') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- Contract Details (Contract only) -->
                <div id="contract_section" class="space-y-6 mt-6 hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="contract_start_date" class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Contract Start Date</label>
                            <input type="date" name="contract_start_date" id="contract_start_date" value="{{ old('contract_start_date') }}"
                                class="w-full px-4 py-3.5 rounded-xl border border-slate-200 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all duration-300 text-sm font-semibold">
                            @error('contract_start_date') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="contract_end_date" class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Contract End Date</label>
                            <input type="date" name="contract_end_date" id="contract_end_date" value="{{ old('contract_end_date') }}"
                                class="w-full px-4 py-3.5 rounded-xl border border-slate-200 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all duration-300 text-sm font-semibold">
                            @error('contract_end_date') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="contract_renewal_option" id="contract_renewal_option" value="1" {{ old('contract_renewal_option') ? 'checked' : '' }}
                            class="w-4 h-4 text-indigo-600 border-slate-200 rounded focus:ring-indigo-500/10">
                        <label for="contract_renewal_option" class="text-xs font-bold text-slate-600">Option for renewal exists</label>
                    </div>
                </div>

                <!-- Shift assignment and salary structures -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                    <!-- Shift -->
                    <div>
                        <label for="shift_id" class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Shift Policy Assignment</label>
                        <select name="shift_id" id="shift_id"
                            class="w-full px-4 py-3.5 rounded-xl border border-slate-200 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all duration-300 text-sm font-semibold bg-white">
                            <option value="">Do Not Assign (Unscheduled)</option>
                            @foreach($shifts as $s)
                                <option value="{{ $s->id }}" {{ old('shift_id') == $s->id ? 'selected' : '' }}>
                                    {{ $s->shift_name }} ({{ $s->start_time }} - {{ $s->end_time }})
                                </option>
                            @endforeach
                        </select>
                        @error('shift_id') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                    </div>

                    <!-- Salary Structure -->
                    <div>
                        <label for="salary_structure_id" class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Salary Template Assignment</label>
                        <select name="salary_structure_id" id="salary_structure_id"
                            class="w-full px-4 py-3.5 rounded-xl border border-slate-200 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all duration-300 text-sm font-semibold bg-white">
                            <option value="">Do Not Allocate (Unsalaried)</option>
                            @foreach($salaryStructures as $structure)
                                <option value="{{ $structure->id }}" {{ old('salary_structure_id') == $structure->id ? 'selected' : '' }}>
                                    {{ $structure->structure_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('salary_structure_id') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                    </div>

                    <!-- Gross Salary -->
                    <div>
                        <label for="gross_salary" class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Monthly Gross Salary (Rs.)</label>
                        <input type="number" name="gross_salary" id="gross_salary" value="{{ old('gross_salary') }}" min="0" step="0.01"
                            class="w-full px-4 py-3.5 rounded-xl border border-slate-200 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all duration-300 text-sm font-semibold placeholder:text-slate-300"
                            placeholder="e.g. 45000.00">
                        @error('gross_salary') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="pt-6 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('admin.employees.index') }}" class="px-5 py-3 rounded-xl text-slate-500 hover:text-slate-700 hover:bg-slate-100/50 text-xs font-bold transition-all duration-200">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-indigo-500/20 hover:shadow-indigo-500/30 transition-all duration-300 hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98]">
                    Save Staff Member
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function togglePasswordVisibility() {
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eye-icon');
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.classList.remove('bi-eye-slash-fill');
            eyeIcon.classList.add('bi-eye-fill');
        } else {
            passwordInput.type = 'password';
            eyeIcon.classList.remove('bi-eye-fill');
            eyeIcon.classList.add('bi-eye-slash-fill');
        }
    }

    function handleTypeChange() {
        const type = document.getElementById('employee_type').value;
        const probationSection = document.getElementById('probation_section');
        const contractSection = document.getElementById('contract_section');

        if (type === 'permanent') {
            probationSection.classList.remove('hidden');
            contractSection.classList.add('hidden');
        } else if (type === 'contract') {
            probationSection.classList.add('hidden');
            contractSection.classList.remove('hidden');
        } else {
            probationSection.classList.add('hidden');
            contractSection.classList.add('hidden');
        }
    }
    
    // Call on load
    document.addEventListener("DOMContentLoaded", function() {
        handleTypeChange();
    });
</script>
@endsection
