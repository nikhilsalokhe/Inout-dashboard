@extends('layouts.admin')

@section('title', 'Corporate Org Hierarchy')

@section('content')
<div class="mb-8 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
    <div>
        <p class="text-slate-500 text-sm font-medium">Visualize employee reporting structures, reporting chains, and corporate tiers in an interactive hierarchy.</p>
    </div>
    
    <!-- Premium Filter Control Panel -->
    <form action="{{ route('admin.org-tree') }}" method="GET" class="flex flex-wrap items-center gap-3">
        <!-- Search -->
        <div class="relative w-64">
            <input type="text" name="search" value="{{ request('search') }}" 
                placeholder="Search staff, code, or email..." 
                class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all duration-300 text-xs font-semibold placeholder:text-slate-300">
            <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
        </div>

        <!-- Department -->
        <select name="department_id" onchange="this.form.submit()" 
            class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 bg-white outline-none focus:border-indigo-500 transition-all cursor-pointer">
            <option value="">All Departments</option>
            @foreach($departments as $dept)
                <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                    {{ $dept->department_name }}
                </option>
            @endforeach
        </select>

        <!-- Location -->
        <select name="location_id" onchange="this.form.submit()" 
            class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 bg-white outline-none focus:border-indigo-500 transition-all cursor-pointer">
            <option value="">All Locations</option>
            @foreach($locations as $loc)
                <option value="{{ $loc->id }}" {{ request('location_id') == $loc->id ? 'selected' : '' }}>
                    {{ $loc->location_name }}
                </option>
            @endforeach
        </select>

        @if(request()->anyFilled(['search', 'department_id', 'location_id']))
            <a href="{{ route('admin.org-tree') }}" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-700 text-xs font-extrabold transition-colors">
                Reset
            </a>
        @endif
    </form>
</div>

<!-- Hierarchy Visualization Panel -->
<div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-8 min-h-[500px]">
    
    @if(count($tree) > 0)
        <!-- Collapsible Controls -->
        <div class="flex items-center gap-3 mb-8 pb-4 border-b border-slate-100">
            <button onclick="toggleAllNodes(true)" class="px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100/80 text-indigo-600 rounded-xl text-xs font-bold transition-colors flex items-center gap-1.5 shadow-sm shadow-indigo-500/5">
                <i class="bi bi-folder-plus text-sm"></i>
                <span>Expand All</span>
            </button>
            <button onclick="toggleAllNodes(false)" class="px-3.5 py-2 bg-slate-50 hover:bg-slate-100 text-slate-500 rounded-xl text-xs font-bold transition-colors flex items-center gap-1.5 border border-slate-200/40">
                <i class="bi bi-folder-minus text-sm"></i>
                <span>Collapse All</span>
            </button>
            <div class="h-4 w-px bg-slate-200 mx-1"></div>
            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Expand managers to trace branches</span>
        </div>

        <!-- Render the Roots -->
        <div class="org-tree-container relative pl-4">
            @foreach($tree as $node)
                @include('admin.employees.tree_node', ['node' => $node, 'depth' => 0])
            @endforeach
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-20 text-center text-slate-400 max-w-md mx-auto">
            <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-300 text-2xl border border-slate-100 mb-4">
                <i class="bi bi-diagram-3-fill"></i>
            </div>
            <h5 class="font-bold text-slate-800 text-base mb-1">No Hierarchy Branches Found</h5>
            <p class="text-xs text-slate-400 leading-relaxed font-semibold">No reporting channels were discovered matching your current filter specifications. Try adjusting your search query or dropdown criteria.</p>
        </div>
    @endif

</div>

<!-- Stylesheet & Scripting for tree line aesthetics and toggle logic -->
<style>
    .tree-node-wrapper::before {
        content: '';
        position: absolute;
        left: -16px;
        top: 24px;
        width: 16px;
        height: 1.5px;
        background-color: #cbd5e1; /* slate-300 line */
    }
    .tree-node-wrapper-has-parent::after {
        content: '';
        position: absolute;
        left: -16px;
        top: -12px;
        width: 1.5px;
        height: calc(100% + 24px);
        background-color: #cbd5e1; /* slate-300 line */
    }
    .tree-node-wrapper-last::after {
        height: 36px !important;
    }
</style>

<script>
    function toggleNode(nodeId) {
        const childrenDiv = document.getElementById('children-' + nodeId);
        const icon = document.getElementById('icon-' + nodeId);
        const folderIcon = document.getElementById('folder-' + nodeId);
        
        if (childrenDiv.classList.contains('hidden')) {
            childrenDiv.classList.remove('hidden');
            icon.classList.remove('bi-chevron-right');
            icon.classList.add('bi-chevron-down');
            folderIcon.classList.remove('bi-folder-fill');
            folderIcon.classList.add('bi-folder2-open');
        } else {
            childrenDiv.classAdd = childrenDiv.classList.add('hidden');
            icon.classList.remove('bi-chevron-down');
            icon.classList.add('bi-chevron-right');
            folderIcon.classList.remove('bi-folder2-open');
            folderIcon.classList.add('bi-folder-fill');
        }
    }

    function toggleAllNodes(expand) {
        const allChildren = document.querySelectorAll('.node-children');
        const allChevrons = document.querySelectorAll('.chevron-icon');
        const allFolders = document.querySelectorAll('.folder-icon');

        allChildren.forEach(div => {
            if (expand) {
                div.classList.remove('hidden');
            } else {
                div.classList.add('hidden');
            }
        });

        allChevrons.forEach(icon => {
            if (expand) {
                icon.classList.remove('bi-chevron-right');
                icon.classList.add('bi-chevron-down');
            } else {
                icon.classList.remove('bi-chevron-down');
                icon.classList.add('bi-chevron-right');
            }
        });

        allFolders.forEach(folder => {
            if (expand) {
                folder.classList.remove('bi-folder-fill');
                folder.classList.add('bi-folder2-open');
            } else {
                folder.classList.remove('bi-folder2-open');
                folder.classList.add('bi-folder-fill');
            }
        });
    }
</script>
@endsection
