@extends('layouts.admin')

@section('title', 'Face Biometrics Reset Requests')

@section('content')
<div class="mb-8">
    <p class="text-slate-500 text-sm font-medium">Verify, compare, and authorize requests from staff members wishing to reset or re-configure their face registration profiles.</p>
</div>

<!-- Table Card container -->
<div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50/70 border-b border-slate-100 text-slate-400 font-bold text-[10px] uppercase tracking-widest">
                <tr>
                    <th class="px-8 py-5">Employee Info</th>
                    <th class="px-8 py-5">Corporate ID</th>
                    <th class="px-8 py-5">Requested Date</th>
                    <th class="px-8 py-5">Request Status</th>
                    <th class="px-8 py-5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($requests as $req)
                    <tr class="hover:bg-slate-50/30 transition-colors group">
                        <!-- Employee Info -->
                        <td class="px-8 py-4.5">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-gradient-to-tr from-indigo-500 to-purple-600 text-white rounded-full flex items-center justify-center font-bold text-sm shadow-md shadow-indigo-500/10 border-2 border-white">
                                    {{ strtoupper(substr($req->employee->name, 0, 1)) }}
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-800 text-sm leading-tight mb-0.5">{{ $req->employee->name }}</h4>
                                    <p class="text-xs text-slate-400 font-medium">{{ $req->employee->email }}</p>
                                </div>
                            </div>
                        </td>
                        
                        <!-- Corporate ID -->
                        <td class="px-8 py-4.5">
                            <span class="px-3 py-1.5 bg-slate-50 text-slate-600 rounded-xl text-xs font-bold border border-slate-100 tracking-wide">
                                {{ $req->employee->employee_code }}
                            </span>
                        </td>

                        <!-- Requested Date -->
                        <td class="px-8 py-4.5 text-xs text-slate-500 font-semibold">
                            {{ $req->requested_at->format('M d, Y \a\t h:i A') }}
                        </td>
                        
                        <!-- Request Status -->
                        <td class="px-8 py-4.5">
                            @if($req->status === 'pending')
                                <span class="flex items-center gap-1.5 text-amber-700 text-xs font-bold bg-amber-50 border border-amber-100/60 px-3 py-1 rounded-full w-fit">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse shadow-[0_0_6px_#f59e0b]"></span>
                                    Pending Approval
                                </span>
                            @elseif($req->status === 'approved')
                                <span class="flex items-center gap-1.5 text-emerald-700 text-xs font-bold bg-emerald-50 border border-emerald-100/60 px-3 py-1 rounded-full w-fit">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-[0_0_6px_#10b981]"></span>
                                    Approved
                                </span>
                            @else
                                <span class="flex items-center gap-1.5 text-rose-700 text-xs font-bold bg-rose-50 border border-rose-100/60 px-3 py-1 rounded-full w-fit">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 shadow-[0_0_6px_#f43f5e]"></span>
                                    Rejected
                                </span>
                            @endif
                        </td>
                        
                        <!-- Actions -->
                        <td class="px-8 py-4.5 text-right">
                            <a href="{{ route('admin.face-resets.show', $req->id) }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-50 hover:bg-indigo-50 border border-slate-200/50 hover:border-indigo-100 text-slate-600 hover:text-indigo-600 text-xs font-bold transition-all duration-300">
                                <span>Review Request</span>
                                <i class="bi bi-arrow-right-short text-base leading-none"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-8 py-16 text-center text-slate-400">
                            <div class="flex flex-col items-center gap-4 max-w-sm mx-auto">
                                <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-300 text-2xl border border-slate-100">
                                    <i class="bi bi-shield-slash-fill"></i>
                                </div>
                                <div>
                                    <h5 class="font-bold text-slate-800 text-base mb-1">No requests registered</h5>
                                    <p class="text-xs text-slate-400 leading-relaxed font-semibold">There are currently no biometric face profile reset requests submitted by your staff.</p>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($requests->hasPages())
        <div class="p-6 border-t border-slate-100 bg-slate-50/50">
            {{ $requests->links() }}
        </div>
    @endif
</div>
@endsection
