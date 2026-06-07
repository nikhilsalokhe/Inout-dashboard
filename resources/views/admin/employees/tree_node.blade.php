<!-- Recursive tree_node template -->
<div class="relative pl-6 select-none my-3">
    <!-- Tree Connector Lines (if depth > 0) -->
    <div class="tree-node-wrapper {{ $depth > 0 ? 'tree-node-wrapper-has-parent' : '' }} {{ isset($isLast) && $isLast ? 'tree-node-wrapper-last' : '' }} relative">
        
        <!-- Node card structure -->
        <div class="flex items-center gap-3">
            
            <!-- Expand/Collapse Chevron (only if children exist) -->
            @if(count($node['children']) > 0)
                <button onclick="toggleNode({{ $node['id'] }})" class="w-6 h-6 rounded-lg bg-slate-50 hover:bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-500 hover:text-indigo-600 transition-colors shadow-sm focus:outline-none">
                    <i class="bi bi-chevron-down text-[10px] font-bold chevron-icon" id="icon-{{ $node['id'] }}"></i>
                </button>
            @else
                <div class="w-6 h-6 flex items-center justify-center text-slate-300">
                    <i class="bi bi-dot text-base leading-none"></i>
                </div>
            @endif

            <!-- Node Card Container -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 min-w-[280px] sm:min-w-[400px] max-w-xl bg-white rounded-2xl border border-slate-200/60 shadow-sm shadow-slate-100 hover:shadow-md hover:border-indigo-150 transition-all duration-300 group relative overflow-hidden">
                <!-- Color Bar based on hierarchy tier -->
                <div class="absolute left-0 top-0 bottom-0 w-1.5 {{ count($node['children']) > 0 ? ($depth == 0 ? 'bg-indigo-500' : 'bg-emerald-500') : 'bg-slate-300' }}"></div>

                <!-- Profile Info -->
                <div class="flex items-center gap-3 pl-1.5">
                    <!-- Avatar bubble -->
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-xs shadow-inner {{ count($node['children']) > 0 ? ($depth == 0 ? 'bg-indigo-50 text-indigo-600 border border-indigo-100' : 'bg-emerald-50 text-emerald-600 border border-emerald-100') : 'bg-slate-50 text-slate-500 border border-slate-200/50' }}">
                        @if(count($node['children']) > 0)
                            <i class="bi {{ $depth == 0 ? 'bi-folder2-open' : 'bi-folder2-open' }} folder-icon text-sm" id="folder-{{ $node['id'] }}"></i>
                        @else
                            {{ $node['avatar'] }}
                        @endif
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <h4 class="font-extrabold text-slate-800 text-xs leading-none">{{ $node['name'] }}</h4>
                            <span class="text-[9px] font-bold bg-slate-50 text-slate-400 border border-slate-100 px-1.5 py-0.5 rounded-md uppercase tracking-wider">{{ $node['code'] }}</span>
                        </div>
                        <p class="text-[10px] text-slate-400 font-semibold mt-1">{{ $node['position'] }}</p>
                    </div>
                </div>

                <!-- Placement Tags -->
                <div class="flex flex-wrap items-center gap-1.5 pl-4 sm:pl-0">
                    <span class="px-2 py-0.5 bg-slate-50 text-slate-500 text-[9px] font-bold rounded-md border border-slate-100/60 uppercase tracking-wide">
                        {{ $node['department'] }}
                    </span>
                    <span class="px-2 py-0.5 bg-slate-50 text-slate-400 text-[9px] font-semibold rounded-md border border-slate-100/60">
                        {{ $node['location'] }}
                    </span>
                </div>

                <!-- Hover Quick Admin Edit Link -->
                <div class="absolute right-3 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                    <a href="{{ route('admin.employees.edit', $node['id']) }}" class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100 hover:bg-indigo-100/80 transition-colors shadow-sm">
                        <i class="bi bi-pencil-square text-xs"></i>
                    </a>
                </div>
            </div>

        </div>

        <!-- Render Children nodes recursively -->
        @if(count($node['children']) > 0)
            <div class="node-children pl-8 pt-1" id="children-{{ $node['id'] }}">
                @php
                    $childCount = count($node['children']);
                @endphp
                @foreach($node['children'] as $index => $child)
                    @include('admin.employees.tree_node', [
                        'node' => $child, 
                        'depth' => $depth + 1,
                        'isLast' => ($index === $childCount - 1)
                    ])
                @endforeach
            </div>
        @endif

    </div>
</div>
