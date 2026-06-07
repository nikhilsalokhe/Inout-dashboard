@extends('layouts.admin')

@section('title', 'Leave Balances Audit')

@section('content')
<div class="max-w-6xl mx-auto space-y-6 animate-fade-in">
    <!-- Header panel -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900 mb-1">Company Leave Balances</h2>
            <p class="text-xs text-slate-500">View and audit accumulated, used, and remaining leave balances per employee.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.leaves.policies') }}" class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-xs rounded-xl transition-all duration-300">
                Configure Policies
            </a>
            <a href="{{ route('admin.leaves.applications') }}" class="px-4 py-2 bg-slate-900 text-white font-bold text-xs rounded-xl hover:bg-slate-800 transition-all duration-300">
                Pending Requests
            </a>
        </div>
    </div>

    <!-- Balances Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="py-4 px-6">Employee</th>
                        <th class="py-4 px-6">Department</th>
                        <th class="py-4 px-6">Leave Category</th>
                        <th class="py-4 px-6">Year</th>
                        <th class="py-4 px-6 text-center">Total Credited</th>
                        <th class="py-4 px-6 text-center">Used Days</th>
                        <th class="py-4 px-6 text-right">Remaining Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse ($balances as $bal)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-4 px-6 font-semibold text-slate-800">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-600 font-bold text-xs">
                                        {{ strtoupper(substr($bal->employee->name ?? '?', 0, 1)) }}
                                    </div>
                                    <div>
                                        <span class="block font-bold text-slate-800 leading-tight">{{ $bal->employee->name ?? 'Deleted Employee' }}</span>
                                        <span class="block text-[10px] text-slate-400 font-semibold">{{ $bal->employee->employee_code ?? '-' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6 text-slate-500 font-semibold text-xs">
                                {{ $bal->employee->department->department_name ?? 'No Department' }}
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-800">{{ $bal->leavePolicy->leave_name ?? 'Unknown Policy' }}</span>
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-mono font-extrabold bg-slate-100 text-slate-500 border">{{ $bal->leavePolicy->leave_code ?? '-' }}</span>
                                </div>
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-500 font-semibold">
                                {{ $bal->year }}
                            </td>
                            <td class="py-4 px-6 text-center font-bold text-slate-700 text-xs">
                                {{ floatval($bal->total_leave) }} days
                            </td>
                            <td class="py-4 px-6 text-center font-semibold text-rose-600 text-xs">
                                {{ floatval($bal->used_leave) }} days
                            </td>
                            <td class="py-4 px-6 text-right font-extrabold text-emerald-600 text-xs">
                                {{ floatval($bal->remaining_leave) }} days
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 px-6 text-center text-slate-400 font-semibold italic">
                                <i class="bi bi-people text-3xl block mb-2 text-slate-300"></i>
                                No leave balance records found in the system database.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if ($balances->hasPages())
            <div class="p-6 border-t border-slate-100">
                {{ $balances->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
