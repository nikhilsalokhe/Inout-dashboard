@extends('layouts.admin')

@section('title', 'Corporate Announcements')

@section('content')
<div class="space-y-8 animate-fade-in">
    <!-- Header Summary & Navigation -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Corporate Announcements</h1>
            <p class="text-slate-500 text-sm font-medium mt-1">Manage corporate broadcasts, target specific departments or locations, and push real-time alerts to mobile nodes.</p>
        </div>
        <a href="{{ route('admin.announcements.create') }}" class="inline-flex items-center justify-center gap-2 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-extrabold px-5 py-3 rounded-2xl text-sm shadow-md shadow-indigo-500/10 hover:shadow-indigo-500/20 transition-all hover:-translate-y-0.5">
            <i class="bi bi-plus-lg"></i>
            <span>Publish Announcement</span>
        </a>
    </div>

    <!-- Alert Messaging Feedback -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-2xl flex items-center gap-3 shadow-sm font-semibold text-xs uppercase tracking-wider text-emerald-600">
            <i class="bi bi-check-circle-fill text-emerald-500 text-base"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Content Table / Empty State -->
    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
            <h3 class="font-extrabold text-slate-900 tracking-tight text-base">Broadcast History</h3>
            <p class="text-xs font-medium text-slate-500 mt-1">Historical track of all notifications pushed to users</p>
        </div>

        <div class="overflow-x-auto">
            @if($announcements->isEmpty())
                <div class="p-16 text-center flex flex-col items-center justify-center">
                    <div class="w-16 h-16 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 mb-4 border border-dashed border-slate-200">
                        <i class="bi bi-megaphone text-2xl"></i>
                    </div>
                    <span class="font-bold text-slate-800 text-sm block">No Announcements Pushed Yet</span>
                    <span class="text-xs text-slate-400 mt-1 block max-w-xs">Publish your first broadcast to alert staff about company updates.</span>
                </div>
            @else
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/30">
                            <th class="p-4 px-6 text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Announcement Details</th>
                            <th class="p-4 px-6 text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Target Scope</th>
                            <th class="p-4 px-6 text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Published By</th>
                            <th class="p-4 px-6 text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Date Published</th>
                            <th class="p-4 px-6 text-[10px] font-extrabold text-slate-400 uppercase tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($announcements as $item)
                            <tr class="hover:bg-slate-50/30 transition-colors duration-150">
                                <td class="p-4 px-6 max-w-sm">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-slate-800 text-sm mb-1 leading-snug">{{ $item->title }}</span>
                                        <span class="text-slate-400 text-xs line-clamp-2 leading-relaxed">{{ $item->content }}</span>
                                    </div>
                                </td>
                                <td class="p-4 px-6">
                                    @if($item->department)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-600 border border-blue-100">
                                            <i class="bi bi-building"></i>
                                            Dept: {{ $item->department->department_name }}
                                        </span>
                                    @elseif($item->location)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-teal-50 text-teal-600 border border-teal-100">
                                            <i class="bi bi-geo-alt"></i>
                                            Loc: {{ $item->location->location_name }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-600 border border-indigo-100">
                                            <i class="bi bi-globe"></i>
                                            All Employees
                                        </span>
                                    @endif
                                </td>
                                <td class="p-4 px-6">
                                    <span class="font-semibold text-slate-700 text-sm">{{ $item->creator->name ?? 'System Admin' }}</span>
                                </td>
                                <td class="p-4 px-6">
                                    <div class="flex flex-col">
                                        <span class="font-semibold text-slate-700 text-sm">{{ $item->created_at->format('d M, Y') }}</span>
                                        <span class="text-[10px] text-slate-400 font-bold tracking-wider mt-0.5">{{ $item->created_at->format('h:i A') }}</span>
                                    </div>
                                </td>
                                <td class="p-4 px-6 text-right">
                                    <form action="{{ route('admin.announcements.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this announcement? This will remove history logs on targeted user devices.');" class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-8 h-8 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-500 hover:text-rose-600 flex items-center justify-center transition-colors duration-150">
                                            <i class="bi bi-trash-fill text-xs"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        @if($announcements->hasPages())
            <div class="p-6 border-t border-slate-100">
                {{ $announcements->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
