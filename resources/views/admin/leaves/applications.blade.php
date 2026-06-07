@extends('layouts.admin')

@section('title', 'Leave Applications Approval Queue')

@section('content')
    <div class="max-w-6xl mx-auto space-y-6 animate-fade-in">
        <!-- Header panel -->
        <div
            class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-slate-900 mb-1">Leave Requests Queue</h2>
                <p class="text-xs text-slate-500">Review pending off-duty applications, reasons, and process HR decisions.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.leaves.policies') }}"
                    class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-xs rounded-xl transition-all duration-300">
                    Leave Policies
                </a>
                <a href="{{ route('admin.leaves.balances') }}"
                    class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-xs rounded-xl transition-all duration-300">
                    Balances Workspace
                </a>
            </div>
        </div>

        <!-- Error/Success session alerts -->
        @if(session('error'))
            <div
                class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl flex items-center gap-3 shadow-sm font-semibold text-sm">
                <i class="bi bi-exclamation-triangle-fill text-rose-500 text-lg"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Applications Table -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr
                            class="bg-slate-50 border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            <th class="py-4 px-6">Employee</th>
                            <th class="py-4 px-6">Leave Category</th>
                            <th class="py-4 px-6">Duration (Dates)</th>
                            <th class="py-4 px-6">Days</th>
                            <th class="py-4 px-6">Reason & Attachment</th>
                            <th class="py-4 px-6">Status</th>
                            <th class="py-4 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse ($applications as $app)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-2.5">
                                        <div
                                            class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-600 font-bold text-xs">
                                            {{ strtoupper(substr($app->employee->name ?? '?', 0, 1)) }}
                                        </div>
                                        <div>
                                            <span
                                                class="block font-bold text-slate-800 leading-tight">{{ $app->employee->name ?? 'Deleted Employee' }}</span>
                                            <span
                                                class="block text-[10px] text-slate-400 font-semibold">{{ $app->employee->department->department_name ?? '-' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="font-bold text-slate-800 text-xs">{{ $app->leavePolicy->leave_name ?? 'Unknown Policy' }}</span>
                                        <span
                                            class="px-1.5 py-0.5 rounded text-[9px] font-mono font-extrabold bg-slate-100 text-slate-500 border">{{ $app->leavePolicy->leave_code ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="text-xs text-slate-600 leading-normal font-semibold">
                                        <span>{{ $app->from_date->format('M d, Y') }}</span>
                                        <i class="bi bi-arrow-right text-[10px] text-slate-400 mx-1"></i>
                                        <span>{{ $app->to_date->format('M d, Y') }}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-6 font-extrabold text-slate-700 text-xs">
                                    {{ $app->total_days }} {{ Str::plural('day', $app->total_days) }}
                                </td>
                                <td class="py-4 px-6">
                                    <div class="max-w-xs space-y-1">
                                        <p class="text-xs text-slate-500 font-medium truncate" title="{{ $app->reason }}">
                                            {{ $app->reason }}</p>
                                        @if($app->attachment)
                                            <a href="{{ Storage::url($app->attachment) }}" target="_blank"
                                                class="inline-flex items-center gap-1 text-[10px] font-bold text-indigo-600 hover:underline">
                                                <i class="bi bi-paperclip"></i> View Attachment
                                            </a>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider border
                                            @if($app->status == 'pending') bg-amber-50 text-amber-600 border-amber-100
                                            @elseif($app->status == 'approved') bg-emerald-50 text-emerald-600 border-emerald-100
                                            @else bg-rose-50 text-rose-600 border-rose-100
                                            @endif">
                                        {{ $app->status }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    @if ($app->status === 'pending')
                                        <button onclick="toggleActionPanel({{ $app->id }})"
                                            class="px-3.5 py-2 rounded-xl bg-slate-900 text-white hover:bg-slate-800 font-bold text-xs transition-colors">
                                            Review
                                        </button>
                                    @else
                                        <div class="text-[10px] text-slate-400 font-semibold leading-normal">
                                            <span>By: {{ $app->approver->name ?? 'System' }}</span><br>
                                            <span>At: {{ $app->approved_at ? $app->approved_at->format('M d, h:i A') : '-' }}</span>
                                            @if($app->remarks)
                                                <span class="block text-[10px] text-slate-500 italic mt-1 font-medium">Remarks:
                                                    "{{ $app->remarks }}"</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                            @if ($app->status === 'pending')
                                <tr id="action-panel-{{ $app->id }}" class="hidden bg-slate-50 border-t border-slate-100">
                                    <td colspan="7" class="p-6">
                                        <div
                                            class="max-w-2xl mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-4">
                                            <div class="flex items-center justify-between border-b pb-2">
                                                <h4 class="font-bold text-slate-800 text-sm">Process Leave Decision</h4>
                                                <button onclick="toggleActionPanel({{ $app->id }})"
                                                    class="text-slate-400 hover:text-slate-600 text-xs font-bold"><i
                                                        class="bi bi-x-lg"></i></button>
                                            </div>
                                            <form action="{{ route('admin.leaves.applications.approve', $app->id) }}" method="POST"
                                                id="form-{{ $app->id }}"
                                                data-approve-url="{{ route('admin.leaves.applications.approve', $app->id) }}"
                                                data-reject-url="{{ route('admin.leaves.applications.reject', $app->id) }}"
                                                class="space-y-4">
                                                @csrf
                                                <div>
                                                    <label
                                                        class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Remarks
                                                        / Reason (Optional)</label>
                                                    <textarea name="remarks" rows="2"
                                                        placeholder="Provide details regarding approval or rejection..."
                                                        class="w-full px-4 py-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 font-semibold text-slate-700 bg-white"></textarea>
                                                </div>
                                                <div class="flex items-center justify-end gap-2">
                                                    <button type="button" onclick="submitDecision({{ $app->id }}, 'reject')"
                                                        class="px-5 py-2.5 rounded-xl border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-700 font-extrabold text-xs transition-colors">
                                                        Reject Leave
                                                    </button>
                                                    <button type="button" onclick="submitDecision({{ $app->id }}, 'approve')"
                                                        class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-extrabold text-xs transition-colors shadow-sm">
                                                        Approve Leave
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 px-6 text-center text-slate-400 font-semibold italic">
                                    <i class="bi bi-journal-check text-3xl block mb-2 text-slate-300"></i>
                                    No leave applications registered in this queue.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($applications->hasPages())
                <div class="p-6 border-t border-slate-100">
                    {{ $applications->links() }}
                </div>
            @endif
        </div>
    </div>

    <script>
        function toggleActionPanel(id) {
            const panel = document.getElementById('action-panel-' + id);
            if (panel) {
                panel.classList.toggle('hidden');
            }
        }

        function submitDecision(id, decision) {
            const form = document.getElementById('form-' + id);

            if (decision === 'reject') {
                form.action = form.dataset.rejectUrl;
            } else {
                form.action = form.dataset.approveUrl;
            }

            form.submit();
        }
    </script>
@endsection