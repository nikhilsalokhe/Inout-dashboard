@extends('layouts.admin')

@section('title', 'Overtime Approval Queue')

@section('content')
<div class="max-w-7xl mx-auto space-y-6 animate-fade-in">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-extrabold text-slate-800">Overtime Approval Queue</h2>
            <p class="text-slate-500 text-sm mt-0.5">Review, approve, or reject overtime records. Approval levels: <strong class="text-indigo-600">{{ $approvalLevels }}-Level</strong></p>
        </div>
        <a href="{{ route('admin.overtime.dashboard') }}" class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-slate-600 font-bold text-sm hover:bg-slate-50 shadow-sm transition-all self-start">
            <i class="bi bi-arrow-left mr-1"></i> Dashboard
        </a>
    </div>

    <!-- Filters -->
    <form method="GET" action="{{ route('admin.overtime.requests.index') }}" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Status</label>
            <select name="status" class="px-3 py-2 text-sm border border-slate-200 rounded-xl focus:border-indigo-400 focus:outline-none bg-white">
                <option value="">All Statuses</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="manager_approved" {{ request('status') === 'manager_approved' ? 'selected' : '' }}>Mgr. Approved</option>
                <option value="hr_approved" {{ request('status') === 'hr_approved' ? 'selected' : '' }}>HR Approved</option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Type</label>
            <select name="type" class="px-3 py-2 text-sm border border-slate-200 rounded-xl focus:border-indigo-400 focus:outline-none bg-white">
                <option value="">All Types</option>
                <option value="daily" {{ request('type') === 'daily' ? 'selected' : '' }}>Daily</option>
                <option value="weekly" {{ request('type') === 'weekly' ? 'selected' : '' }}>Weekly</option>
                <option value="weekend" {{ request('type') === 'weekend' ? 'selected' : '' }}>Weekend</option>
                <option value="holiday" {{ request('type') === 'holiday' ? 'selected' : '' }}>Holiday</option>
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">From Date</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="px-3 py-2 text-sm border border-slate-200 rounded-xl focus:border-indigo-400 focus:outline-none">
        </div>
        <div>
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">To Date</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="px-3 py-2 text-sm border border-slate-200 rounded-xl focus:border-indigo-400 focus:outline-none">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl transition-all shadow-sm">Apply</button>
            <a href="{{ route('admin.overtime.requests.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-sm rounded-xl transition-all">Clear</a>
        </div>
    </form>

    <!-- Bulk Actions Form -->
    <form id="bulkForm" action="{{ route('admin.overtime.requests.bulk-approve') }}" method="POST">
        @csrf
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <input type="checkbox" id="selectAll" class="w-4 h-4 rounded cursor-pointer accent-indigo-600" onchange="toggleSelectAll(this)">
                    <label for="selectAll" class="text-sm font-bold text-slate-600 cursor-pointer">Select All</label>
                </div>
                <button type="submit" onclick="return confirm('Approve all selected records?')" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-sm transition-all">
                    <i class="bi bi-check2-all mr-1"></i> Bulk Approve Selected
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead class="bg-slate-50/60 border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="p-4 pl-5 w-8"></th>
                            <th class="p-4">Employee</th>
                            <th class="p-4">Date</th>
                            <th class="p-4">Type</th>
                            <th class="p-4 text-center">Hours</th>
                            <th class="p-4 text-right">Amount</th>
                            <th class="p-4 text-center">Status</th>
                            <th class="p-4 pr-5 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($records as $record)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="p-4 pl-5">
                                @if($record->status === 'pending')
                                    <input type="checkbox" name="ids[]" value="{{ $record->id }}" class="bulk-check w-4 h-4 rounded accent-indigo-600 cursor-pointer">
                                @endif
                            </td>
                            <td class="p-4">
                                <div class="font-semibold text-slate-800">{{ $record->user->name ?? 'N/A' }}</div>
                                <div class="text-[11px] text-slate-400">{{ $record->user->employee_code ?? '' }}</div>
                            </td>
                            <td class="p-4 text-slate-600">{{ \Carbon\Carbon::parse($record->date)->format('d M, Y') }}</td>
                            <td class="p-4">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wide
                                    {{ $record->overtime_type === 'holiday' ? 'bg-amber-50 text-amber-700' :
                                       ($record->overtime_type === 'weekend' ? 'bg-rose-50 text-rose-700' :
                                       ($record->overtime_type === 'weekly' ? 'bg-blue-50 text-blue-700' : 'bg-indigo-50 text-indigo-700')) }}">
                                    {{ $record->overtime_type }}
                                </span>
                                @if($record->is_manual_request)
                                    <span class="ml-1 px-1.5 py-0.5 rounded bg-slate-100 text-slate-500 text-[10px] font-bold">Manual</span>
                                @endif
                            </td>
                            <td class="p-4 text-center font-bold text-slate-700">{{ number_format($record->hours, 2) }}h</td>
                            <td class="p-4 text-right font-bold text-emerald-700">₹{{ number_format($record->amount, 0) }}</td>
                            <td class="p-4 text-center">
                                @php
                                    $statusColors = [
                                        'pending'          => 'bg-amber-50 text-amber-700 border-amber-100',
                                        'manager_approved' => 'bg-blue-50 text-blue-700 border-blue-100',
                                        'hr_approved'      => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                        'rejected'         => 'bg-rose-50 text-rose-700 border-rose-100',
                                        'processed'        => 'bg-purple-50 text-purple-700 border-purple-100',
                                        'paid'             => 'bg-slate-100 text-slate-600 border-slate-200',
                                    ];
                                    $color = $statusColors[$record->status] ?? 'bg-slate-50 text-slate-600 border-slate-100';
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full border text-[10px] font-bold uppercase tracking-wide {{ $color }}">
                                    {{ ucfirst(str_replace('_', ' ', $record->status)) }}
                                </span>
                            </td>
                            <td class="p-4 pr-5 text-center">
                                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                    @if($record->status === 'pending')
                                        <form action="{{ route('admin.overtime.requests.manager-approve', $record) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" title="{{ $approvalLevels >= 2 ? 'Manager Approve' : 'Approve' }}" class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white transition-all flex items-center justify-center">
                                                <i class="bi bi-check-lg text-sm"></i>
                                            </button>
                                        </form>
                                    @endif

                                    @if($record->status === 'manager_approved' && $approvalLevels >= 2)
                                        <form action="{{ route('admin.overtime.requests.hr-approve', $record) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" title="HR Final Approve" class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all flex items-center justify-center">
                                                <i class="bi bi-patch-check text-sm"></i>
                                            </button>
                                        </form>
                                    @endif

                                    @if(!in_array($record->status, ['paid', 'processed', 'rejected']))
                                        <button type="button" onclick="openRejectModal({{ $record->id }})" title="Reject" class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-all flex items-center justify-center">
                                            <i class="bi bi-x-lg text-sm"></i>
                                        </button>
                                    @endif

                                    @if($record->notes)
                                        <button type="button" title="{{ $record->notes }}" class="w-8 h-8 rounded-lg bg-slate-50 text-slate-500 flex items-center justify-center cursor-default">
                                            <i class="bi bi-info-circle text-sm"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="p-10 text-center text-slate-400">
                                <i class="bi bi-inbox text-3xl block mb-2"></i>
                                No overtime records found for the selected filters.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100">
                {{ $records->links() }}
            </div>
        </div>
    </form>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" onclick="closeRejectModal()"></div>
    <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md p-6 z-10">
        <h3 class="text-lg font-bold text-slate-800 mb-4">Reject Overtime Record</h3>
        <form id="rejectForm" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-bold text-slate-700 mb-2">Reason for Rejection <span class="text-slate-400 font-normal">(optional)</span></label>
                <textarea name="notes" rows="3" placeholder="Enter a brief reason..." class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm text-slate-700 focus:border-rose-400 focus:ring-1 focus:ring-rose-400 outline-none resize-none"></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeRejectModal()" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-semibold text-sm hover:bg-slate-50 transition-all">Cancel</button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-sm shadow-md transition-all">Reject</button>
            </div>
        </form>
    </div>
</div>

<script>
function openRejectModal(id) {
    document.getElementById('rejectForm').action = '/admin/overtime/requests/' + id + '/reject';
    document.getElementById('rejectModal').classList.remove('hidden');
}
function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
}
function toggleSelectAll(cb) {
    document.querySelectorAll('.bulk-check').forEach(c => c.checked = cb.checked);
}
</script>
@endsection
