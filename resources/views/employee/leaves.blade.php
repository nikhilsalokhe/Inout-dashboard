@extends('layouts.employee')

@section('title', 'Employee Leaves Portal')

@section('content')
<div class="mb-8">
    <p class="text-slate-500 text-sm font-medium">Request leaves, review your active balances, and track manager approval logs.</p>
</div>

<!-- Errors display -->
@if($errors->any())
    <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl flex flex-col gap-1 shadow-sm">
        @foreach($errors->all() as $error)
            <div class="flex items-center gap-2">
                <div class="w-1.5 h-1.5 rounded-full bg-rose-500 shrink-0"></div>
                <span class="font-semibold text-xs leading-none">{{ $error }}</span>
            </div>
        @endforeach
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <!-- Left Column: Apply for Leave Form -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6 sticky top-24">
            <div class="flex items-center gap-2 mb-6">
                <div class="w-1 h-5 bg-indigo-500 rounded-full"></div>
                <h3 class="font-bold text-slate-800 text-base font-sans">Request Leave</h3>
            </div>

            <form action="{{ route('employee.leaves.apply') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                
                <div>
                    <label class="block text-slate-700 text-xs font-bold mb-2 uppercase tracking-wide">Leave Type</label>
                    <select name="leave_policy_id" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:border-indigo-500 focus:outline-none focus:bg-white transition-all">
                        <option value="">Select Leave Type</option>
                        @foreach($policies as $pol)
                            <option value="{{ $pol->id }}">{{ $pol->leave_name }} ({{ $pol->leave_type }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-slate-700 text-xs font-bold mb-2 uppercase tracking-wide">From Date</label>
                        <input type="date" name="from_date" required min="{{ date('Y-m-d') }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:border-indigo-500 focus:outline-none focus:bg-white transition-all">
                    </div>
                    <div>
                        <label class="block text-slate-700 text-xs font-bold mb-2 uppercase tracking-wide">To Date</label>
                        <input type="date" name="to_date" required min="{{ date('Y-m-d') }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:border-indigo-500 focus:outline-none focus:bg-white transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-slate-700 text-xs font-bold mb-2 uppercase tracking-wide">Reason</label>
                    <textarea name="reason" rows="4" required placeholder="Describe your reason for leave request..." class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:border-indigo-500 focus:outline-none focus:bg-white transition-all"></textarea>
                </div>

                <div>
                    <label class="block text-slate-700 text-xs font-bold mb-2 uppercase tracking-wide">Attachment (Optional)</label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-200 border-dashed rounded-xl hover:border-indigo-400 transition-colors">
                        <div class="space-y-1 text-center">
                            <i class="bi bi-cloud-arrow-up-fill text-slate-400 text-2xl"></i>
                            <div class="flex text-xs text-slate-600">
                                <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-semibold text-indigo-600 hover:text-indigo-500 focus-within:outline-none">
                                    <span>Upload a file</span>
                                    <input id="file-upload" name="attachment" type="file" class="sr-only">
                                </label>
                                <p class="pl-1">or drag and drop</p>
                            </div>
                            <p class="text-[9px] text-slate-400 font-medium">PDF, PNG, JPG up to 5MB</p>
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full py-3 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-indigo-500/25 hover:shadow-indigo-500/35 transition-all duration-300 transform hover:-translate-y-0.5 active:translate-y-0">Submit Application</button>
            </form>
        </div>
    </div>

    <!-- Right Column: Balances & History -->
    <div class="lg:col-span-2 space-y-8">
        
        <!-- Leave Balances -->
        <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6">
            <div class="flex items-center gap-2 mb-6">
                <div class="w-1 h-5 bg-indigo-500 rounded-full"></div>
                <h3 class="font-bold text-slate-800 text-base font-sans">Active Balances</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @forelse($balances as $bal)
                    <div class="bg-slate-50 border border-slate-200/30 p-4.5 rounded-2xl">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">{{ $bal->leavePolicy->leave_name }}</span>
                        <div class="flex justify-between items-baseline mb-2">
                            <span class="text-slate-800 font-extrabold text-lg">{{ $bal->remaining_leave }}</span>
                            <span class="text-[10px] text-slate-400 font-bold">/ {{ $bal->total_leave }} left</span>
                        </div>
                        <div class="w-full bg-slate-200 h-1 rounded-full overflow-hidden">
                            @php
                                $percent = $bal->total_leave > 0 ? ($bal->remaining_leave / $bal->total_leave) * 100 : 0;
                            @endphp
                            <div class="bg-indigo-600 h-full" style="width: {{ $percent }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 text-center py-4 font-semibold col-span-3">No active leave policies or balances found.</p>
                @endforelse
            </div>
        </div>

        <!-- History -->
        <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6 overflow-hidden">
            <div class="flex items-center gap-2 mb-6">
                <div class="w-1 h-5 bg-indigo-500 rounded-full"></div>
                <h3 class="font-bold text-slate-800 text-base font-sans">Request History</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse whitespace-nowrap text-xs">
                    <thead class="bg-slate-50/70 border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider">
                        <tr>
                            <th class="px-5 py-4">Dates</th>
                            <th class="px-5 py-4">Type</th>
                            <th class="px-5 py-4">Total Days</th>
                            <th class="px-5 py-4">Reason</th>
                            <th class="px-5 py-4">Status</th>
                            <th class="px-5 py-4">Approver</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($applications as $app)
                            <tr class="hover:bg-slate-50/30 transition-colors">
                                <td class="px-5 py-4 font-semibold text-slate-800">
                                    {{ $app->from_date }} <i class="bi bi-arrow-right mx-1.5 text-slate-400"></i> {{ $app->to_date }}
                                    <span class="text-[9px] text-slate-400 font-semibold block mt-0.5">Applied: {{ $app->created_at->format('M d, Y') }}</span>
                                </td>
                                <td class="px-5 py-4 font-bold text-slate-600">
                                    {{ $app->leavePolicy->leave_name }}
                                </td>
                                <td class="px-5 py-4 font-bold text-slate-800">
                                    {{ $app->total_days }} days
                                </td>
                                <td class="px-5 py-4 text-slate-500 max-w-xs truncate" title="{{ $app->reason }}">
                                    {{ $app->reason }}
                                </td>
                                <td class="px-5 py-4">
                                    @if($app->status === 'pending')
                                        <span class="px-2 py-0.5 bg-yellow-50 text-yellow-700 border border-yellow-100 rounded text-[9px] font-extrabold uppercase">Pending</span>
                                    @elseif($app->status === 'approved')
                                        <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-100 rounded text-[9px] font-extrabold uppercase">Approved</span>
                                    @else
                                        <span class="px-2 py-0.5 bg-rose-50 text-rose-700 border border-rose-100 rounded text-[9px] font-extrabold uppercase">Rejected</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    @if($app->status === 'approved' || $app->status === 'rejected')
                                        <span class="font-semibold text-slate-800 block">{{ $app->approver->name ?? 'HR Manager' }}</span>
                                        @if($app->remarks)
                                            <span class="text-[9px] text-slate-400 block italic mt-0.5">"{{ $app->remarks }}"</span>
                                        @endif
                                    @else
                                        <span class="text-slate-400 italic">Waiting approval</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-slate-400 font-semibold">
                                    No leave requests submitted yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                {{ $applications->links() }}
            </div>
        </div>

    </div>

</div>

<script>
    document.getElementById('file-upload').addEventListener('change', function(e) {
        var fileName = e.target.files[0] ? e.target.files[0].name : "Upload a file";
        document.querySelector('#file-upload').parentElement.querySelector('span').innerText = fileName;
    });
</script>
@endsection
