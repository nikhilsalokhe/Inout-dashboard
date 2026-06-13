@extends('layouts.admin')

@section('title', 'Overtime Dashboard')

@section('content')
<div class="max-w-7xl mx-auto space-y-6 animate-fade-in">

    <!-- Hero Header -->
    <div class="bg-gradient-to-r from-slate-900 via-orange-950 to-amber-950 text-white rounded-3xl p-8 shadow-xl relative overflow-hidden">
        <div class="absolute right-0 top-0 -mt-8 -mr-8 w-72 h-72 bg-orange-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute left-0 bottom-0 w-48 h-48 bg-amber-500/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-orange-400 bg-orange-950/60 px-3 py-1 rounded-full border border-orange-900/50 inline-block mb-3">
                    Overtime Engine
                </span>
                <h2 class="text-3xl font-extrabold tracking-tight mb-2">Overtime Dashboard</h2>
                <p class="text-slate-300 text-sm max-w-xl">Real-time overview of overtime hours, approvals, and payroll impact across the organization.</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <form method="GET" action="{{ route('admin.overtime.dashboard') }}" class="flex items-center gap-2">
                    <input type="month" name="month" value="{{ $month }}" class="px-3 py-2 rounded-xl bg-white/10 border border-white/20 text-white text-sm font-semibold focus:outline-none focus:border-orange-400 focus:bg-white/20 transition-all" onchange="this.form.submit()">
                </form>
                <a href="{{ route('admin.overtime.requests.index') }}" class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-orange-500 hover:bg-orange-400 text-white font-bold text-sm shadow-lg hover:-translate-y-0.5 transition-all duration-300">
                    <i class="bi bi-list-check"></i> Approval Queue
                    @if($pending > 0)
                        <span class="px-1.5 py-0.5 rounded-full bg-white text-orange-600 text-[10px] font-extrabold">{{ $pending }}</span>
                    @endif
                </a>
            </div>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Pending</span>
                <div class="w-9 h-9 rounded-xl bg-amber-100 flex items-center justify-center text-amber-600"><i class="bi bi-hourglass-split"></i></div>
            </div>
            <div class="text-3xl font-extrabold text-slate-800">{{ $pending }}</div>
            <p class="text-xs text-slate-400 mt-1">Awaiting approval</p>
        </div>

        @if($approvalLevels >= 2)
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Mgr. Approved</span>
                <div class="w-9 h-9 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600"><i class="bi bi-person-check"></i></div>
            </div>
            <div class="text-3xl font-extrabold text-blue-700">{{ $managerApproved }}</div>
            <p class="text-xs text-slate-400 mt-1">Awaiting HR sign-off</p>
        </div>
        @endif

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">HR Approved</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-600"><i class="bi bi-patch-check-fill"></i></div>
            </div>
            <div class="text-3xl font-extrabold text-emerald-700">{{ $hrApproved }}</div>
            <p class="text-xs text-slate-400 mt-1">Ready for payroll</p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Rejected</span>
                <div class="w-9 h-9 rounded-xl bg-rose-100 flex items-center justify-center text-rose-600"><i class="bi bi-x-circle-fill"></i></div>
            </div>
            <div class="text-3xl font-extrabold text-rose-700">{{ $rejected }}</div>
            <p class="text-xs text-slate-400 mt-1">This cycle</p>
        </div>
    </div>

    <!-- Month Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-gradient-to-br from-indigo-600 to-purple-700 text-white rounded-2xl p-6 shadow-lg">
            <div class="flex items-center gap-3 mb-2">
                <i class="bi bi-clock-fill text-xl opacity-70"></i>
                <span class="text-xs font-bold uppercase tracking-wider opacity-70">This Month — Total OT Hours</span>
            </div>
            <div class="text-4xl font-extrabold">{{ number_format($monthHours, 1) }} <span class="text-lg font-medium opacity-70">hrs</span></div>
        </div>
        <div class="bg-gradient-to-br from-emerald-600 to-teal-700 text-white rounded-2xl p-6 shadow-lg">
            <div class="flex items-center gap-3 mb-2">
                <i class="bi bi-currency-rupee text-xl opacity-70"></i>
                <span class="text-xs font-bold uppercase tracking-wider opacity-70">This Month — OT Liability</span>
            </div>
            <div class="text-4xl font-extrabold">₹{{ number_format($monthAmount, 0) }}</div>
        </div>
        <div class="bg-gradient-to-br from-orange-500 to-amber-600 text-white rounded-2xl p-6 shadow-lg">
            <div class="flex items-center gap-3 mb-2">
                <i class="bi bi-wallet2 text-xl opacity-70"></i>
                <span class="text-xs font-bold uppercase tracking-wider opacity-70">This Month — Already Paid</span>
            </div>
            <div class="text-4xl font-extrabold">₹{{ number_format($monthPaid, 0) }}</div>
        </div>
    </div>

    <!-- Bottom Split: Pending Queue + Activity Feed -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Pending Records Table -->
        <div class="lg:col-span-2 bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-md font-bold text-slate-800 flex items-center gap-2">
                    <i class="bi bi-hourglass-split text-amber-500"></i> Pending Approvals
                </h3>
                <a href="{{ route('admin.overtime.requests.index', ['status' => 'pending']) }}" class="text-xs font-bold text-indigo-600 hover:underline">View All →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50/60 border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="p-4 pl-5">Employee</th>
                            <th class="p-4">Date</th>
                            <th class="p-4">Type</th>
                            <th class="p-4 text-right">Hours</th>
                            <th class="p-4 pr-5 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($pendingRecords as $record)
                        <tr class="hover:bg-slate-50/40 transition-colors">
                            <td class="p-4 pl-5">
                                <div class="font-semibold text-slate-800">{{ $record->user->name ?? 'N/A' }}</div>
                                <div class="text-[11px] text-slate-400">{{ $record->user->employee_code ?? '' }}</div>
                            </td>
                            <td class="p-4 text-slate-600">{{ \Carbon\Carbon::parse($record->date)->format('d M, Y') }}</td>
                            <td class="p-4">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wide
                                    {{ $record->overtime_type === 'holiday' ? 'bg-amber-50 text-amber-700' :
                                       ($record->overtime_type === 'weekend' ? 'bg-rose-50 text-rose-700' : 'bg-indigo-50 text-indigo-700') }}">
                                    {{ $record->overtime_type }}
                                </span>
                            </td>
                            <td class="p-4 text-right font-bold text-slate-700">{{ number_format($record->hours, 1) }}h</td>
                            <td class="p-4 pr-5 text-right font-bold text-emerald-700">₹{{ number_format($record->amount, 0) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-slate-400 text-sm">
                                <i class="bi bi-check-circle text-2xl block mb-2 text-emerald-400"></i>
                                All caught up! No pending approvals.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Activity Feed -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden flex flex-col">
            <div class="p-5 border-b border-slate-100">
                <h3 class="text-md font-bold text-slate-800 flex items-center gap-2">
                    <i class="bi bi-activity text-indigo-500"></i> Recent Activity
                </h3>
            </div>
            <div class="flex-1 overflow-y-auto divide-y divide-slate-100 max-h-96">
                @forelse($recentActivity as $act)
                <div class="p-4 flex items-start gap-3">
                    <div class="mt-0.5 w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0
                        {{ $act->status === 'hr_approved' ? 'bg-emerald-100 text-emerald-600' :
                           ($act->status === 'rejected' ? 'bg-rose-100 text-rose-600' :
                           ($act->status === 'paid' ? 'bg-indigo-100 text-indigo-600' : 'bg-blue-100 text-blue-600')) }}">
                        <i class="bi text-xs {{ $act->status === 'hr_approved' ? 'bi-check-lg' :
                           ($act->status === 'rejected' ? 'bi-x-lg' :
                           ($act->status === 'paid' ? 'bi-wallet-fill' : 'bi-person-check')) }}"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-slate-700 truncate">{{ $act->user->name ?? 'Unknown' }}</p>
                        <p class="text-[11px] text-slate-500">
                            {{ ucfirst(str_replace('_', ' ', $act->status)) }} •
                            {{ number_format($act->hours, 1) }}h •
                            {{ \Carbon\Carbon::parse($act->date)->format('d M') }}
                        </p>
                    </div>
                    <span class="text-[10px] text-slate-400 flex-shrink-0">{{ $act->updated_at->diffForHumans(null, true) }}</span>
                </div>
                @empty
                <div class="p-8 text-center text-slate-400 text-sm">No recent activity.</div>
                @endforelse
            </div>
        </div>

    </div>

    <!-- Quick Link Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pb-4">
        <a href="{{ route('admin.overtime.policies.index') }}" class="group bg-white rounded-2xl p-5 border border-slate-200 shadow-sm hover:shadow-md hover:border-indigo-200 transition-all flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-indigo-100 group-hover:bg-indigo-600 flex items-center justify-center text-indigo-600 group-hover:text-white transition-all duration-300">
                <i class="bi bi-file-earmark-ruled-fill text-xl"></i>
            </div>
            <div>
                <div class="font-bold text-slate-800 text-sm">OT Policies</div>
                <div class="text-xs text-slate-500">Manage rules & rates</div>
            </div>
        </a>
        <a href="{{ route('admin.overtime.assignments.index') }}" class="group bg-white rounded-2xl p-5 border border-slate-200 shadow-sm hover:shadow-md hover:border-purple-200 transition-all flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-purple-100 group-hover:bg-purple-600 flex items-center justify-center text-purple-600 group-hover:text-white transition-all duration-300">
                <i class="bi bi-link-45deg text-xl"></i>
            </div>
            <div>
                <div class="font-bold text-slate-800 text-sm">Policy Assignments</div>
                <div class="text-xs text-slate-500">Bind policies to teams</div>
            </div>
        </a>
        <a href="{{ route('admin.settings.index') }}" class="group bg-white rounded-2xl p-5 border border-slate-200 shadow-sm hover:shadow-md hover:border-orange-200 transition-all flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-orange-100 group-hover:bg-orange-600 flex items-center justify-center text-orange-600 group-hover:text-white transition-all duration-300">
                <i class="bi bi-sliders text-xl"></i>
            </div>
            <div>
                <div class="font-bold text-slate-800 text-sm">OT Settings</div>
                <div class="text-xs text-slate-500">Approval levels, weekly off</div>
            </div>
        </a>
    </div>

</div>
@endsection
