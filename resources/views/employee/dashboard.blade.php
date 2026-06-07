@extends('layouts.employee')

@section('title', 'Employee Self-Service Dashboard')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <!-- Left 2 Columns: Stats and Core Panels -->
    <div class="lg:col-span-2 space-y-8">
        
        <!-- Welcome Card -->
        <div class="bg-gradient-to-r from-indigo-500 via-indigo-600 to-purple-600 rounded-3xl p-8 text-white shadow-xl shadow-indigo-500/10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 relative overflow-hidden">
            <div class="absolute -right-10 -bottom-10 w-40 h-40 rounded-full bg-white/5 blur-xl"></div>
            <div class="absolute right-20 top-0 w-24 h-24 rounded-full bg-white/5 blur-lg"></div>
            <div>
                <h2 class="text-2xl font-extrabold mb-1">Welcome back, {{ $user->name }}!</h2>
                <p class="text-indigo-100 text-xs font-semibold uppercase tracking-wider">Dept: {{ $user->department->department_name ?? 'General Staff' }} • Code: {{ $user->employee_code ?? 'N/A' }}</p>
                <div class="mt-4 flex flex-wrap gap-2 text-xs font-bold bg-white/10 border border-white/10 px-3 py-2 rounded-xl backdrop-blur-sm self-start">
                    <span>Reporting Manager: {{ $user->reportingManager->name ?? 'None' }}</span>
                </div>
            </div>
            <div class="text-right">
                <span class="text-[10px] text-indigo-200 font-extrabold uppercase block mb-1">Current Date</span>
                <span class="text-lg font-bold">{{ date('F d, Y') }}</span>
            </div>
        </div>

        <!-- Current Month Attendance Stats Card -->
        <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-2">
                    <div class="w-1 h-5 bg-indigo-500 rounded-full"></div>
                    <h3 class="font-bold text-slate-800 text-base">Attendance Summary ({{ date('F') }})</h3>
                </div>
                <span class="text-[10px] font-bold text-slate-400">Updates live</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-slate-50 border border-slate-200/40 p-4.5 rounded-2xl text-center">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Present Days</span>
                    <span class="text-slate-800 font-extrabold text-xl">{{ $presentDays }}</span>
                </div>
                <div class="bg-yellow-50/50 border border-yellow-100 p-4.5 rounded-2xl text-center">
                    <span class="text-[10px] font-bold text-yellow-600 uppercase tracking-wider block mb-1">Late Marks</span>
                    <span class="text-yellow-700 font-extrabold text-xl">{{ $lateMarks }}</span>
                </div>
                <div class="bg-indigo-50/50 border border-indigo-100 p-4.5 rounded-2xl text-center">
                    <span class="text-[10px] font-bold text-indigo-500 uppercase tracking-wider block mb-1">Half Days</span>
                    <span class="text-indigo-600 font-extrabold text-xl">{{ $halfDays }}</span>
                </div>
                <div class="bg-rose-50/50 border border-rose-100 p-4.5 rounded-2xl text-center">
                    <span class="text-[10px] font-bold text-rose-500 uppercase tracking-wider block mb-1">Absents</span>
                    <span class="text-rose-600 font-extrabold text-xl">{{ $absents }}</span>
                </div>
            </div>
        </div>

        <!-- Active Salary Structure Card -->
        <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-2">
                    <div class="w-1 h-5 bg-indigo-500 rounded-full"></div>
                    <h3 class="font-bold text-slate-800 text-base">Active Salary Structure</h3>
                </div>
                @if($activeSalary)
                    <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-100 text-[9px] font-extrabold uppercase rounded">Gross: Rs. {{ number_format($activeSalary->gross_salary, 2) }}</span>
                @else
                    <span class="px-2 py-0.5 bg-slate-50 text-slate-500 border border-slate-100 text-[9px] font-extrabold uppercase rounded">Unassigned</span>
                @endif
            </div>

            @if($activeSalary && $activeSalary->salaryStructure)
                @php
                    $struct = $activeSalary->salaryStructure;
                    $gross = $activeSalary->gross_salary;
                    $basic = round($gross * $struct->basic_percentage / 100, 2);
                    $hra = round($gross * $struct->hra_percentage / 100, 2);
                    $da = round($gross * $struct->da_percentage / 100, 2);
                    $travel = $struct->travel_allowance;
                    $special = max(0, $gross - ($basic + $hra + $da + $travel));
                @endphp
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wide block border-b border-slate-100 pb-1">Earnings Component</span>
                        <div class="flex justify-between text-xs font-medium text-slate-600">
                            <span>Basic Salary ({{ $struct->basic_percentage }}%)</span>
                            <span class="font-bold text-slate-800">Rs. {{ number_format($basic, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-xs font-medium text-slate-600">
                            <span>HRA ({{ $struct->hra_percentage }}%)</span>
                            <span class="font-bold text-slate-800">Rs. {{ number_format($hra, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-xs font-medium text-slate-600">
                            <span>DA ({{ $struct->da_percentage }}%)</span>
                            <span class="font-bold text-slate-800">Rs. {{ number_format($da, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-xs font-medium text-slate-600">
                            <span>Travel Allowance</span>
                            <span class="font-bold text-slate-800">Rs. {{ number_format($travel, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-xs font-medium text-slate-600">
                            <span>Special Allowance (Balancing)</span>
                            <span class="font-bold text-slate-800">Rs. {{ number_format($special, 2) }}</span>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wide block border-b border-slate-100 pb-1">Deduction Rules</span>
                        <div class="flex justify-between text-xs font-medium text-slate-600">
                            <span>Provident Fund (PF)</span>
                            <span class="font-bold text-slate-800">{{ $struct->pf_enabled ? '12% of Basic' : 'Disabled' }}</span>
                        </div>
                        <div class="flex justify-between text-xs font-medium text-slate-600">
                            <span>ESIC</span>
                            <span class="font-bold text-slate-800">{{ $struct->esic_enabled ? '0.75% of Gross' : 'Disabled' }}</span>
                        </div>
                        <div class="flex justify-between text-xs font-medium text-slate-600">
                            <span>Professional Tax (PT)</span>
                            <span class="font-bold text-slate-800">Rs. {{ number_format($struct->professional_tax, 2) }} / month</span>
                        </div>
                        <div class="bg-indigo-50 border border-indigo-100 p-3 rounded-2xl text-[10px] text-indigo-700 font-semibold leading-relaxed mt-4">
                            <i class="bi bi-info-circle-fill mr-1"></i> These figures show your base salary structure package. Final monthly disbursements reflect attendance penalties and deductions.
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-6 text-slate-400">
                    <p class="text-xs font-semibold">No salary structure packages assigned to you yet.</p>
                </div>
            @endif
        </div>

    </div>

    <!-- Right 1 Column: Profile, Leaves, Notifications -->
    <div class="space-y-8">
        
        <!-- Leave Balances -->
        <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6">
            <div class="flex items-center gap-2 mb-6">
                <div class="w-1 h-5 bg-indigo-500 rounded-full"></div>
                <h3 class="font-bold text-slate-800 text-base">Leave Balances</h3>
            </div>

            <div class="space-y-3.5">
                @forelse($leaveBalances as $bal)
                    <div class="bg-slate-50 border border-slate-200/30 p-3.5 rounded-2xl">
                        <div class="flex justify-between items-center mb-1">
                            <span class="font-bold text-xs text-slate-700">{{ $bal->leavePolicy->leave_name }} ({{ $bal->leavePolicy->leave_code }})</span>
                            <span class="text-[10px] text-slate-400 font-extrabold">{{ $bal->remaining_leave }} / {{ $bal->total_leave }} Left</span>
                        </div>
                        <!-- Progress Bar -->
                        <div class="w-full bg-slate-200 h-1.5 rounded-full overflow-hidden">
                            @php
                                $percent = $bal->total_leave > 0 ? ($bal->remaining_leave / $bal->total_leave) * 100 : 0;
                            @endphp
                            <div class="bg-indigo-600 h-full transition-all duration-550" style="width: {{ $percent }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 text-center py-4 font-semibold">No active leave policies or balances found.</p>
                @endforelse
            </div>
            
            <a href="{{ route('employee.leaves') }}" class="w-full block text-center mt-4 px-4 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded-2xl text-xs font-extrabold border border-indigo-150 transition-all">Request Leave Workspace</a>
        </div>

        <!-- Custom Notification Inbox -->
        <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6">
            <div class="flex items-center gap-2 mb-6">
                <div class="w-1 h-5 bg-indigo-500 rounded-full"></div>
                <h3 class="font-bold text-slate-800 text-base">Alerts Inbox</h3>
            </div>

            <div class="space-y-4">
                @forelse($unreadNotifications as $notif)
                    <div class="flex gap-3 border-b border-slate-100 pb-3 last:border-0 last:pb-0">
                        <div class="w-7 h-7 rounded-lg bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center text-xs shrink-0 mt-0.5">
                            @if($notif->type === 'salary')
                                <i class="bi bi-cash"></i>
                            @elseif($notif->type === 'payroll')
                                <i class="bi bi-wallet2"></i>
                            @else
                                <i class="bi bi-bell"></i>
                            @endif
                        </div>
                        <div>
                            <h5 class="font-bold text-slate-800 text-xs leading-tight mb-0.5">{{ $notif->title }}</h5>
                            <p class="text-[10px] text-slate-500 font-medium leading-relaxed mb-1">{{ $notif->description }}</p>
                            <span class="text-[9px] text-slate-400 font-bold block">{{ $notif->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 text-center py-6 font-semibold">Inbox is empty. No new notifications.</p>
                @endforelse
            </div>
        </div>

    </div>

</div>
@endsection
