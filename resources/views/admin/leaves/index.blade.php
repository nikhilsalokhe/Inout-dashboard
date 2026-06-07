@extends('layouts.admin')

@section('title', 'Leave Policies Configuration')

@section('content')
<div class="max-w-6xl mx-auto space-y-8 animate-fade-in">
    <!-- Header panel -->
    <div class="bg-gradient-to-r from-emerald-900 to-indigo-950 text-white rounded-3xl p-8 shadow-xl relative overflow-hidden border border-emerald-800">
        <div class="absolute right-0 top-0 -mt-6 -mr-6 w-72 h-72 bg-emerald-500/10 rounded-full blur-3xl"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-400 bg-emerald-950/60 px-3 py-1 rounded-full border border-emerald-900/50 inline-block mb-3">
                    HR Policies & Accruals
                </span>
                <h2 class="text-3xl font-extrabold tracking-tight mb-2">Leave Management Workspace</h2>
                <p class="text-slate-300 text-sm max-w-xl">
                    Configure leave categories, balance caps, carry-forward options, and approval workflows. Enable employees to request off-duty logs.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('admin.leaves.balances') }}" class="px-5 py-3 rounded-2xl bg-white/10 hover:bg-white/15 text-white font-bold text-sm transition-all duration-300 border border-white/10 shadow-lg backdrop-blur-md">
                    <i class="bi bi-person-badge mr-1"></i> Balances Audit
                </a>
                <a href="{{ route('admin.leaves.applications') }}" class="px-5 py-3 rounded-2xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-sm transition-all duration-300 shadow-lg shadow-emerald-500/20">
                    <i class="bi bi-journal-check mr-1"></i> Approval Queue
                </a>
            </div>
        </div>
    </div>

    {{-- Error/Validation Messages --}}
    @if(session('error'))
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl flex items-center gap-3 shadow-sm animate-fade-in">
            <div class="w-8 h-8 rounded-lg bg-rose-500 flex items-center justify-center text-white text-sm">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <span class="font-semibold text-sm">{{ session('error') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl space-y-2 shadow-sm animate-fade-in">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-rose-500 flex items-center justify-center text-white text-sm">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <span class="font-bold text-sm">Please correct the following errors:</span>
            </div>
            <ul class="list-disc list-inside text-xs text-rose-700 pl-11 font-semibold space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left: Leave Policies List -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-800">Active Leave Policies</h3>
                    <span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold">{{ $policies->count() }} Categories</span>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($policies as $policy)
                        <div class="p-6 hover:bg-slate-50/50 transition-colors flex items-center justify-between gap-4">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2.5">
                                    <span class="font-extrabold text-slate-800 text-base">{{ $policy->leave_name }}</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-extrabold bg-slate-100 text-slate-600 uppercase border">{{ $policy->leave_code }}</span>
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider
                                        @if($policy->leave_type == 'paid') bg-emerald-50 text-emerald-600 border border-emerald-100
                                        @else bg-amber-50 text-amber-600 border border-amber-100
                                        @endif">
                                        {{ $policy->leave_type }}
                                    </span>
                                </div>
                                <div class="flex flex-wrap items-center gap-y-1 gap-x-4 text-xs text-slate-400 font-medium">
                                    <span><strong class="text-slate-600">{{ $policy->total_yearly_leave }}</strong> yearly days</span>
                                    <span>•</span>
                                    <span>Accrual: <strong class="text-slate-600">{{ $policy->monthly_credit }}</strong>/month</span>
                                    <span>•</span>
                                    <span>Carry Forward: <strong class="text-slate-600">{{ $policy->carry_forward ? 'Yes' : 'No' }}</strong></span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button onclick="editPolicy({{ json_encode($policy) }})" class="px-3.5 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs transition-colors">
                                    Edit
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="p-12 text-center text-slate-400 font-semibold italic">
                            <i class="bi bi-journal-code text-3xl block mb-2 text-slate-300"></i>
                            No leave policies configured yet. Create one on the right.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Right: Create/Edit Form Panel -->
        <div>
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 space-y-6 sticky top-24">
                <div>
                    <h3 id="form-title" class="text-base font-bold text-slate-800">Create New Policy</h3>
                    <p id="form-subtitle" class="text-xs text-slate-400 mt-1">Configure parameters for leave accruals and approvals.</p>
                </div>

                <form id="policy-form" action="{{ route('admin.leaves.policies.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" id="policy-method" name="_method" value="POST">
                    
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Leave Category Name</label>
                        <input type="text" name="leave_name" id="leave_name" required placeholder="e.g. Privilege Leave" class="w-full px-4 py-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 font-semibold text-slate-700 bg-white">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Code</label>
                            <input type="text" name="leave_code" id="leave_code" required placeholder="e.g. PL" class="w-full px-4 py-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 font-semibold text-slate-700 bg-white uppercase">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Type</label>
                            <select name="leave_type" id="leave_type" class="w-full px-4 py-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 font-semibold text-slate-700 bg-white">
                                <option value="paid">Paid</option>
                                <option value="unpaid">Unpaid</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Yearly Limit</label>
                            <input type="number" name="total_yearly_leave" id="total_yearly_leave" required value="12" min="0" class="w-full px-4 py-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 font-semibold text-slate-700 bg-white">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Monthly Credit</label>
                            <input type="number" step="0.05" name="monthly_credit" id="monthly_credit" required value="1.00" min="0" class="w-full px-4 py-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 font-semibold text-slate-700 bg-white">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Carry Forward</label>
                            <select name="carry_forward" id="carry_forward" class="w-full px-4 py-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 font-semibold text-slate-700 bg-white" onchange="toggleCarryForward()">
                                <option value="0">Disabled</option>
                                <option value="1">Enabled</option>
                            </select>
                        </div>
                        <div id="max-cf-container">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Max Carry Forward</label>
                            <input type="number" name="max_carry_forward" id="max_carry_forward" value="0" min="0" class="w-full px-4 py-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 font-semibold text-slate-700 bg-white">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Requires Mgr Approval</label>
                            <select name="requires_approval" id="requires_approval" class="w-full px-4 py-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 font-semibold text-slate-700 bg-white">
                                <option value="1">Yes</option>
                                <option value="0">No (Auto-Approve)</option>
                            </select>
                        </div>
                        <div id="status-container" class="hidden">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Status</label>
                            <select name="status" id="status" class="w-full px-4 py-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 font-semibold text-slate-700 bg-white">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="pt-4 flex items-center justify-end gap-2">
                        <button type="button" id="cancel-edit-btn" onclick="resetForm()" class="hidden px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-500 font-bold text-xs transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-900 text-white font-extrabold text-xs hover:bg-slate-800 transition-colors shadow-sm">
                            Save Policy
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
    function toggleCarryForward() {
        const cfSelect = document.getElementById('carry_forward');
        const container = document.getElementById('max-cf-container');
        if (cfSelect && container) {
            if (cfSelect.value == '1') {
                container.style.opacity = '1';
                container.querySelectorAll('input').forEach(i => i.disabled = false);
            } else {
                container.style.opacity = '0.5';
                container.querySelectorAll('input').forEach(i => i.disabled = true);
            }
        }
    }

    function editPolicy(policy) {
        // Switch form details
        document.getElementById('form-title').innerText = 'Modify Policy: ' + policy.leave_code;
        document.getElementById('form-subtitle').innerText = 'Make modifications to policy constraints.';
        
        // Populate inputs
        document.getElementById('leave_name').value = policy.leave_name;
        document.getElementById('leave_code').value = policy.leave_code;
        document.getElementById('leave_type').value = policy.leave_type;
        document.getElementById('total_yearly_leave').value = policy.total_yearly_leave;
        document.getElementById('monthly_credit').value = policy.monthly_credit;
        document.getElementById('carry_forward').value = policy.carry_forward ? '1' : '0';
        document.getElementById('max_carry_forward').value = policy.max_carry_forward;
        document.getElementById('requires_approval').value = policy.requires_approval ? '1' : '0';
        document.getElementById('status').value = policy.status;

        // Form action & method
        const form = document.getElementById('policy-form');
        form.action = '/admin/leaves/policies/' + policy.id + '/update';
        document.getElementById('policy-method').value = 'POST'; // Since Laravel routing can capture POST for custom updates

        // Show status & cancel button
        document.getElementById('status-container').classList.remove('hidden');
        document.getElementById('cancel-edit-btn').classList.remove('hidden');

        toggleCarryForward();
    }

    function resetForm() {
        document.getElementById('form-title').innerText = 'Create New Policy';
        document.getElementById('form-subtitle').innerText = 'Configure parameters for leave accruals and approvals.';
        
        document.getElementById('policy-form').reset();
        document.getElementById('policy-form').action = "{{ route('admin.leaves.policies.store') }}";
        document.getElementById('policy-method').value = 'POST';

        document.getElementById('status-container').classList.add('hidden');
        document.getElementById('cancel-edit-btn').classList.add('hidden');
        
        toggleCarryForward();
    }

    document.addEventListener('DOMContentLoaded', () => {
        toggleCarryForward();
    });
</script>
@endsection
