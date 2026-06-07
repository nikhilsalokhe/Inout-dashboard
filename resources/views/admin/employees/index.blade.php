@extends('layouts.admin')

@section('title', 'Employee Directory')

@section('content')
<div class="mb-8 flex flex-col sm:flex-row justify-between sm:items-center gap-4">
    <div>
        <p class="text-slate-500 text-sm font-medium">Manage and audit all corporate staff credentials, job hierarchies, and lifecycle events.</p>
    </div>
    <a href="{{ route('admin.employees.create') }}" class="px-5 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-extrabold rounded-2xl shadow-md shadow-indigo-500/20 hover:shadow-indigo-500/30 transition-all duration-300 flex items-center justify-center gap-2 self-start sm:self-auto hover:-translate-y-0.5 active:translate-y-0">
        <i class="bi bi-plus-lg text-sm"></i>
        <span class="text-sm">Add Employee</span>
    </a>
</div>

<!-- Filter Bar -->
<div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6 mb-6">
    <form action="{{ route('admin.employees.index') }}" method="GET" class="flex flex-wrap items-end gap-4">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-[10px] font-extrabold uppercase tracking-widest text-slate-400 mb-2">Employee Type</label>
            <select name="employee_type" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-700 focus:outline-none focus:border-indigo-500 transition duration-200">
                <option value="">All Staff Types</option>
                <option value="permanent" {{ request('employee_type') === 'permanent' ? 'selected' : '' }}>Permanent</option>
                <option value="contract" {{ request('employee_type') === 'contract' ? 'selected' : '' }}>Contract-Based</option>
                <option value="temporary" {{ request('employee_type') === 'temporary' ? 'selected' : '' }}>Temporary / Hourly</option>
                <option value="trainee" {{ request('employee_type') === 'trainee' ? 'selected' : '' }}>Intern / Trainee</option>
            </select>
        </div>

        <div class="flex-1 min-w-[200px]">
            <label class="block text-[10px] font-extrabold uppercase tracking-widest text-slate-400 mb-2">Employment Status</label>
            <select name="employment_status" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-700 focus:outline-none focus:border-indigo-500 transition duration-200">
                <option value="">All Lifecycle Statuses</option>
                <option value="active" {{ request('employment_status') === 'active' ? 'selected' : '' }}>Active Staff</option>
                <option value="notice_period" {{ request('employment_status') === 'notice_period' ? 'selected' : '' }}>On Notice Period</option>
                <option value="terminated" {{ request('employment_status') === 'terminated' ? 'selected' : '' }}>Terminated</option>
                <option value="resigned" {{ request('employment_status') === 'resigned' ? 'selected' : '' }}>Resigned</option>
                <option value="inactive" {{ request('employment_status') === 'inactive' ? 'selected' : '' }}>Inactive / Suspended</option>
            </select>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="px-5 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 font-extrabold text-xs uppercase tracking-wider rounded-xl transition duration-200 border border-indigo-200/50">
                Apply Filters
            </button>
            @if(request()->anyFilled(['employee_type', 'employment_status']))
                <a href="{{ route('admin.employees.index') }}" class="px-5 py-2.5 bg-slate-50 hover:bg-slate-100 text-slate-500 font-extrabold text-xs uppercase tracking-wider rounded-xl transition duration-200 border border-slate-200">
                    Reset
                </a>
            @endif
        </div>
    </form>
</div>

