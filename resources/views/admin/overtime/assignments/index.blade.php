@extends('layouts.admin')

@section('title', 'Assignments - Overtime Policy')

@section('content')
<div class="max-w-7xl mx-auto space-y-6 animate-fade-in">
    <!-- Header -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-purple-950 text-white rounded-3xl p-8 shadow-xl relative overflow-hidden">
        <div class="absolute right-0 top-0 -mt-6 -mr-6 w-72 h-72 bg-indigo-500/10 rounded-full blur-3xl"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-orange-400 bg-orange-950/60 px-3 py-1 rounded-full border border-orange-900/50 inline-block mb-3">
                    Overtime Engine
                </span>
                <h2 class="text-3xl font-extrabold tracking-tight mb-2">Policy Assignments</h2>
                <p class="text-slate-300 text-sm max-w-xl">
                    Bind overtime policies to specific departments or individual employees. User-level policies override department policies.
                </p>
            </div>
            <div>
                <button onclick="document.getElementById('assignModal').classList.remove('hidden')" class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-white hover:bg-slate-50 text-indigo-900 font-bold text-sm shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all duration-300">
                    <i class="bi bi-link-45deg"></i>
                    <span>Assign Policy</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-800">Current Assignments</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100 text-xs uppercase tracking-wider font-bold text-slate-500">
                        <th class="p-4 pl-6">Assigned To (Target)</th>
                        <th class="p-4">Target Type</th>
                        <th class="p-4">Policy Applied</th>
                        <th class="p-4 text-center">Assigned Date</th>
                        <th class="p-4 pr-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($assignments as $assignment)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="p-4 pl-6 font-bold text-slate-800">
                            @if($assignment->assignable_type === 'App\Models\User')
                                <i class="bi bi-person text-indigo-500 mr-2"></i> {{ $assignment->assignable->name ?? 'N/A' }}
                            @else
                                <i class="bi bi-building text-purple-500 mr-2"></i> {{ $assignment->assignable->department_name ?? 'N/A' }}
                            @endif
                        </td>
                        <td class="p-4">
                            @if($assignment->assignable_type === 'App\Models\User')
                                <span class="px-2.5 py-1 rounded-md font-semibold text-[10px] bg-indigo-50 text-indigo-600 uppercase tracking-wider">Employee</span>
                            @else
                                <span class="px-2.5 py-1 rounded-md font-semibold text-[10px] bg-purple-50 text-purple-600 uppercase tracking-wider">Department</span>
                            @endif
                        </td>
                        <td class="p-4">
                            <div class="font-semibold text-slate-700">{{ $assignment->policy->name ?? 'Deleted Policy' }}</div>
                        </td>
                        <td class="p-4 text-center text-slate-500">
                            {{ $assignment->created_at->format('d M, Y') }}
                        </td>
                        <td class="p-4 pr-6 text-right space-x-2">
                            <form action="{{ route('admin.overtime.assignments.destroy', $assignment) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to remove this assignment?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-rose-600 hover:bg-rose-50 transition-colors">
                                    <i class="bi bi-unlink"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-slate-500">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-50 mb-3 text-slate-400">
                                <i class="bi bi-link text-2xl"></i>
                            </div>
                            <p class="font-medium">No policies assigned yet.</p>
                            <button onclick="document.getElementById('assignModal').classList.remove('hidden')" class="text-indigo-600 hover:underline text-sm font-semibold mt-1 inline-block">Assign a policy now</button>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $assignments->links() }}
        </div>
    </div>
</div>

<!-- Assign Modal -->
<div id="assignModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-slate-900/50 backdrop-blur-sm" onclick="document.getElementById('assignModal').classList.add('hidden')"></div>

        <div class="relative inline-block w-full max-w-lg overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-3xl">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                <h3 class="text-lg font-bold text-slate-800">Assign Policy</h3>
                <button onclick="document.getElementById('assignModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            
            <form action="{{ route('admin.overtime.assignments.store') }}" method="POST" class="p-6 space-y-6">
                @csrf
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Select Policy</label>
                    <select name="policy_id" required class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 bg-white">
                        <option value="">-- Choose Policy --</option>
                        @foreach($policies as $policy)
                            <option value="{{ $policy->id }}">{{ $policy->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Target Type</label>
                    <select name="assignable_type" id="targetType" required class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 bg-white" onchange="toggleTargets()">
                        <option value="Department">Department</option>
                        <option value="User">Specific Employee</option>
                    </select>
                </div>

                <div id="deptTarget">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Select Department</label>
                    <select name="assignable_id" id="deptSelect" required class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 bg-white">
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->department_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="userTarget" class="hidden">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Select Employee</label>
                    <select name="assignable_id" id="userSelect" disabled class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 bg-white">
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->employee_code }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('assignModal').classList.add('hidden')" class="px-5 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 font-semibold text-sm transition-all">Cancel</button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm shadow-md transition-all">Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleTargets() {
        const type = document.getElementById('targetType').value;
        const deptWrap = document.getElementById('deptTarget');
        const userWrap = document.getElementById('userTarget');
        const deptSel = document.getElementById('deptSelect');
        const userSel = document.getElementById('userSelect');

        if(type === 'Department') {
            deptWrap.classList.remove('hidden');
            userWrap.classList.add('hidden');
            deptSel.disabled = false;
            userSel.disabled = true;
        } else {
            deptWrap.classList.add('hidden');
            userWrap.classList.remove('hidden');
            deptSel.disabled = true;
            userSel.disabled = false;
        }
    }
</script>
@endsection
