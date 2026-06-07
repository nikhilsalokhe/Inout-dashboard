@extends('layouts.admin')

@section('title', 'Security Audit Trail & Compliance')

@section('content')
<div class="max-w-6xl mx-auto space-y-6 animate-fade-in">
    <!-- Header summary panel -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900 mb-1">Audit Trail Workspace</h2>
            <p class="text-xs text-slate-500">Read-only list recording system changes, policy adjustments, and user device bindings.</p>
        </div>
        <form action="{{ route('admin.audit-logs.index') }}" method="GET" class="flex flex-wrap items-center gap-3">
            <div>
                <select name="module" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 bg-white focus:outline-none focus:border-indigo-500">
                    <option value="">All Modules</option>
                    <option value="settings" {{ request('module') == 'settings' ? 'selected' : '' }}>Settings</option>
                    <option value="leaves" {{ request('module') == 'leaves' ? 'selected' : '' }}>Leaves</option>
                    <option value="shifts" {{ request('module') == 'shifts' ? 'selected' : '' }}>Shifts</option>
                    <option value="face_recognition" {{ request('module') == 'face_recognition' ? 'selected' : '' }}>Face Recognition</option>
                </select>
            </div>
            <div>
                <select name="action" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 bg-white focus:outline-none focus:border-indigo-500">
                    <option value="">All Actions</option>
                    <option value="update_setting" {{ request('action') == 'update_setting' ? 'selected' : '' }}>Update Setting</option>
                    <option value="create" {{ request('action') == 'create' ? 'selected' : '' }}>Create</option>
                    <option value="update" {{ request('action') == 'update' ? 'selected' : '' }}>Update</option>
                    <option value="delete" {{ request('action') == 'delete' ? 'selected' : '' }}>Delete</option>
                    <option value="approve" {{ request('action') == 'approve' ? 'selected' : '' }}>Approve</option>
                    <option value="reject" {{ request('action') == 'reject' ? 'selected' : '' }}>Reject</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-slate-900 text-white font-bold text-xs rounded-xl hover:bg-slate-800 transition-all duration-300">
                Filter
            </button>
            @if(request('module') || request('action'))
                <a href="{{ route('admin.audit-logs.index') }}" class="px-3 py-2 border border-slate-200 hover:bg-slate-50 text-slate-500 font-semibold text-xs rounded-xl transition-all duration-300">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Audit Logs Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="py-4 px-6">Timestamp</th>
                        <th class="py-4 px-6">Actor</th>
                        <th class="py-4 px-6">Module</th>
                        <th class="py-4 px-6">Action</th>
                        <th class="py-4 px-6">Network / Info</th>
                        <th class="py-4 px-6 text-right">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse ($auditLogs as $log)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-4 px-6 text-xs font-semibold text-slate-500">
                                {{ $log->created_at->timezone('Asia/Kolkata')->format('M d, Y h:i A') }}
                            </td>
                            <td class="py-4 px-6">
                                @if($log->user)
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-600 font-bold text-xs">
                                            {{ strtoupper(substr($log->user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <span class="font-semibold text-slate-800 block leading-tight">{{ $log->user->name }}</span>
                                            <span class="text-[9px] font-medium text-slate-400 block">{{ $log->user->email }}</span>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-xs font-medium text-slate-400 italic">System Auto</span>
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider
                                    @if($log->module == 'settings') bg-indigo-50 border border-indigo-100 text-indigo-600
                                    @elseif($log->module == 'leaves') bg-emerald-50 border border-emerald-100 text-emerald-600
                                    @elseif($log->module == 'shifts') bg-amber-50 border border-amber-100 text-amber-600
                                    @else bg-slate-50 border border-slate-200 text-slate-600
                                    @endif">
                                    {{ $log->module }}
                                </span>
                            </td>
                            <td class="py-4 px-6 font-semibold text-slate-700 text-xs">
                                {{ str_replace('_', ' ', $log->action) }}
                            </td>
                            <td class="py-4 px-6">
                                <div class="text-xs text-slate-500 leading-normal">
                                    <span class="font-medium text-slate-700 block"><i class="bi bi-laptop text-slate-400 mr-1"></i>{{ Str::limit($log->device_info, 30) }}</span>
                                    <span class="text-[10px] text-slate-400 font-semibold block"><i class="bi bi-globe2 text-slate-400 mr-1"></i>IP: {{ $log->ip_address }}</span>
                                </div>
                            </td>
                            <td class="py-4 px-6 text-right">
                                @if($log->old_data || $log->new_data)
                                    <button onclick="toggleDetails({{ $log->id }})" class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 hover:text-indigo-800 transition-colors">
                                        <span>Show</span>
                                        <i class="bi bi-chevron-down text-[10px] transition-transform duration-200" id="chevron-{{ $log->id }}"></i>
                                    </button>
                                @else
                                    <span class="text-xs text-slate-400 italic">None</span>
                                @endif
                            </td>
                        </tr>
                        @if($log->old_data || $log->new_data)
                            <tr id="details-{{ $log->id }}" class="hidden bg-slate-50 border-t border-slate-100">
                                <td colspan="6" class="p-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-mono">
                                        <div class="bg-white p-4 rounded-xl border border-slate-200/60 shadow-inner">
                                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 border-b pb-1">Old State Data</div>
                                            <pre class="overflow-x-auto text-rose-600 leading-relaxed">{{ json_encode($log->old_data, JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                        <div class="bg-white p-4 rounded-xl border border-slate-200/60 shadow-inner">
                                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 border-b pb-1">New State Data</div>
                                            <pre class="overflow-x-auto text-emerald-600 leading-relaxed">{{ json_encode($log->new_data, JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 px-6 text-center text-slate-400 font-semibold italic">
                                <i class="bi bi-shield-slash text-3xl block mb-2 text-slate-300"></i>
                                No audit log records found matching search filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if ($auditLogs->hasPages())
            <div class="p-6 border-t border-slate-100">
                {{ $auditLogs->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>

<script>
    function toggleDetails(logId) {
        const detailsRow = document.getElementById('details-' + logId);
        const chevron = document.getElementById('chevron-' + logId);
        if (detailsRow && chevron) {
            if (detailsRow.classList.contains('hidden')) {
                detailsRow.classList.remove('hidden');
                chevron.classList.add('rotate-180');
            } else {
                detailsRow.classList.add('hidden');
                chevron.classList.remove('rotate-180');
            }
        }
    }
</script>
@endsection
