@extends('layouts.admin')

@section('title', 'Review Reset Request')

@section('content')
<div class="mb-8 flex items-center justify-between">
    <a href="{{ route('admin.face-resets.index') }}" class="inline-flex items-center gap-2 text-slate-500 hover:text-indigo-600 text-sm font-bold transition-colors">
        <i class="bi bi-arrow-left"></i>
        <span>Back to Directory</span>
    </a>
    
    <div>
        @if($request->status === 'pending')
            <span class="flex items-center gap-1.5 text-amber-700 text-xs font-bold bg-amber-50 border border-amber-100/60 px-3 py-1.5 rounded-full shadow-sm">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                Awaiting Admin Action
            </span>
        @elseif($request->status === 'approved')
            <span class="flex items-center gap-1.5 text-emerald-700 text-xs font-bold bg-emerald-50 border border-emerald-100/60 px-3 py-1.5 rounded-full shadow-sm">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                Approved & Activated
            </span>
        @else
            <span class="flex items-center gap-1.5 text-rose-700 text-xs font-bold bg-rose-50 border border-rose-100/60 px-3 py-1.5 rounded-full shadow-sm">
                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                Rejected
            </span>
        @endif
    </div>
</div>

@if(session('error'))
    <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl flex items-center gap-3 shadow-sm">
        <div class="w-8 h-8 rounded-lg bg-rose-500 flex items-center justify-center text-white text-sm">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>
        <span class="font-semibold text-sm">{{ session('error') }}</span>
    </div>
@endif

<!-- Comparison Dashboard Grid -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    
    <!-- Left Column: Existing Profile Photo -->
    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6 flex flex-col">
        <div class="border-b border-slate-100 pb-4 mb-6 flex justify-between items-center">
            <div>
                <h3 class="font-extrabold text-slate-900 text-base leading-tight">Registered Face Profile</h3>
                <p class="text-xs text-slate-400 font-semibold mt-0.5">Currently active biometrics identifier.</p>
            </div>
            <span class="px-2.5 py-1 bg-slate-50 border border-slate-100 text-slate-500 text-[10px] font-bold rounded-lg uppercase tracking-wider">Active</span>
        </div>
        
        <div class="flex-1 flex flex-col items-center justify-center py-6 min-h-[300px]">
            @if($request->old_face_image)
                <div class="relative group rounded-2xl overflow-hidden shadow-md max-w-[240px] border border-slate-200">
                    <img src="{{ Storage::url($request->old_face_image) }}" alt="Registered Profile" class="w-full h-auto max-h-[300px] object-cover">
                </div>
            @else
                <div class="text-center p-8 bg-slate-50 border border-dashed border-slate-200 rounded-2xl max-w-[280px]">
                    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-300 text-lg mx-auto mb-3">
                        <i class="bi bi-person-slash"></i>
                    </div>
                    <h5 class="font-bold text-slate-800 text-xs mb-1">No Profile Image</h5>
                    <p class="text-[10px] text-slate-400 font-semibold leading-relaxed">This employee does not have a registered face encoding stored in the database.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Right Column: New Face Registration Candidate -->
    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6 flex flex-col">
        <div class="border-b border-slate-100 pb-4 mb-6 flex justify-between items-center">
            <div>
                <h3 class="font-extrabold text-slate-900 text-base leading-tight">New Face Candidate</h3>
                <p class="text-xs text-slate-400 font-semibold mt-0.5">Captured face requested for active configuration.</p>
            </div>
            <span class="px-2.5 py-1 bg-indigo-50 border border-indigo-100/60 text-indigo-600 text-[10px] font-bold rounded-lg uppercase tracking-wider">Candidate</span>
        </div>
        
        <div class="flex-1 flex flex-col items-center justify-center py-6 min-h-[300px]">
            <div class="relative group rounded-2xl overflow-hidden shadow-md max-w-[240px] border border-indigo-200 ring-4 ring-indigo-500/10">
                <img src="{{ Storage::url($request->new_face_image) }}" alt="Candidate Photo" class="w-full h-auto max-h-[300px] object-cover">
            </div>
        </div>
    </div>

</div>

