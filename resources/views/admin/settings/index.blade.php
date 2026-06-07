@extends('layouts.admin')

@section('title', 'System Settings & Control Panel')

@section('content')
<div class="max-w-6xl mx-auto space-y-8 animate-fade-in">
    <!-- Header Summary Card -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-purple-950 text-white rounded-3xl p-8 shadow-xl relative overflow-hidden border border-slate-800">
        <div class="absolute right-0 top-0 -mt-6 -mr-6 w-72 h-72 bg-indigo-500/10 rounded-full blur-3xl"></div>
        <div class="absolute left-1/3 bottom-0 -mb-10 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-400 bg-indigo-950/60 px-3 py-1 rounded-full border border-indigo-900/50 inline-block mb-3">
                    System Core Configuration
                </span>
                <h2 class="text-3xl font-extrabold tracking-tight mb-2">InOut Control Center</h2>
                <p class="text-slate-300 text-sm max-w-xl">
                    Configure global policies, biometrics thresholds, security settings, and geolocation boundaries. All changes are logged to the audit trail for compliance.
                </p>
            </div>
            <div>
                <a href="{{ route('admin.audit-logs.index') }}" class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-white/10 hover:bg-white/15 text-white font-bold text-sm transition-all duration-300 border border-white/10 shadow-lg backdrop-blur-md">
                    <i class="bi bi-clock-history"></i>
                    <span>View Audit Trail</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Settings Form -->
    <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-8">
        @csrf

        <!-- Tabs Container -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            
            <!-- Sidebar Tab Navigation -->
            <div class="space-y-2">
                <button type="button" onclick="switchTab('attendance')" id="tab-btn-attendance" class="tab-btn w-full text-left flex items-center gap-3 px-5 py-4 rounded-2xl font-bold text-sm transition-all duration-300 bg-white border border-slate-200 text-slate-700 shadow-sm hover:bg-slate-50">
                    <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-600">
                        <i class="bi bi-calendar-check-fill"></i>
                    </div>
                    <span>Attendance Policies</span>
                </button>
                
                <button type="button" onclick="switchTab('face')" id="tab-btn-face" class="tab-btn w-full text-left flex items-center gap-3 px-5 py-4 rounded-2xl font-bold text-sm transition-all duration-300 bg-white border border-slate-200 text-slate-700 shadow-sm hover:bg-slate-50">
                    <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center text-purple-600">
                        <i class="bi bi-person-bounding-box"></i>
                    </div>
                    <span>Face Recognition</span>
                </button>
                
                <button type="button" onclick="switchTab('geo')" id="tab-btn-geo" class="tab-btn w-full text-left flex items-center gap-3 px-5 py-4 rounded-2xl font-bold text-sm transition-all duration-300 bg-white border border-slate-200 text-slate-700 shadow-sm hover:bg-slate-50">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-600">
                        <i class="bi bi-geo-alt-fill"></i>
                    </div>
                    <span>Geo Restriction</span>
                </button>
                
                <button type="button" onclick="switchTab('security')" id="tab-btn-security" class="tab-btn w-full text-left flex items-center gap-3 px-5 py-4 rounded-2xl font-bold text-sm transition-all duration-300 bg-white border border-slate-200 text-slate-700 shadow-sm hover:bg-slate-50">
                    <div class="w-8 h-8 rounded-lg bg-rose-100 flex items-center justify-center text-rose-600">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                    <span>Security & Bindings</span>
                </button>
            </div>

            <!-- Tab Contents Area -->
            <div class="lg:col-span-3 bg-white rounded-3xl border border-slate-200 shadow-sm p-8 min-h-[450px] flex flex-col">
                
                <!-- Attendance Tab -->
                <div id="tab-content-attendance" class="tab-content space-y-6 hidden">
                    <h3 class="text-xl font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center gap-2">
                        <i class="bi bi-calendar-check text-indigo-500"></i> Attendance Rules
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                        <!-- checkout_mandatory -->
                        <div class="bg-slate-50/50 p-5 rounded-2xl border border-slate-100 flex flex-col justify-between">
                            <div>
                                <h4 class="font-bold text-sm text-slate-800 mb-1">Mandatory Checkout</h4>
                                <p class="text-xs text-slate-500 leading-relaxed mb-4">Require employees to register checkout at the end of their shift, rather than relying on automatic transitions.</p>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Status</span>
                                <input type="hidden" name="settings[checkout_mandatory]" value="0">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="settings[checkout_mandatory]" value="1" class="sr-only peer" {{ ($settings->get('checkout_mandatory', '1')) == '1' ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>
                        </div>

                        <!-- prevent_multiple_checkin -->
                        <div class="bg-slate-50/50 p-5 rounded-2xl border border-slate-100 flex flex-col justify-between">
                            <div>
                                <h4 class="font-bold text-sm text-slate-800 mb-1">Prevent Multiple Checkins</h4>
                                <p class="text-xs text-slate-500 leading-relaxed mb-4">Block employees from performing check-in multiple times within the same day once a record is already registered.</p>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Status</span>
                                <input type="hidden" name="settings[prevent_multiple_checkin]" value="0">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="settings[prevent_multiple_checkin]" value="1" class="sr-only peer" {{ ($settings->get('prevent_multiple_checkin', '1')) == '1' ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>
                        </div>

                        <!-- auto_checkout_enabled -->
                        <div class="bg-slate-50/50 p-5 rounded-2xl border border-slate-100 flex flex-col justify-between">
                            <div>
                                <h4 class="font-bold text-sm text-slate-800 mb-1">Auto Checkout Command</h4>
                                <p class="text-xs text-slate-500 leading-relaxed mb-4">Automatically perform checkout for employees at the end of the day if they forgot to register exit.</p>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Status</span>
                                <input type="hidden" name="settings[auto_checkout_enabled]" value="0">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="settings[auto_checkout_enabled]" id="auto-checkout-toggle" value="1" class="sr-only peer" {{ ($settings->get('auto_checkout_enabled', '0')) == '1' ? 'checked' : '' }} onchange="toggleAutoCheckoutTime()">
                                    <div class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>
                        </div>

                        <!-- auto_checkout_time -->
                        <div id="auto-checkout-time-container" class="bg-slate-50/50 p-5 rounded-2xl border border-slate-100 flex flex-col justify-between">
                            <div>
                                <h4 class="font-bold text-sm text-slate-800 mb-1">Auto Checkout Time</h4>
                                <p class="text-xs text-slate-500 leading-relaxed mb-4">The time of day when auto-checkout commands should evaluate and register checkout logs.</p>
                            </div>
                            <div class="mt-2">
                                <input type="text" name="settings[auto_checkout_time]" value="{{ $settings->get('auto_checkout_time', '18:00:00') }}" placeholder="18:00:00" class="w-full px-4 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 font-semibold text-slate-700 bg-white">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Face Recognition Tab -->
                <div id="tab-content-face" class="tab-content space-y-6 hidden">
                    <h3 class="text-xl font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center gap-2">
                        <i class="bi bi-person-bounding-box text-purple-500"></i> Face Recognition & Biometrics
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                        <!-- face_recognition_enabled -->
                        <div class="bg-slate-50/50 p-5 rounded-2xl border border-slate-100 flex flex-col justify-between">
                            <div>
                                <h4 class="font-bold text-sm text-slate-800 mb-1">Enable Face Verification</h4>
                                <p class="text-xs text-slate-500 leading-relaxed mb-4">If active, employees must pass face validation matches on their mobile devices during clock-ins/outs.</p>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Status</span>
                                <input type="hidden" name="settings[face_recognition_enabled]" value="0">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="settings[face_recognition_enabled]" value="1" class="sr-only peer" {{ ($settings->get('face_recognition_enabled', '1')) == '1' ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                                </label>
                            </div>
                        </div>

                        <!-- live_face_detection_enabled -->
                        <div class="bg-slate-50/50 p-5 rounded-2xl border border-slate-100 flex flex-col justify-between">
                            <div>
                                <h4 class="font-bold text-sm text-slate-800 mb-1">Liveness Checks (Anti-Spoofing)</h4>
                                <p class="text-xs text-slate-500 leading-relaxed mb-4">Require standard liveness / blink checks to prevent photos or printouts from simulating physical checkins.</p>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Status</span>
                                <input type="hidden" name="settings[live_face_detection_enabled]" value="0">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="settings[live_face_detection_enabled]" value="1" class="sr-only peer" {{ ($settings->get('live_face_detection_enabled', '1')) == '1' ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                                </label>
                            </div>
                        </div>

                        <!-- face_match_threshold -->
                        <div class="bg-slate-50/50 p-5 rounded-2xl border border-slate-100 flex flex-col justify-between">
                            <div>
                                <h4 class="font-bold text-sm text-slate-800 mb-1">Recognition Match Threshold</h4>
                                <p class="text-xs text-slate-500 leading-relaxed mb-4">Minimum percentage match required to approve recognition. Higher implies strict accuracy requirements.</p>
                            </div>
                            <div class="mt-2 flex items-center gap-4">
                                <input type="range" name="settings[face_match_threshold]" min="50" max="100" value="{{ $settings->get('face_match_threshold', '85') }}" class="w-full accent-purple-600 cursor-pointer" oninput="document.getElementById('threshold-val').innerText = this.value + '%'">
                                <span id="threshold-val" class="text-sm font-extrabold text-purple-600 bg-purple-50 px-3 py-1 rounded-lg min-w-[50px] text-center border border-purple-100">
                                    {{ $settings->get('face_match_threshold', '85') }}%
                                </span>
                            </div>
                        </div>

                        <!-- reject_multiple_faces -->
                        <div class="bg-slate-50/50 p-5 rounded-2xl border border-slate-100 flex flex-col justify-between">
                            <div>
                                <h4 class="font-bold text-sm text-slate-800 mb-1">Reject Multiple Faces</h4>
                                <p class="text-xs text-slate-500 leading-relaxed mb-4">Block transactions if more than one physical face is captured in the camera view to avoid buddy check-ins.</p>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Status</span>
                                <input type="hidden" name="settings[reject_multiple_faces]" value="0">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="settings[reject_multiple_faces]" value="1" class="sr-only peer" {{ ($settings->get('reject_multiple_faces', '1')) == '1' ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                                </label>
                            </div>
                        </div>

                        <!-- require_admin_approval_face_reset -->
                        <div class="bg-slate-50/50 p-5 rounded-2xl border border-slate-100 flex flex-col justify-between">
                            <div>
                                <h4 class="font-bold text-sm text-slate-800 mb-1">Admin Approval for resets</h4>
                                <p class="text-xs text-slate-500 leading-relaxed mb-4">Require manual Admin/HR review and approval whenever an employee requests face profile reset.</p>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Status</span>
                                <input type="hidden" name="settings[require_admin_approval_face_reset]" value="0">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="settings[require_admin_approval_face_reset]" value="1" class="sr-only peer" {{ ($settings->get('require_admin_approval_face_reset', '1')) == '1' ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Geo Location Tab -->
                <div id="tab-content-geo" class="tab-content space-y-6 hidden">
                    <h3 class="text-xl font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center gap-2">
                        <i class="bi bi-geo-alt-fill text-emerald-500"></i> Geofencing & Location Rules
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                        <!-- geo_restriction_enabled -->
                        <div class="bg-slate-50/50 p-5 rounded-2xl border border-slate-100 flex flex-col justify-between">
                            <div>
                                <h4 class="font-bold text-sm text-slate-800 mb-1">Enforce Geofence Boundaries</h4>
                                <p class="text-xs text-slate-500 leading-relaxed mb-4">Restrict clock-in/out commands to office coordinates. Block entries registered outside allowed radius.</p>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Status</span>
                                <input type="hidden" name="settings[geo_restriction_enabled]" value="0">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="settings[geo_restriction_enabled]" value="1" class="sr-only peer" {{ ($settings->get('geo_restriction_enabled', '1')) == '1' ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                                </label>
                            </div>
                        </div>

                        <!-- capture_selfie_enabled -->
                        <div class="bg-slate-50/50 p-5 rounded-2xl border border-slate-100 flex flex-col justify-between">
                            <div>
                                <h4 class="font-bold text-sm text-slate-800 mb-1">Require Verification Selfie</h4>
                                <p class="text-xs text-slate-500 leading-relaxed mb-4">Require employees to capture a live selfie image check alongside GPS coordinates.</p>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Status</span>
                                <input type="hidden" name="settings[capture_selfie_enabled]" value="0">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="settings[capture_selfie_enabled]" value="1" class="sr-only peer" {{ ($settings->get('capture_selfie_enabled', '1')) == '1' ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security & Bindings Tab -->
                <div id="tab-content-security" class="tab-content space-y-6 hidden">
                    <h3 class="text-xl font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center gap-2">
                        <i class="bi bi-shield-lock-fill text-rose-500"></i> Security Control & Device Bindings
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                        <!-- allow_single_device -->
                        <div class="bg-slate-50/50 p-5 rounded-2xl border border-slate-100 flex flex-col justify-between">
                            <div>
                                <h4 class="font-bold text-sm text-slate-800 mb-1">Restrict login to a single device</h4>
                                <p class="text-xs text-slate-500 leading-relaxed mb-4">When checked, the system binds the user's initial login device ID, rejecting checkins from other hardware.</p>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Status</span>
                                <input type="hidden" name="settings[allow_single_device]" value="0">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="settings[allow_single_device]" value="1" class="sr-only peer" {{ ($settings->get('allow_single_device', '0')) == '1' ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-rose-600"></div>
                                </label>
                            </div>
                        </div>

                        <!-- max_failed_attempts -->
                        <div class="bg-slate-50/50 p-5 rounded-2xl border border-slate-100 flex flex-col justify-between">
                            <div>
                                <h4 class="font-bold text-sm text-slate-800 mb-1">Max Failed Biometric Attempts</h4>
                                <p class="text-xs text-slate-500 leading-relaxed mb-4">Number of failed face validation check attempts before the profile is locked temporarily.</p>
                            </div>
                            <div class="mt-2">
                                <input type="number" name="settings[max_failed_attempts]" value="{{ $settings->get('max_failed_attempts', '3') }}" min="1" max="10" class="w-full px-4 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500 font-semibold text-slate-700 bg-white">
                            </div>
                        </div>

                        <!-- failed_attempts_lock_duration -->
                        <div class="bg-slate-50/50 p-5 rounded-2xl border border-slate-100 flex flex-col justify-between">
                            <div>
                                <h4 class="font-bold text-sm text-slate-800 mb-1">Profile Lockout Duration</h4>
                                <p class="text-xs text-slate-500 leading-relaxed mb-4">Minutes the user is blocked from recapturing face verification logs after exceeding failed attempts limit.</p>
                            </div>
                            <div class="mt-2">
                                <input type="number" name="settings[failed_attempts_lock_duration]" value="{{ $settings->get('failed_attempts_lock_duration', '15') }}" min="1" max="1440" class="w-full px-4 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500 font-semibold text-slate-700 bg-white">
                            </div>
                        </div>

                        <!-- session_timeout -->
                        <div class="bg-slate-50/50 p-5 rounded-2xl border border-slate-100 flex flex-col justify-between">
                            <div>
                                <h4 class="font-bold text-sm text-slate-800 mb-1">Mobile Token Session Expiry</h4>
                                <p class="text-xs text-slate-500 leading-relaxed mb-4">Duration in minutes before bearer access tokens expire. Requiring refresh token updates.</p>
                            </div>
                            <div class="mt-2">
                                <input type="number" name="settings[session_timeout]" value="{{ $settings->get('session_timeout', '120') }}" min="5" max="525600" class="w-full px-4 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500 font-semibold text-slate-700 bg-white">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Submit Action panel -->
                <div class="mt-auto pt-8 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="reset" class="px-5 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-500 font-semibold text-xs transition-all duration-300">
                        Reset Defaults
                    </button>
                    <button type="submit" class="px-6 py-3 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-bold text-xs shadow-lg shadow-indigo-500/20 hover:shadow-indigo-500/30 hover:scale-[1.02] active:scale-[0.98] transition-all duration-300">
                        Apply Changes
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Tabs JavaScript Toggle -->
<script>
    function switchTab(tabId) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });

        // Deactivate all tab buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('bg-gradient-to-r', 'from-indigo-50', 'to-purple-50', 'border-indigo-200', 'text-indigo-900', 'ring-2', 'ring-indigo-100/50');
            btn.classList.add('bg-white', 'border-slate-200', 'text-slate-700');
        });

        // Show selected tab content
        const activeContent = document.getElementById('tab-content-' + tabId);
        if (activeContent) {
            activeContent.classList.remove('hidden');
        }

        // Activate selected tab button
        const activeBtn = document.getElementById('tab-btn-' + tabId);
        if (activeBtn) {
            activeBtn.classList.remove('bg-white', 'border-slate-200', 'text-slate-700');
            activeBtn.classList.add('bg-gradient-to-r', 'from-indigo-50', 'to-purple-50', 'border-indigo-200', 'text-indigo-900', 'ring-2', 'ring-indigo-100/50');
        }
    }

    function toggleAutoCheckoutTime() {
        const autoCheckoutToggle = document.getElementById('auto-checkout-toggle');
        const container = document.getElementById('auto-checkout-time-container');
        if (autoCheckoutToggle && container) {
            if (autoCheckoutToggle.checked) {
                container.style.opacity = '1';
                container.querySelectorAll('input').forEach(el => el.disabled = false);
            } else {
                container.style.opacity = '0.5';
                container.querySelectorAll('input').forEach(el => el.disabled = true);
            }
        }
    }

    // Initialize Page (default tab)
    document.addEventListener('DOMContentLoaded', () => {
        switchTab('attendance');
        toggleAutoCheckoutTime();
    });
</script>
@endsection