<!-- Table Card container -->
<div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50/70 border-b border-slate-100 text-slate-400 font-bold text-[10px] uppercase tracking-widest">
                <tr>
                    <th class="px-8 py-5">Employee Info</th>
                    <th class="px-8 py-5">Position / Department</th>
                    <th class="px-8 py-5">Location</th>
                    <th class="px-8 py-5">Reporting Manager</th>
                    <th class="px-8 py-5">Employment Status</th>
                    <th class="px-8 py-5 text-right">Directory Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($employees as $employee)
                    <tr class="hover:bg-slate-50/30 transition-colors group">
                        <!-- Employee Info -->
                        <td class="px-8 py-4.5">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-gradient-to-tr from-indigo-500 to-purple-600 text-white rounded-full flex items-center justify-center font-bold text-sm shadow-md shadow-indigo-500/10 border-2 border-white">
                                    {{ strtoupper(substr($employee->name, 0, 1)) }}
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-800 text-sm leading-tight mb-0.5">{{ $employee->name }}</h4>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span class="text-[9px] font-extrabold tracking-wide uppercase px-1.5 py-0.5 rounded {{ $employee->employee_type === 'permanent' ? 'bg-indigo-50 text-indigo-700 border border-indigo-100' : ($employee->employee_type === 'contract' ? 'bg-amber-50 text-amber-700 border border-amber-100' : 'bg-purple-50 text-purple-700 border border-purple-100') }}">
                                            {{ ucfirst($employee->employee_type) }}
                                        </span>
                                        <span class="text-xs text-slate-400 font-medium">{{ $employee->employee_code }}</span>
                                    </div>
                                    <p class="text-xs text-slate-400 font-medium">{{ $employee->email }}</p>
                                </div>
                            </div>
                        </td>

                        <!-- Position / Department -->
                        <td class="px-8 py-4.5">
                            <h4 class="font-bold text-slate-700 text-xs leading-tight mb-0.5">
                                {{ $employee->position ? $employee->position->position_name : 'Unassigned Position' }}
                            </h4>
                            <p class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">
                                {{ $employee->department ? $employee->department->department_name : 'No Department' }}
                            </p>
                        </td>

                        <!-- Location -->
                        <td class="px-8 py-4.5">
                            <span class="px-3 py-1.5 bg-slate-50 text-slate-600 rounded-xl text-xs font-bold border border-slate-100 tracking-wide">
                                {{ $employee->location ? $employee->location->location_name : 'Remote / No Office' }}
                            </span>
                        </td>

                        <!-- Reporting Manager -->
                        <td class="px-8 py-4.5">
                            @if($employee->reportingManager)
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-[10px] font-bold text-slate-600">
                                        {{ strtoupper(substr($employee->reportingManager->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <h5 class="font-bold text-slate-700 text-xs leading-none">{{ $employee->reportingManager->name }}</h5>
                                        <p class="text-[9px] text-slate-400 font-medium mt-0.5">
                                            {{ $employee->reportingManager->position ? $employee->reportingManager->position->position_name : 'Manager' }}
                                        </p>
                                    </div>
                                </div>
                            @else
                                <span class="text-xs text-slate-400 font-semibold italic">Head of Hierarchy</span>
                            @endif
                        </td>
                        
                        <!-- Employment Status -->
                        <td class="px-8 py-4.5">
                            @if($employee->employment_status === 'active')
                                <span class="flex items-center gap-1.5 text-emerald-700 text-xs font-bold bg-emerald-50 border border-emerald-100/60 px-3 py-1 rounded-full w-fit">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-[0_0_6px_#10b981]"></span>
                                    Active
                                </span>
                            @elseif($employee->employment_status === 'notice_period')
                                <span class="flex items-center gap-1.5 text-amber-700 text-xs font-bold bg-amber-50 border border-amber-100/60 px-3 py-1 rounded-full w-fit">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 shadow-[0_0_6px_#f59e0b]"></span>
                                    Notice Period
                                </span>
                            @elseif($employee->isTerminated())
                                <span class="flex items-center gap-1.5 text-rose-700 text-xs font-bold bg-rose-50 border border-rose-100/60 px-3 py-1 rounded-full w-fit">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 shadow-[0_0_6px_#f43f5e]"></span>
                                    Terminated
                                </span>
                            @else
                                <span class="flex items-center gap-1.5 text-slate-700 text-xs font-bold bg-slate-50 border border-slate-200 px-3 py-1 rounded-full w-fit">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400 shadow-[0_0_6px_#94a3b8]"></span>
                                    Inactive
                                </span>
                            @endif
                        </td>
                        
                        <!-- Actions -->
                        <td class="px-8 py-4.5 text-right">
                            <div class="flex items-center justify-end gap-1.5 opacity-0 group-hover:opacity-100 transition-all duration-300">
                                <a href="{{ route('admin.employees.edit', $employee->id) }}" class="w-8 h-8 rounded-xl bg-slate-50 hover:bg-indigo-50 text-slate-400 hover:text-indigo-600 flex items-center justify-center transition-colors border border-transparent hover:border-indigo-100 shadow-sm" title="Edit Profile">
                                    <i class="bi bi-pencil-square text-sm"></i>
                                </a>

                                @if(!$employee->isTerminated())
                                    <a href="{{ route('admin.terminations.create', $employee->id) }}" class="w-8 h-8 rounded-xl bg-slate-50 hover:bg-rose-50 text-slate-400 hover:text-rose-600 flex items-center justify-center transition-colors border border-transparent hover:border-rose-100 shadow-sm" title="Initiate Exit Workflow">
                                        <i class="bi bi-person-x-fill text-sm"></i>
                                    </a>
                                @else
                                    @if($employee->termination)
                                        <a href="{{ route('admin.terminations.show', $employee->termination->id) }}" class="w-8 h-8 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-500 flex items-center justify-center transition-colors border border-transparent hover:border-slate-200 shadow-sm" title="View Exit Summary">
                                            <i class="bi bi-file-earmark-person-fill text-sm"></i>
                                        </a>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-8 py-16 text-center text-slate-400">
                            <div class="flex flex-col items-center gap-4 max-w-sm mx-auto">
                                <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-300 text-2xl border border-slate-100">
                                    <i class="bi bi-inbox-fill"></i>
                                </div>
                                <div>
                                    <h5 class="font-bold text-slate-800 text-base mb-1">No staff records discovered</h5>
                                    <p class="text-xs text-slate-400 leading-relaxed font-semibold">Your corporate employee database is currently empty or no matches fit these filters.</p>
                                </div>
                                <a href="{{ route('admin.employees.create') }}" class="px-4 py-2 bg-indigo-50 border border-indigo-100 text-indigo-600 text-xs font-bold rounded-xl hover:bg-indigo-100 transition-colors">
                                    Register First Staff
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($employees->hasPages())
        <div class="p-6 border-t border-slate-100 bg-slate-50/50">
            {{ $employees->links() }}
        </div>
    @endif
</div>
@endsection
