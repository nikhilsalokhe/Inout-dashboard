@extends('layouts.admin')

@section('title', 'Publish Announcement')

@section('content')
<div class="mb-8 animate-fade-in">
    <a href="{{ route('admin.announcements.index') }}" class="inline-flex items-center gap-2 text-slate-500 hover:text-indigo-600 font-semibold text-sm transition-colors mb-2">
        <i class="bi bi-arrow-left"></i>
        <span>Back to Announcements</span>
    </a>
    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Draft Broadcast</h1>
    <p class="text-slate-500 text-sm font-medium">Compose an announcement to be published on the employee dashboard and pushed directly to targeted mobile devices.</p>
</div>

<div class="max-w-3xl">
    <form action="{{ route('admin.announcements.store') }}" method="POST" class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden animate-fade-in">
        @csrf
        
        <div class="p-8 space-y-6">
            @if ($errors->any())
                <div class="p-4 bg-rose-50 border border-rose-100 text-rose-800 rounded-2xl flex flex-col gap-1.5 shadow-sm">
                    <span class="font-bold text-xs uppercase tracking-wider text-rose-600">Verification Failure</span>
                    <ul class="list-disc pl-5 text-xs font-semibold space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Title -->
            <div>
                <label for="title" class="block text-slate-700 font-extrabold text-xs uppercase tracking-wider mb-2">Announcement Title</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400">
                        <i class="bi bi-chat-left-text"></i>
                    </span>
                    <input type="text" name="title" id="title" value="{{ old('title') }}" required
                        placeholder="e.g. Town Hall Schedule, Office Holiday Alert" 
                        class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 focus:border-indigo-500 focus:bg-white rounded-2xl text-sm font-semibold transition-all outline-none text-slate-800">
                </div>
            </div>

            <!-- Content -->
            <div>
                <label for="content" class="block text-slate-700 font-extrabold text-xs uppercase tracking-wider mb-2">Message Content</label>
                <textarea name="content" id="content" rows="6" required
                    placeholder="Write the details of the announcement here..."
                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 focus:border-indigo-500 focus:bg-white rounded-2xl text-sm font-semibold transition-all outline-none text-slate-800 resize-none">{{ old('content') }}</textarea>
            </div>

            <!-- Target Scope Selection -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="target_type" class="block text-slate-700 font-extrabold text-xs uppercase tracking-wider mb-2">Audience Target</label>
                    <select name="target_type" id="target_type" required onchange="toggleTargets(this.value)"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 focus:border-indigo-500 focus:bg-white rounded-2xl text-sm font-semibold transition-all outline-none text-slate-800">
                        <option value="all" {{ old('target_type') == 'all' ? 'selected' : '' }}>All Employees</option>
                        <option value="department" {{ old('target_type') == 'department' ? 'selected' : '' }}>By Department</option>
                        <option value="location" {{ old('target_type') == 'location' ? 'selected' : '' }}>By Location</option>
                    </select>
                </div>

                <!-- Target Department Select (Conditional) -->
                <div id="target_department_wrapper" class="hidden">
                    <label for="target_department_id" class="block text-slate-700 font-extrabold text-xs uppercase tracking-wider mb-2">Select Department</label>
                    <select name="target_department_id" id="target_department_id"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 focus:border-indigo-500 focus:bg-white rounded-2xl text-sm font-semibold transition-all outline-none text-slate-800">
                        <option value="">Choose Department...</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('target_department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->department_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Target Location Select (Conditional) -->
                <div id="target_location_wrapper" class="hidden">
                    <label for="target_location_id" class="block text-slate-700 font-extrabold text-xs uppercase tracking-wider mb-2">Select Location</label>
                    <select name="target_location_id" id="target_location_id"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 focus:border-indigo-500 focus:bg-white rounded-2xl text-sm font-semibold transition-all outline-none text-slate-800">
                        <option value="">Choose Location...</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" {{ old('target_location_id') == $loc->id ? 'selected' : '' }}>{{ $loc->location_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="px-8 py-5 bg-slate-50/70 border-t border-slate-150 flex items-center justify-end gap-3.5">
            <a href="{{ route('admin.announcements.index') }}" class="px-5 py-3 bg-white hover:bg-slate-100 border border-slate-200 text-slate-500 hover:text-slate-700 font-extrabold rounded-2xl text-xs transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-extrabold rounded-2xl text-xs shadow-md shadow-indigo-500/10 hover:shadow-indigo-500/20 transition-all hover:-translate-y-0.5 active:translate-y-0 flex items-center gap-2">
                <i class="bi bi-send-fill text-2xs"></i>
                <span>Publish Announcement</span>
            </button>
        </div>
    </form>
</div>

<script>
    function toggleTargets(targetType) {
        const deptWrapper = document.getElementById('target_department_wrapper');
        const locWrapper = document.getElementById('target_location_wrapper');
        const deptSelect = document.getElementById('target_department_id');
        const locSelect = document.getElementById('target_location_id');

        // Reset
        deptWrapper.classList.add('hidden');
        locWrapper.classList.add('hidden');
        deptSelect.required = false;
        locSelect.required = false;

        if (targetType === 'department') {
            deptWrapper.classList.remove('hidden');
            deptSelect.required = true;
            deptSelect.value = '';
        } else if (targetType === 'location') {
            locWrapper.classList.remove('hidden');
            locSelect.required = true;
            locSelect.value = '';
        }
    }

    // Run on initial load
    document.addEventListener('DOMContentLoaded', function() {
        toggleTargets(document.getElementById('target_type').value);
    });
</script>
@endsection