<!-- Admin Auditing & Actions Suite -->
<div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-8">
    <div class="border-b border-slate-100 pb-5 mb-6">
        <h3 class="font-extrabold text-slate-900 text-lg leading-tight">Auditing & Processing Panel</h3>
        <p class="text-xs text-slate-400 font-semibold mt-1">Audit verification results and finalize the approval request status.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 text-sm">
        <div class="p-4 bg-slate-50 border border-slate-100 rounded-2xl">
            <span class="text-slate-400 text-xs font-semibold uppercase block mb-1">Requested By</span>
            <span class="font-bold text-slate-800">{{ $request->employee->name }}</span>
            <span class="text-xs text-slate-400 font-medium block mt-0.5">{{ $request->employee->email }}</span>
        </div>
        <div class="p-4 bg-slate-50 border border-slate-100 rounded-2xl">
            <span class="text-slate-400 text-xs font-semibold uppercase block mb-1">Corporate ID</span>
            <span class="font-bold text-slate-800 tracking-wide">{{ $request->employee->employee_code }}</span>
        </div>
        <div class="p-4 bg-slate-50 border border-slate-100 rounded-2xl">
            <span class="text-slate-400 text-xs font-semibold uppercase block mb-1">Requested At</span>
            <span class="font-bold text-slate-800">{{ $request->requested_at->format('M d, Y \a\t h:i A') }}</span>
        </div>
    </div>

    @if($request->status === 'pending')
        <!-- Action Form: Pending Status -->
        <form action="" method="POST" id="process-form" class="space-y-6">
            @csrf
            <div>
                <label for="remarks" class="block text-sm font-bold text-slate-700 mb-2">Auditor Remarks (Optional)</label>
                <textarea 
                    name="remarks" 
                    id="remarks" 
                    rows="3" 
                    placeholder="Enter audit remarks or rejection reasons..." 
                    class="w-full px-4 py-3 rounded-2xl border border-slate-200/80 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all duration-200 text-sm placeholder-slate-400 font-medium"
                ></textarea>
            </div>

            <div class="flex flex-col sm:flex-row items-center gap-4 pt-4 border-t border-slate-100">
                <button 
                    type="submit" 
                    onclick="submitForm('approve')"
                    class="w-full sm:w-auto px-6 py-3.5 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-extrabold rounded-2xl shadow-lg shadow-indigo-500/10 hover:shadow-indigo-500/20 transition-all duration-300 flex items-center justify-center gap-2 hover:-translate-y-0.5 active:translate-y-0 text-sm"
                >
                    <i class="bi bi-shield-check"></i>
                    <span>Approve & Update Face Profile</span>
                </button>
                
                <button 
                    type="submit" 
                    onclick="submitForm('reject')"
                    class="w-full sm:w-auto px-6 py-3.5 bg-white border border-rose-200 hover:bg-rose-50 text-rose-600 font-extrabold rounded-2xl transition-all duration-300 flex items-center justify-center gap-2 hover:-translate-y-0.5 active:translate-y-0 text-sm"
                >
                    <i class="bi bi-shield-slash"></i>
                    <span>Reject Reset Request</span>
                </button>
            </div>
        </form>

        <script>
            function submitForm(action) {
                var form = document.getElementById('process-form');
                if (action === 'approve') {
                    form.action = "{{ route('admin.face-resets.approve', $request->id) }}";
                } else {
                    form.action = "{{ route('admin.face-resets.reject', $request->id) }}";
                }
            }
        </script>
    @else
        <!-- Action Summary Card: Approved/Rejected Status -->
        <div class="p-6 bg-slate-50 border border-slate-200/50 rounded-2xl">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white text-lg {{ $request->status === 'approved' ? 'bg-emerald-500 shadow-emerald-500/20' : 'bg-rose-500 shadow-rose-500/20' }}">
                    <i class="bi {{ $request->status === 'approved' ? 'bi-shield-check' : 'bi-shield-slash' }}"></i>
                </div>
                <div class="flex-1">
                    <h4 class="font-extrabold text-slate-800 text-sm mb-1">
                        Request has been {{ ucfirst($request->status) }}
                    </h4>
                    <p class="text-xs text-slate-500 font-medium">
                        Processed by <strong>{{ $request->approver->name }}</strong> on {{ $request->approved_at->format('M d, Y \a\t h:i A') }}.
                    </p>
                    
                    @if($request->remarks)
                        <div class="mt-4 pt-4 border-t border-slate-200/60">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Admin Remarks</span>
                            <p class="text-xs text-slate-600 font-medium leading-relaxed bg-white border border-slate-100 p-3 rounded-xl">
                                {{ $request->remarks }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
