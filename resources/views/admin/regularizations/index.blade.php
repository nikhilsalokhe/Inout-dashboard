@extends('layouts.admin')

@section('title', 'Attendance Regularization Queue')

@section('content')
<!-- Include jQuery & Select2 CDN -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    /* Premium Tailwind styling for Select2 */
    .select2-container {
        width: 100% !important;
    }
    .select2-container .select2-selection--single {
        height: 42px !important;
        background-color: #f8fafc !important; /* bg-slate-50 */
        border: 1px solid #e2e8f0 !important; /* border-slate-200 */
        border-radius: 0.75rem !important; /* rounded-xl */
        display: flex;
        align-items: center;
        transition: all 0.2s ease;
    }
    .select2-container--open .select2-selection--single,
    .select2-container .select2-selection--single:focus,
    .select2-container .select2-selection--single:hover {
        border-color: #6366f1 !important; /* border-indigo-500 */
        box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.15);
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #334155 !important; /* text-slate-700 */
        font-size: 0.75rem !important; /* text-xs */
        font-weight: 700 !important; /* font-bold */
        padding-left: 1rem !important;
        padding-right: 2rem !important;
        width: 100%;
    }
    /* Modal specific size styling */
    .select-large + .select2-container .select2-selection--single {
        height: 48px !important;
    }
    .select-large + .select2-container .select2-selection--single .select2-selection__rendered {
        font-size: 0.875rem !important; /* text-sm */
        font-weight: 600 !important; /* font-semibold */
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 100% !important;
        right: 12px !important;
        display: flex;
        align-items: center;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow b {
        border-color: #64748b transparent transparent transparent !important; /* text-slate-500 */
        border-width: 5px 4px 0 4px !important;
    }
    .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
        border-color: transparent transparent #64748b transparent !important;
        border-width: 0 4px 5px 4px !important;
    }
    .select2-dropdown {
        background-color: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 0.75rem !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
        z-index: 9999 !important;
        overflow: hidden;
        margin-top: 4px;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field {
        border: 1px solid #e2e8f0 !important;
        border-radius: 0.5rem !important;
        padding: 8px 12px !important;
        outline: none !important;
        font-size: 0.75rem !important;
        font-weight: 600;
        color: #334155;
    }
    .select2-container--default .select2-results__option {
        padding: 8px 12px !important;
        font-size: 0.75rem !important;
        font-weight: 600;
        color: #475569;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #4f46e5 !important; /* bg-indigo-600 */
        color: #ffffff !important;
    }
    .select2-container--default .select2-results__option[aria-selected=true] {
        background-color: #f1f5f9 !important;
        color: #1e293b !important;
        font-weight: 700;
    }
</style>

<div class="space-y-6 animate-fade-in">

    <!-- Header -->
    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-extrabold text-slate-900 mb-1 flex items-center gap-2">
                <i class="bi bi-check2-circle text-indigo-500"></i> Attendance Regularization Queue
            </h2>
            <p class="text-xs text-slate-400 font-medium">Review, edit, and approve/reject employee attendance correction requests. You can also manually create entries.</p>
        </div>
        <button onclick="document.getElementById('create-modal').classList.remove('hidden')"
                class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-wider px-5 py-3 rounded-xl transition-all duration-200 shadow-lg shadow-indigo-600/20 whitespace-nowrap">
            <i class="bi bi-plus-circle-fill text-base"></i> New Regularization Entry
        </button>
    </div>

    <!-- Alerts -->
    @if(session('error'))
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl flex items-center gap-3 shadow-sm font-semibold text-sm">
            <i class="bi bi-exclamation-triangle-fill text-rose-500 text-lg"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl flex items-center gap-3 shadow-sm font-semibold text-sm">
            <i class="bi bi-check-circle-fill text-emerald-500 text-lg"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Filters Panel -->
    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6">
        <form method="GET" action="{{ route('admin.regularizations.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Employee</label>
                <select name="employee_id" class="select2-select select-filter">
                    <option value="">All Employees</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ $employeeId == $emp->id ? 'selected' : '' }}>
                            {{ $emp->name }} ({{ $emp->employee_code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Status</label>
                <select name="status" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-200">
                    <option value="">All Statuses</option>
                    <option value="pending"  {{ $status == 'pending'  ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ $status == 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ $status == 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-wider px-5 py-3 rounded-xl transition-all duration-200 flex-1 shadow-lg shadow-indigo-600/10 flex items-center justify-center gap-2">
                    <i class="bi bi-filter"></i> Filter
                </button>
                <a href="{{ route('admin.regularizations.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs uppercase tracking-wider px-5 py-3 rounded-xl transition-all duration-200 flex-1 flex items-center justify-center gap-2">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Requests Table -->
    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="py-4 px-6">Employee</th>
                        <th class="py-4 px-6">Target Date</th>
                        <th class="py-4 px-6">Proposed Timings</th>
                        <th class="py-4 px-6">Reason</th>
                        <th class="py-4 px-6">Status</th>
                        <th class="py-4 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse ($regularizations as $reg)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <!-- Employee -->
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 font-extrabold text-xs shadow-inner">
                                        {{ strtoupper(substr($reg->employee->name ?? '?', 0, 2)) }}
                                    </div>
                                    <div>
                                        <span class="block font-bold text-slate-800 leading-tight">{{ $reg->employee->name ?? 'Deleted Employee' }}</span>
                                        <span class="block text-[10px] text-slate-400 font-semibold">{{ $reg->employee->department->department_name ?? '—' }}</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Date -->
                            <td class="py-4 px-6 font-bold text-slate-800">
                                {{ $reg->attendance_date->format('M d, Y') }}
                                <span class="block text-[10px] text-slate-400 font-semibold">{{ $reg->attendance_date->format('l') }}</span>
                            </td>

                            <!-- Proposed Timings -->
                            <td class="py-4 px-6">
                                <div class="text-xs text-slate-700 leading-relaxed font-semibold space-y-0.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[9px] uppercase font-black text-emerald-600 bg-emerald-50 border border-emerald-100 px-1.5 py-0.5 rounded">In</span>
                                        {{ $reg->check_in ? $reg->check_in->format('h:i A') : '—' }}
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[9px] uppercase font-black text-rose-600 bg-rose-50 border border-rose-100 px-1.5 py-0.5 rounded">Out</span>
                                        {{ $reg->check_out ? $reg->check_out->format('h:i A') : '—' }}
                                    </div>
                                </div>
                            </td>

                            <!-- Reason -->
                            <td class="py-4 px-6">
                                <p class="text-xs text-slate-500 font-medium max-w-[200px] truncate" title="{{ $reg->reason }}">
                                    {{ $reg->reason }}
                                </p>
                            </td>

                            <!-- Status Badge -->
                            <td class="py-4 px-6">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider border
                                    @if($reg->status == 'pending')  bg-amber-50  text-amber-600  border-amber-100
                                    @elseif($reg->status == 'approved') bg-emerald-50 text-emerald-600 border-emerald-100
                                    @else bg-rose-50 text-rose-600 border-rose-100
                                    @endif">
                                    {{ $reg->status }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="py-4 px-6 text-right">
                                @if (strtolower($reg->status) === 'pending')
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Edit timings button -->
                                        <button onclick="toggleEditPanel({{ $reg->id }})"
                                                class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs transition-colors flex items-center gap-1.5">
                                            <i class="bi bi-pencil-fill text-xs"></i> Edit
                                        </button>
                                        <!-- Review / approve-reject button -->
                                        <button onclick="toggleReviewPanel({{ $reg->id }})"
                                                class="px-3.5 py-2 rounded-xl bg-slate-900 text-white hover:bg-slate-800 font-bold text-xs transition-colors flex items-center gap-1.5">
                                            <i class="bi bi-check2-square text-xs"></i> Review
                                        </button>
                                    </div>
                                @else
                                    <div class="text-[10px] text-slate-400 font-semibold leading-relaxed text-right">
                                        <span class="block font-bold text-slate-600">{{ $reg->approver->name ?? 'System' }}</span>
                                        <span>{{ $reg->approved_at ? $reg->approved_at->format('M d, h:i A') : '—' }}</span>
                                        @if($reg->remarks)
                                            <span class="block mt-0.5 italic text-slate-400 max-w-[160px] truncate" title="{{ $reg->remarks }}">{{ $reg->remarks }}</span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>

                        {{-- EDIT TIMINGS PANEL --}}
                        @if (strtolower($reg->status) === 'pending')
                        <tr id="edit-panel-{{ $reg->id }}" class="hidden bg-sky-50/40 border-t border-sky-100">
                            <td colspan="6" class="px-6 py-5">
                                <div class="max-w-xl mx-auto bg-white rounded-2xl border border-sky-200/60 shadow-sm p-5">
                                    <div class="flex items-center justify-between mb-4">
                                        <h4 class="font-extrabold text-slate-800 text-sm flex items-center gap-2">
                                            <i class="bi bi-pencil-fill text-sky-500"></i> Edit Regularization Request
                                        </h4>
                                        <button onclick="toggleEditPanel({{ $reg->id }})" class="text-slate-400 hover:text-slate-700 text-xs">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                    <form action="{{ route('admin.regularizations.update', $reg->id) }}" method="POST" class="space-y-4">
                                        @csrf
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Employee <span class="text-rose-500">*</span></label>
                                                <select name="employee_id" required class="w-full px-4 py-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 font-bold text-slate-700 bg-slate-50">
                                                    @foreach($employees as $emp)
                                                        <option value="{{ $emp->id }}" {{ $reg->employee_id == $emp->id ? 'selected' : '' }}>
                                                            {{ $emp->name }} ({{ $emp->employee_code }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Attendance Date <span class="text-rose-500">*</span></label>
                                                <input type="date" name="attendance_date" value="{{ $reg->attendance_date->format('Y-m-d') }}" required max="{{ date('Y-m-d') }}"
                                                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-700 focus:outline-none focus:border-indigo-500 transition-all">
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">
                                                    <i class="bi bi-box-arrow-in-right text-emerald-500"></i> Check-in Time <span class="text-rose-500">*</span>
                                                </label>
                                                <input type="time" name="check_in"
                                                       value="{{ $reg->check_in ? $reg->check_in->format('H:i') : '' }}"
                                                       required
                                                       class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 font-bold text-slate-700 bg-slate-50">
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">
                                                    <i class="bi bi-box-arrow-left text-rose-500"></i> Check-out Time
                                                </label>
                                                <input type="time" name="check_out"
                                                       value="{{ $reg->check_out ? $reg->check_out->format('H:i') : '' }}"
                                                       class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 font-bold text-slate-700 bg-slate-50">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Reason / Justification <span class="text-rose-500">*</span></label>
                                            <textarea name="reason" rows="2" required
                                                      class="w-full px-4 py-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 font-semibold text-slate-700 bg-slate-50"
                                                      placeholder="Provide notes regarding the correction...">{{ $reg->reason }}</textarea>
                                        </div>
                                        <div class="flex justify-end">
                                            <button type="submit"
                                                    class="px-6 py-2.5 bg-sky-600 hover:bg-sky-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition-colors shadow-sm flex items-center gap-2">
                                                <i class="bi bi-floppy-fill"></i> Save Changes
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        {{-- REVIEW / APPROVE / REJECT PANEL --}}
                        <tr id="review-panel-{{ $reg->id }}" class="hidden bg-slate-50/60 border-t border-slate-100">
                            <td colspan="6" class="px-6 py-5">
                                <div class="max-w-2xl mx-auto bg-white rounded-2xl border border-slate-200/60 shadow-sm p-5 space-y-4">
                                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                        <h4 class="font-extrabold text-slate-800 text-sm flex items-center gap-2">
                                            <i class="bi bi-check2-square text-indigo-500"></i> Process Regularization Decision
                                        </h4>
                                        <button onclick="toggleReviewPanel({{ $reg->id }})" class="text-slate-400 hover:text-slate-700 text-xs">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                    <form action="{{ route('admin.regularizations.action', $reg->id) }}" method="POST" id="form-{{ $reg->id }}" class="space-y-4">
                                        @csrf

                                        <!-- Editable timings at approval step -->
                                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200/60">
                                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">
                                                <i class="bi bi-clock-history text-indigo-400"></i> Confirm or Adjust Final Timings Before Approving
                                            </p>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">
                                                        <i class="bi bi-box-arrow-in-right text-emerald-500"></i> Check-in Time
                                                    </label>
                                                    <input type="time" name="check_in"
                                                           value="{{ $reg->check_in ? $reg->check_in->format('H:i') : '' }}"
                                                           class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 font-bold text-slate-700 bg-white">
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">
                                                        <i class="bi bi-box-arrow-left text-rose-500"></i> Check-out Time
                                                    </label>
                                                    <input type="time" name="check_out"
                                                           value="{{ $reg->check_out ? $reg->check_out->format('H:i') : '' }}"
                                                           class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 font-bold text-slate-700 bg-white">
                                                </div>
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Admin Remarks / Comments (Optional)</label>
                                            <textarea name="remarks" rows="2"
                                                      placeholder="Provide notes regarding your approval or rejection..."
                                                      class="w-full px-4 py-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 font-semibold text-slate-700 bg-white"></textarea>
                                        </div>

                                        <div class="flex items-center justify-end gap-3 pt-1">
                                            <button type="button" onclick="submitDecision({{ $reg->id }}, 'reject')"
                                                    class="px-5 py-2.5 rounded-xl border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-700 font-extrabold text-xs transition-colors flex items-center gap-1.5">
                                                <i class="bi bi-x-circle-fill"></i> Reject Request
                                            </button>
                                            <button type="button" onclick="submitDecision({{ $reg->id }}, 'approve')"
                                                    class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-extrabold text-xs transition-colors shadow-sm flex items-center gap-1.5">
                                                <i class="bi bi-check-circle-fill"></i> Approve & Update Logs
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endif

                    @empty
                        <tr>
                            <td colspan="6" class="py-16 px-6 text-center">
                                <div class="flex flex-col items-center gap-3 text-slate-300">
                                    <i class="bi bi-check-circle text-5xl"></i>
                                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">No regularization requests found in this queue.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($regularizations->hasPages())
            <div class="p-6 border-t border-slate-100">
                {{ $regularizations->links() }}
            </div>
        @endif
    </div>
</div>

{{-- ========================= CREATE REGULARIZATION MODAL ========================= --}}
<div id="create-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
    <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl border border-slate-200/60 overflow-hidden">
        <!-- Modal Header -->
        <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-indigo-600 to-indigo-700">
            <h3 class="font-extrabold text-white text-base flex items-center gap-2">
                <i class="bi bi-plus-circle-fill"></i> Create Regularization Entry
            </h3>
            <button onclick="document.getElementById('create-modal').classList.add('hidden')"
                    class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition">
                <i class="bi bi-x-lg text-sm"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <form action="{{ route('admin.regularizations.store') }}" method="POST" class="p-6 space-y-4">
            @csrf

            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Employee <span class="text-rose-500">*</span></label>
                <select name="employee_id" required class="select2-select select-large select-modal">
                    <option value="">Select Employee…</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->employee_code }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Attendance Date <span class="text-rose-500">*</span></label>
                <input type="date" name="attendance_date" required max="{{ date('Y-m-d') }}"
                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">
                        <i class="bi bi-box-arrow-in-right text-emerald-500"></i> Check-in Time <span class="text-rose-500">*</span>
                    </label>
                    <input type="time" name="check_in" required
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">
                        <i class="bi bi-box-arrow-left text-rose-500"></i> Check-out Time
                    </label>
                    <input type="time" name="check_out"
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                    <p class="text-[9px] text-slate-400 mt-1 font-semibold">Leave blank if employee hasn't checked out yet.</p>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Reason / Justification <span class="text-rose-500">*</span></label>
                <textarea name="reason" rows="3" required
                          placeholder="e.g. Biometric machine malfunction, client site visit, system error..."
                          class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all resize-none"></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-2 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('create-modal').classList.add('hidden')"
                        class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs uppercase tracking-wider rounded-xl transition-colors">
                    Cancel
                </button>
                <button type="submit"
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition-colors shadow-sm flex items-center gap-2">
                    <i class="bi bi-plus-circle-fill"></i> Create Entry
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Initialize Select2 for filters
        $('.select-filter').select2({
            placeholder: "All Employees",
            allowClear: true
        });

        // Initialize Select2 for Modal dropdown
        $('.select-modal').select2({
            placeholder: "Select Employee…",
            allowClear: false,
            dropdownParent: $('#create-modal') // Important to prevent focus/rendering issues inside fixed modal
        });
    });

    function toggleEditPanel(id) {
        const editPanel   = document.getElementById('edit-panel-' + id);
        const reviewPanel = document.getElementById('review-panel-' + id);
        if (reviewPanel) reviewPanel.classList.add('hidden');
        if (editPanel) editPanel.classList.toggle('hidden');
    }

    function toggleReviewPanel(id) {
        const editPanel   = document.getElementById('edit-panel-' + id);
        const reviewPanel = document.getElementById('review-panel-' + id);
        if (editPanel) editPanel.classList.add('hidden');
        if (reviewPanel) reviewPanel.classList.toggle('hidden');
    }

    function submitDecision(id, decision) {
        const form = document.getElementById('form-' + id);
        const existing = form.querySelector('input[name="action"]');
        if (existing) existing.remove();
        const actionInput = document.createElement('input');
        actionInput.type  = 'hidden';
        actionInput.name  = 'action';
        actionInput.value = decision;
        form.appendChild(actionInput);
        form.submit();
    }

    // Close create modal on backdrop click
    document.getElementById('create-modal').addEventListener('click', function(e) {
        if (e.target === this) this.classList.add('hidden');
    });
</script>
@endsection
