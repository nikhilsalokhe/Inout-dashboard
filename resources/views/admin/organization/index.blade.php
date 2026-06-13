@extends('layouts.admin')

@section('title', 'Organization Management')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<style>
    /* Fix for Leaflet z-index inside sticky container */
    .leaflet-container { z-index: 10; }
</style>
@endpush

@section('content')
    <div class="max-w-7xl mx-auto space-y-8 animate-fade-in">

        {{-- Header panel --}}
        <div
            class="bg-gradient-to-r from-violet-950 to-indigo-950 text-white rounded-3xl p-8 shadow-xl relative overflow-hidden border border-violet-800">
            <div class="absolute right-0 top-0 -mt-6 -mr-6 w-72 h-72 bg-violet-500/10 rounded-full blur-3xl"></div>
            <div class="absolute left-1/2 bottom-0 -mb-16 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl"></div>

            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <span
                        class="text-xs font-bold uppercase tracking-wider text-violet-300 bg-violet-950/60 px-3 py-1 rounded-full border border-violet-900/50 inline-block mb-3">
                        <i class="bi bi-building mr-1"></i> Organization Structure
                    </span>
                    <h2 class="text-3xl font-extrabold tracking-tight mb-2">Departments, Designations & Locations</h2>
                    <p class="text-slate-300 text-sm max-w-xl">
                        Manage your organizational hierarchy. Create, edit, and manage departments, designation roles, and
                        office locations used across the system.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3 text-sm font-bold">
                    <div
                        class="px-4 py-2.5 rounded-2xl bg-white/10 border border-white/10 backdrop-blur-md flex items-center gap-2">
                        <i class="bi bi-diagram-3 text-violet-300"></i>
                        <span>{{ $departments->count() }} Depts</span>
                    </div>
                    <div
                        class="px-4 py-2.5 rounded-2xl bg-white/10 border border-white/10 backdrop-blur-md flex items-center gap-2">
                        <i class="bi bi-person-badge text-indigo-300"></i>
                        <span>{{ $positions->count() }} Designations</span>
                    </div>
                    <div
                        class="px-4 py-2.5 rounded-2xl bg-white/10 border border-white/10 backdrop-blur-md flex items-center gap-2">
                        <i class="bi bi-geo-alt text-emerald-300"></i>
                        <span>{{ $locations->count() }} Locations</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Error messages --}}
        @if(session('error'))
            <div
                class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl flex items-center gap-3 shadow-sm animate-fade-in">
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

        {{-- Tab Navigation --}}
        <div class="flex items-center gap-1 bg-slate-100 p-1.5 rounded-2xl w-fit">
            <a href="{{ route('admin.organization.index', ['tab' => 'departments']) }}"
                class="px-5 py-2.5 rounded-xl text-xs font-extrabold uppercase tracking-wider transition-all duration-300
                      {{ $tab === 'departments' ? 'bg-white text-slate-900 shadow-sm border border-slate-200' : 'text-slate-500 hover:text-slate-700 hover:bg-white/50' }}">
                <i class="bi bi-diagram-3 mr-1"></i> Departments
            </a>
            <a href="{{ route('admin.organization.index', ['tab' => 'designations']) }}"
                class="px-5 py-2.5 rounded-xl text-xs font-extrabold uppercase tracking-wider transition-all duration-300
                      {{ $tab === 'designations' ? 'bg-white text-slate-900 shadow-sm border border-slate-200' : 'text-slate-500 hover:text-slate-700 hover:bg-white/50' }}">
                <i class="bi bi-person-badge mr-1"></i> Designations
            </a>
            <a href="{{ route('admin.organization.index', ['tab' => 'locations']) }}"
                class="px-5 py-2.5 rounded-xl text-xs font-extrabold uppercase tracking-wider transition-all duration-300
                      {{ $tab === 'locations' ? 'bg-white text-slate-900 shadow-sm border border-slate-200' : 'text-slate-500 hover:text-slate-700 hover:bg-white/50' }}">
                <i class="bi bi-geo-alt mr-1"></i> Locations
            </a>
        </div>

        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- DEPARTMENTS TAB --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}
        @if($tab === 'departments')
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {{-- Left: Departments List --}}
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                            <h3 class="text-lg font-bold text-slate-800">All Departments</h3>
                            <span
                                class="px-2.5 py-1 rounded-full bg-violet-50 text-violet-700 text-xs font-bold">{{ $departments->count() }}
                                Total</span>
                        </div>
                        <div class="divide-y divide-slate-100">
                            @forelse ($departments as $dept)
                                <div class="p-5 hover:bg-slate-50/50 transition-colors flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="w-10 h-10 rounded-xl bg-violet-50 border border-violet-100 flex items-center justify-center text-violet-600 font-extrabold text-sm">
                                            {{ strtoupper(substr($dept->department_name, 0, 2)) }}
                                        </div>
                                        <div class="space-y-0.5">
                                            <span class="font-extrabold text-slate-800 text-sm">{{ $dept->department_name }}</span>
                                            <div class="flex items-center gap-3 text-xs text-slate-400 font-medium">
                                                <span><strong class="text-slate-600">{{ $dept->employees_count }}</strong>
                                                    employees</span>
                                                <span>•</span>
                                                <span
                                                    class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider
                                                        {{ $dept->status === 'active' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-slate-100 text-slate-500 border border-slate-200' }}">
                                                    {{ $dept->status }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button onclick="editDepartment({{ json_encode($dept) }})"
                                            class="px-3.5 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs transition-colors">
                                            Edit
                                        </button>
                                        <form action="{{ route('admin.organization.departments.destroy', $dept->id) }}"
                                            method="POST"
                                            onsubmit="return confirm('Delete department \'{{ $dept->department_name }}\'? This action cannot be undone.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="px-3.5 py-2 rounded-xl border border-rose-200 hover:bg-rose-50 text-rose-500 font-bold text-xs transition-colors">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <div class="p-12 text-center text-slate-400 font-semibold italic">
                                    <i class="bi bi-diagram-3 text-3xl block mb-2 text-slate-300"></i>
                                    No departments created yet. Add one on the right.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Right: Create/Edit Form --}}
                <div>
                    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 space-y-6 sticky top-24">
                        <div>
                            <h3 id="dept-form-title" class="text-base font-bold text-slate-800">Create New Department</h3>
                            <p id="dept-form-subtitle" class="text-xs text-slate-400 mt-1">Add a new organizational department.
                            </p>
                        </div>

                        <form id="dept-form" action="{{ route('admin.organization.departments.store') }}" method="POST"
                            class="space-y-4">
                            @csrf
                            <input type="hidden" id="dept-method" name="_method" value="POST">

                            <div>
                                <label
                                    class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Department
                                    Name</label>
                                <input type="text" name="department_name" id="dept_name" required placeholder="e.g. Engineering"
                                    class="w-full px-4 py-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 font-semibold text-slate-700 bg-white">
                            </div>

                            <div id="dept-status-container" class="hidden">
                                <label
                                    class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Status</label>
                                <select name="status" id="dept_status"
                                    class="w-full px-4 py-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 font-semibold text-slate-700 bg-white">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>

                            <div>
                                <label
                                    class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Attendance
                                    Method Override</label>
                                <select name="attendance_method" id="dept_attendance_method"
                                    class="w-full px-4 py-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 font-semibold text-slate-700 bg-white">
                                    <option value="">Inherit from Global</option>
                                    <option value="face">Face Recognition</option>
                                    <option value="qr">QR Code</option>
                                    <option value="face_or_qr">Face Recognition OR QR Code</option>
                                    <option value="face_and_qr">Face Recognition AND QR Code</option>
                                    <option value="manual">Manual (Button Click)</option>
                                    <option value="gps_only">GPS Only</option>
                                </select>
                            </div>

                            <div class="pt-4 flex items-center justify-end gap-2">
                                <button type="button" id="dept-cancel-btn" onclick="resetDeptForm()"
                                    class="hidden px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-500 font-bold text-xs transition-colors">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="px-5 py-2.5 rounded-xl bg-slate-900 text-white font-extrabold text-xs hover:bg-slate-800 transition-colors shadow-sm">
                                    Save Department
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- DESIGNATIONS TAB --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}
        @if($tab === 'designations')
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {{-- Left: Designations List --}}
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                            <h3 class="text-lg font-bold text-slate-800">All Designations</h3>
                            <span
                                class="px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-bold">{{ $positions->count() }}
                                Total</span>
                        </div>
                        <div class="divide-y divide-slate-100">
                            @forelse ($positions as $pos)
                                <div class="p-5 hover:bg-slate-50/50 transition-colors flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 font-extrabold text-sm">
                                            {{ strtoupper(substr($pos->position_name, 0, 2)) }}
                                        </div>
                                        <div class="space-y-0.5">
                                            <span class="font-extrabold text-slate-800 text-sm">{{ $pos->position_name }}</span>
                                            <div class="flex items-center gap-3 text-xs text-slate-400 font-medium">
                                                <span
                                                    class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 font-mono text-[10px] border">{{ $pos->department->department_name ?? 'N/A' }}</span>
                                                <span><strong class="text-slate-600">{{ $pos->employees_count }}</strong>
                                                    employees</span>
                                                <span
                                                    class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider
                                                        {{ $pos->status === 'active' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-slate-100 text-slate-500 border border-slate-200' }}">
                                                    {{ $pos->status }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button onclick="editPosition({{ json_encode($pos) }})"
                                            class="px-3.5 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs transition-colors">
                                            Edit
                                        </button>
                                        <form action="{{ route('admin.organization.positions.destroy', $pos->id) }}" method="POST"
                                            onsubmit="return confirm('Delete designation \'{{ $pos->position_name }}\'? This action cannot be undone.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="px-3.5 py-2 rounded-xl border border-rose-200 hover:bg-rose-50 text-rose-500 font-bold text-xs transition-colors">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <div class="p-12 text-center text-slate-400 font-semibold italic">
                                    <i class="bi bi-person-badge text-3xl block mb-2 text-slate-300"></i>
                                    No designations created yet. Add one on the right.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Right: Create/Edit Form --}}
                <div>
                    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 space-y-6 sticky top-24">
                        <div>
                            <h3 id="pos-form-title" class="text-base font-bold text-slate-800">Create New Designation</h3>
                            <p id="pos-form-subtitle" class="text-xs text-slate-400 mt-1">Define a job title within a
                                department.</p>
                        </div>

                        <form id="pos-form" action="{{ route('admin.organization.positions.store') }}" method="POST"
                            class="space-y-4">
                            @csrf
                            <input type="hidden" id="pos-method" name="_method" value="POST">

                            <div>
                                <label
                                    class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Designation
                                    Name</label>
                                <input type="text" name="position_name" id="pos_name" required
                                    placeholder="e.g. Senior Developer"
                                    class="w-full px-4 py-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 font-semibold text-slate-700 bg-white">
                            </div>

                            <div>
                                <label
                                    class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Department</label>
                                <select name="department_id" id="pos_department" required
                                    class="w-full px-4 py-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 font-semibold text-slate-700 bg-white">
                                    <option value="">Select Department</option>
                                    @foreach($activeDepartments as $d)
                                        <option value="{{ $d->id }}">{{ $d->department_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div id="pos-status-container" class="hidden">
                                <label
                                    class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Status</label>
                                <select name="status" id="pos_status"
                                    class="w-full px-4 py-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 font-semibold text-slate-700 bg-white">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>

                            <div class="pt-4 flex items-center justify-end gap-2">
                                <button type="button" id="pos-cancel-btn" onclick="resetPosForm()"
                                    class="hidden px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-500 font-bold text-xs transition-colors">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="px-5 py-2.5 rounded-xl bg-slate-900 text-white font-extrabold text-xs hover:bg-slate-800 transition-colors shadow-sm">
                                    Save Designation
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- LOCATIONS TAB --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}
        @if($tab === 'locations')
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {{-- Left: Locations List --}}
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                            <h3 class="text-lg font-bold text-slate-800">All Office Locations</h3>
                            <span
                                class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold">{{ $locations->count() }}
                                Total</span>
                        </div>
                        <div class="divide-y divide-slate-100">
                            @forelse ($locations as $loc)
                                <div class="p-5 hover:bg-slate-50/50 transition-colors flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 text-lg">
                                            <i class="bi bi-geo-alt-fill"></i>
                                        </div>
                                        <div class="space-y-0.5">
                                            <span class="font-extrabold text-slate-800 text-sm">{{ $loc->location_name }}</span>
                                            <div class="flex flex-wrap items-center gap-3 text-xs text-slate-400 font-medium">
                                                <span><strong class="text-slate-600">{{ $loc->employees_count }}</strong>
                                                    employees</span>
                                                @if($loc->address)
                                                    <span>•</span>
                                                    <span class="truncate max-w-[200px]" title="{{ $loc->address }}">{{ $loc->address }}</span>
                                                @endif
                                                @if($loc->latitude && $loc->longitude)
                                                    <span>•</span>
                                                    <span class="font-mono text-[10px] text-slate-500">{{ number_format($loc->latitude, 5) }},
                                                        {{ number_format($loc->longitude, 5) }}</span>
                                                @endif
                                                @if($loc->allowed_radius_meter)
                                                    <span>•</span>
                                                    <span>Radius: <strong
                                                            class="text-slate-600">{{ $loc->allowed_radius_meter }}m</strong></span>
                                                @endif
                                                <span
                                                    class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider
                                                        {{ $loc->status === 'active' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-slate-100 text-slate-500 border border-slate-200' }}">
                                                    {{ $loc->status }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.organization.locations.show-qr', $loc->id) }}"
                                            class="px-3.5 py-2 rounded-xl border border-blue-200 hover:bg-blue-50 text-blue-600 font-bold text-xs transition-colors"
                                            title="View QR">
                                            <i class="bi bi-qr-code"></i> View QR
                                        </a>
                                        <button onclick="editLocation({{ json_encode($loc) }})"
                                            class="px-3.5 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs transition-colors">
                                            Edit
                                        </button>
                                        <form action="{{ route('admin.organization.locations.destroy', $loc->id) }}" method="POST"
                                            onsubmit="return confirm('Delete location \'{{ $loc->location_name }}\'? This action cannot be undone.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="px-3.5 py-2 rounded-xl border border-rose-200 hover:bg-rose-50 text-rose-500 font-bold text-xs transition-colors">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <div class="p-12 text-center text-slate-400 font-semibold italic">
                                    <i class="bi bi-geo-alt text-3xl block mb-2 text-slate-300"></i>
                                    No locations configured yet. Add one on the right.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Right: Create/Edit Form --}}
                <div>
                    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 space-y-6 sticky top-24">
                        <div>
                            <h3 id="loc-form-title" class="text-base font-bold text-slate-800">Create New Location</h3>
                            <p id="loc-form-subtitle" class="text-xs text-slate-400 mt-1">Add an office location with geofence
                                coordinates.</p>
                        </div>

                        <form id="loc-form" action="{{ route('admin.organization.locations.store') }}" method="POST"
                            class="space-y-4">
                            @csrf
                            <input type="hidden" id="loc-method" name="_method" value="POST">

                            <div>
                                <label
                                    class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Location
                                    Name</label>
                                <input type="text" name="location_name" id="loc_name" required
                                    placeholder="e.g. Head Office, Mumbai"
                                    class="w-full px-4 py-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 font-semibold text-slate-700 bg-white">
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Office Address</label>
                                <div class="relative">
                                    <input type="text" name="address" id="loc_address" placeholder="Search address to set marker..."
                                        class="w-full px-4 py-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 font-semibold text-slate-700 bg-white pr-10">
                                    <button type="button" id="btn_search_address" class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 text-slate-400 hover:text-indigo-600 transition-colors">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                                <p class="text-[9px] text-slate-400 mt-1">Press enter or click search to locate on map.</p>
                            </div>

                            <!-- Map Container -->
                            <div class="w-full h-64 rounded-xl border border-slate-200 overflow-hidden relative" id="location-map"></div>

                            <!-- Validation Preview Box -->
                            <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 mt-2">
                                <h4 class="text-[10px] font-bold text-indigo-800 uppercase tracking-widest mb-2 flex items-center gap-1">
                                    <i class="bi bi-info-circle"></i> Location Preview
                                </h4>
                                <div class="space-y-1.5 text-xs">
                                    <div class="flex"><span class="w-20 font-semibold text-indigo-700">Address:</span> <span id="preview_address" class="text-indigo-900 font-medium truncate flex-1">--</span></div>
                                    <div class="flex"><span class="w-20 font-semibold text-indigo-700">Latitude:</span> <span id="preview_lat" class="text-indigo-900 font-mono flex-1">--</span></div>
                                    <div class="flex"><span class="w-20 font-semibold text-indigo-700">Longitude:</span> <span id="preview_lng" class="text-indigo-900 font-mono flex-1">--</span></div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Latitude</label>
                                    <input type="number" step="any" name="latitude" id="loc_lat" placeholder="e.g. 19.07609"
                                        class="w-full px-4 py-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 font-semibold text-slate-700 bg-white">
                                </div>
                                <div>
                                    <label
                                        class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Longitude</label>
                                    <input type="number" step="any" name="longitude" id="loc_lng" placeholder="e.g. 72.87744"
                                        class="w-full px-4 py-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 font-semibold text-slate-700 bg-white">
                                </div>
                            </div>

                            <div>
                                <label
                                    class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Allowed
                                    Radius (meters)</label>
                                <input type="number" name="allowed_radius_meter" id="loc_radius" value="200" min="0"
                                    placeholder="200"
                                    class="w-full px-4 py-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 font-semibold text-slate-700 bg-white">
                            </div>

                            <div id="loc-status-container" class="hidden">
                                <label
                                    class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Status</label>
                                <select name="status" id="loc_status"
                                    class="w-full px-4 py-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 font-semibold text-slate-700 bg-white">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>

                            <div class="pt-4 flex items-center justify-end gap-2">
                                <button type="button" id="loc-cancel-btn" onclick="resetLocForm()"
                                    class="hidden px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-500 font-bold text-xs transition-colors">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="px-5 py-2.5 rounded-xl bg-slate-900 text-white font-extrabold text-xs hover:bg-slate-800 transition-colors shadow-sm">
                                    Save Location
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <script>
        // Route templates for dynamic URL resolution
        const deptUpdateRoute = "{{ route('admin.organization.departments.update', ['id' => ':id']) }}";
        const posUpdateRoute = "{{ route('admin.organization.positions.update', ['id' => ':id']) }}";
        const locUpdateRoute = "{{ route('admin.organization.locations.update', ['id' => ':id']) }}";

        // ─── DEPARTMENT FORM ─────────────────────────────────────
        function editDepartment(dept) {
            document.getElementById('dept-form-title').innerText = 'Modify: ' + dept.department_name;
            document.getElementById('dept-form-subtitle').innerText = 'Update department details.';
            document.getElementById('dept_name').value = dept.department_name;
            document.getElementById('dept_status').value = dept.status;
            document.getElementById('dept_attendance_method').value = dept.attendance_method || '';

            const form = document.getElementById('dept-form');
            form.action = deptUpdateRoute.replace(':id', dept.id);
            document.getElementById('dept-method').value = 'POST';

            document.getElementById('dept-status-container').classList.remove('hidden');
            document.getElementById('dept-cancel-btn').classList.remove('hidden');
        }

        function resetDeptForm() {
            document.getElementById('dept-form-title').innerText = 'Create New Department';
            document.getElementById('dept-form-subtitle').innerText = 'Add a new organizational department.';
            document.getElementById('dept-form').reset();
            document.getElementById('dept-form').action = "{{ route('admin.organization.departments.store') }}";
            document.getElementById('dept-method').value = 'POST';
            document.getElementById('dept-status-container').classList.add('hidden');
            document.getElementById('dept-cancel-btn').classList.add('hidden');
            document.getElementById('dept_attendance_method').value = '';
        }

        // ─── POSITION/DESIGNATION FORM ───────────────────────────
        function editPosition(pos) {
            document.getElementById('pos-form-title').innerText = 'Modify: ' + pos.position_name;
            document.getElementById('pos-form-subtitle').innerText = 'Update designation details.';
            document.getElementById('pos_name').value = pos.position_name;
            document.getElementById('pos_department').value = pos.department_id;
            document.getElementById('pos_status').value = pos.status;

            const form = document.getElementById('pos-form');
            form.action = posUpdateRoute.replace(':id', pos.id);
            document.getElementById('pos-method').value = 'POST';

            document.getElementById('pos-status-container').classList.remove('hidden');
            document.getElementById('pos-cancel-btn').classList.remove('hidden');
        }

        function resetPosForm() {
            document.getElementById('pos-form-title').innerText = 'Create New Designation';
            document.getElementById('pos-form-subtitle').innerText = 'Define a job title within a department.';
            document.getElementById('pos-form').reset();
            document.getElementById('pos-form').action = "{{ route('admin.organization.positions.store') }}";
            document.getElementById('pos-method').value = 'POST';
            document.getElementById('pos-status-container').classList.add('hidden');
            document.getElementById('pos-cancel-btn').classList.add('hidden');
        }

        // ─── LOCATION FORM ───────────────────────────────────────
        function editLocation(loc) {
            document.getElementById('loc-form-title').innerText = 'Modify: ' + loc.location_name;
            document.getElementById('loc-form-subtitle').innerText = 'Update location details.';
            document.getElementById('loc_name').value = loc.location_name;
            document.getElementById('loc_address').value = loc.address || '';
            document.getElementById('loc_lat').value = loc.latitude || '';
            document.getElementById('loc_lng').value = loc.longitude || '';
            document.getElementById('loc_radius').value = loc.allowed_radius_meter || 200;
            document.getElementById('loc_status').value = loc.status;

            const form = document.getElementById('loc-form');
            form.action = locUpdateRoute.replace(':id', loc.id);
            document.getElementById('loc-method').value = 'POST';

            document.getElementById('loc-status-container').classList.remove('hidden');
            document.getElementById('loc-cancel-btn').classList.remove('hidden');
            
            if (typeof updateMapFromInputs === 'function') updateMapFromInputs();
            if (typeof updatePreview === 'function') updatePreview();
        }

        function resetLocForm() {
            document.getElementById('loc-form-title').innerText = 'Create New Location';
            document.getElementById('loc-form-subtitle').innerText = 'Add an office location with geofence coordinates.';
            document.getElementById('loc-form').reset();
            document.getElementById('loc-form').action = "{{ route('admin.organization.locations.store') }}";
            document.getElementById('loc-method').value = 'POST';
            document.getElementById('loc-status-container').classList.add('hidden');
            document.getElementById('loc-cancel-btn').classList.add('hidden');
            
            if (typeof updatePreview === 'function') updatePreview();
        }
    </script>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    // Leaflet Map Logic
    let map = null;
    let marker = null;

    function initMap() {
        if(map) return;
        
        map = L.map('location-map').setView([20.5937, 78.9629], 5); // Default to India
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        delete L.Icon.Default.prototype._getIconUrl;
        L.Icon.Default.mergeOptions({
            iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
            iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
        });

        marker = L.marker([20.5937, 78.9629], { draggable: true }).addTo(map);

        marker.on('dragend', function(event) {
            const position = marker.getLatLng();
            updateLatLngFields(position.lat, position.lng);
            reverseGeocode(position.lat, position.lng);
        });

        map.on('click', function(e) {
            marker.setLatLng(e.latlng);
            updateLatLngFields(e.latlng.lat, e.latlng.lng);
            reverseGeocode(e.latlng.lat, e.latlng.lng);
        });

        document.getElementById('btn_search_address').addEventListener('click', doAddressSearch);
        document.getElementById('loc_address').addEventListener('keypress', function(e) {
            if(e.key === 'Enter') {
                e.preventDefault();
                doAddressSearch();
            }
        });
        
        document.getElementById('loc_lat').addEventListener('change', updateMapFromInputs);
        document.getElementById('loc_lng').addEventListener('change', updateMapFromInputs);
        document.getElementById('loc_address').addEventListener('input', updatePreview);
    }

    function updateLatLngFields(lat, lng) {
        document.getElementById('loc_lat').value = lat.toFixed(6);
        document.getElementById('loc_lng').value = lng.toFixed(6);
        updatePreview();
    }

    function updateMapFromInputs() {
        const lat = parseFloat(document.getElementById('loc_lat').value);
        const lng = parseFloat(document.getElementById('loc_lng').value);
        if(!isNaN(lat) && !isNaN(lng)) {
            const latlng = new L.LatLng(lat, lng);
            marker.setLatLng(latlng);
            map.setView(latlng, 15);
            updatePreview();
        }
    }

    function doAddressSearch() {
        const address = document.getElementById('loc_address').value;
        if(!address) return;

        const btn = document.getElementById('btn_search_address');
        const oldIcon = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-hourglass-split animate-spin"></i>';

        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}`)
            .then(res => res.json())
            .then(data => {
                btn.innerHTML = oldIcon;
                if(data && data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lng = parseFloat(data[0].lon);
                    const latlng = new L.LatLng(lat, lng);
                    
                    marker.setLatLng(latlng);
                    map.setView(latlng, 15);
                    
                    document.getElementById('loc_address').value = data[0].display_name;
                    updateLatLngFields(lat, lng);
                } else {
                    alert('Address not found!');
                }
            })
            .catch(err => {
                btn.innerHTML = oldIcon;
                console.error('Geocoding error:', err);
            });
    }

    function reverseGeocode(lat, lng) {
        document.getElementById('loc_address').placeholder = "Fetching address...";
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
            .then(res => res.json())
            .then(data => {
                if(data && data.display_name) {
                    document.getElementById('loc_address').value = data.display_name;
                    updatePreview();
                }
                document.getElementById('loc_address').placeholder = "Search address to set marker...";
            })
            .catch(err => console.error('Reverse Geocoding error:', err));
    }

    function updatePreview() {
        document.getElementById('preview_address').innerText = document.getElementById('loc_address').value || '--';
        document.getElementById('preview_lat').innerText = document.getElementById('loc_lat').value || '--';
        document.getElementById('preview_lng').innerText = document.getElementById('loc_lng').value || '--';
    }

    document.addEventListener('DOMContentLoaded', function() {
        if('{{ $tab }}' === 'locations') {
            setTimeout(() => {
                initMap();
                updateMapFromInputs();
            }, 300);
        }
    });
</script>
@endpush