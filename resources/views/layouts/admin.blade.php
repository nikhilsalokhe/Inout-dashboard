<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InOut Admin Portal - Attendance System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .sidebar-active {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(139, 92, 246, 0.15) 100%);
            color: #ffffff;
            border-left: 4px solid #6366f1;
            box-shadow: inset 0 0 12px rgba(99, 102, 241, 0.05);
        }

        .glass-header {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        /* Custom Scrollbar for Sidebar */
        .sidebar-scroll::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 4px;
        }

        .sidebar-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }
    </style>
    @stack('styles')
</head>

<body class="bg-gradient-to-br from-slate-50 via-slate-50/80 to-indigo-50/20 text-slate-800 min-h-screen">

    <div class="flex min-h-screen">
        <!-- Modern Dark Glassmorphic Sidebar -->
        <aside
            class="w-64 bg-slate-950 text-slate-400 hidden md:flex flex-col sticky top-0 h-screen z-20 border-r border-slate-900">
            <div class="p-6 flex flex-col h-full">
                <!-- Branding Header -->
                <div class="flex items-center gap-3 mb-8 px-2">
                    <div
                        class="w-10 h-10 bg-gradient-to-tr from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-white text-xl shadow-lg shadow-indigo-500/30">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <span class="text-xl font-extrabold tracking-wider text-white block leading-none">InOut</span>
                        <span class="text-[9px] font-bold text-indigo-400 tracking-widest uppercase">Admin System</span>
                    </div>
                </div>

                <!-- Navigation List -->
                <nav class="space-y-1.5 flex-1 overflow-y-auto sidebar-scroll pr-1">
                    <a href="{{ route('admin.dashboard') }}"
                        class="flex items-center gap-3.5 px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('admin.dashboard') ? 'sidebar-active text-white' : 'hover:bg-slate-900/50 hover:text-white hover:translate-x-1' }}">
                        <i class="bi bi-grid-1x2-fill text-lg"></i>
                        <span class="font-semibold text-sm">Dashboard</span>
                    </a>

                    <a href="{{ route('admin.employees.index') }}"
                        class="flex items-center gap-3.5 px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('admin.employees.index') || request()->routeIs('admin.employees.create') || request()->routeIs('admin.employees.edit') ? 'sidebar-active text-white' : 'hover:bg-slate-900/50 hover:text-white hover:translate-x-1' }}">
                        <i class="bi bi-people-fill text-lg"></i>
                        <span class="font-semibold text-sm">Employees</span>
                    </a>

                    <a href="{{ route('admin.terminations.index') }}"
                        class="flex items-center gap-3.5 px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('admin.terminations.*') ? 'sidebar-active text-white' : 'hover:bg-slate-900/50 hover:text-white hover:translate-x-1' }}">
                        <i class="bi bi-person-dash-fill text-lg"></i>
                        <span class="font-semibold text-sm">Exit Management</span>
                    </a>

                    <a href="{{ route('admin.organization.index') }}"
                        class="flex items-center gap-3.5 px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('admin.organization.*') ? 'sidebar-active text-white' : 'hover:bg-slate-900/50 hover:text-white hover:translate-x-1' }}">
                        <i class="bi bi-building text-lg"></i>
                        <span class="font-semibold text-sm">Organization</span>
                    </a>

                    <a href="{{ route('admin.org-tree') }}"
                        class="flex items-center gap-3.5 px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('admin.org-tree') ? 'sidebar-active text-white' : 'hover:bg-slate-900/50 hover:text-white hover:translate-x-1' }}">
                        <i class="bi bi-diagram-3-fill text-lg"></i>
                        <span class="font-semibold text-sm">Org Hierarchy</span>
                    </a>

                    <a href="{{ route('admin.leaves.policies') }}"
                        class="flex items-center gap-3.5 px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('admin.leaves.policies') || request()->routeIs('admin.leaves.balances') ? 'sidebar-active text-white' : 'hover:bg-slate-900/50 hover:text-white hover:translate-x-1' }}">
                        <i class="bi bi-calendar-event-fill text-lg"></i>
                        <span class="font-semibold text-sm">Leave Policies</span>
                    </a>

                    <a href="{{ route('admin.leaves.applications') }}"
                        class="flex items-center gap-3.5 px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('admin.leaves.applications') ? 'sidebar-active text-white' : 'hover:bg-slate-900/50 hover:text-white hover:translate-x-1' }}">
                        <i class="bi bi-journal-check text-lg"></i>
                        <span class="font-semibold text-sm">Leave Queue</span>
                    </a>

                    <a href="{{ route('admin.holidays.index') }}"
                        class="flex items-center gap-3.5 px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('admin.holidays.*') ? 'sidebar-active text-white' : 'hover:bg-slate-900/50 hover:text-white hover:translate-x-1' }}">
                        <i class="bi bi-calendar3-event-fill text-lg"></i>
                        <span class="font-semibold text-sm">Holiday Master</span>
                    </a>

                    <a href="{{ route('admin.face-resets.index') }}"
                        class="flex items-center gap-3.5 px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('admin.face-resets.*') ? 'sidebar-active text-white' : 'hover:bg-slate-900/50 hover:text-white hover:translate-x-1' }}">
                        <i class="bi bi-person-exclamation text-lg"></i>
                        <span class="font-semibold text-sm">Face Resets</span>
                    </a>

                    <a href="{{ route('admin.announcements.index') }}"
                        class="flex items-center gap-3.5 px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('admin.announcements.*') ? 'sidebar-active text-white' : 'hover:bg-slate-900/50 hover:text-white hover:translate-x-1' }}">
                        <i class="bi bi-megaphone-fill text-lg"></i>
                        <span class="font-semibold text-sm">Announcements</span>
                    </a>

                    <div class="pt-6 pb-2 px-4 text-[10px] font-bold text-slate-600 uppercase tracking-widest">Time &
                        Attendance</div>

                    <a href="{{ route('admin.attendance.board') }}"
                        class="flex items-center gap-3.5 px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('admin.attendance.board') ? 'sidebar-active text-white' : 'hover:bg-slate-900/50 hover:text-white hover:translate-x-1' }}">
                        <i class="bi bi-calendar-check-fill text-lg"></i>
                        <span class="font-semibold text-sm">Attendance Board</span>
                    </a>

                    <a href="{{ route('admin.regularizations.index') }}"
                        class="flex items-center gap-3.5 px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('admin.regularizations.*') ? 'sidebar-active text-white' : 'hover:bg-slate-900/50 hover:text-white hover:translate-x-1' }}">
                        <i class="bi bi-check2-circle text-lg"></i>
                        <span class="font-semibold text-sm">Regularizations</span>
                    </a>

                    <a href="{{ route('admin.shifts.index') }}"
                        class="flex items-center gap-3.5 px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('admin.shifts.*') ? 'sidebar-active text-white' : 'hover:bg-slate-900/50 hover:text-white hover:translate-x-1' }}">
                        <i class="bi bi-clock-history text-lg"></i>
                        <span class="font-semibold text-sm">Shift Policies</span>
                    </a>

                    @if(\App\Models\Setting::get('overtime_module_enabled', '0') == '1')
                        <div class="pt-4 pb-2 px-4 text-[10px] font-bold text-slate-600 uppercase tracking-widest">Overtime
                        </div>

                        <a href="{{ route('admin.overtime.dashboard') }}"
                            class="flex items-center gap-3.5 px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('admin.overtime.dashboard') ? 'sidebar-active text-white' : 'hover:bg-slate-900/50 hover:text-white hover:translate-x-1' }}">
                            <i class="bi bi-speedometer2 text-lg"></i>
                            <span class="font-semibold text-sm">OT Dashboard</span>
                        </a>

                        <a href="{{ route('admin.overtime.requests.index') }}"
                            class="flex items-center gap-3.5 px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('admin.overtime.requests.*') ? 'sidebar-active text-white' : 'hover:bg-slate-900/50 hover:text-white hover:translate-x-1' }}">
                            <i class="bi bi-list-check text-lg"></i>
                            <span class="font-semibold text-sm">OT Approvals</span>
                            @php
                                $otPending = \App\Models\OvertimeRecord::where('status', 'pending')->count();
                            @endphp
                            @if($otPending > 0)
                                <span
                                    class="ml-auto px-1.5 py-0.5 rounded-full bg-orange-500 text-white text-[10px] font-extrabold leading-none">{{ $otPending }}</span>
                            @endif
                        </a>

                        <a href="{{ route('admin.overtime.policies.index') }}"
                            class="flex items-center gap-3.5 px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('admin.overtime.policies.*') ? 'sidebar-active text-white' : 'hover:bg-slate-900/50 hover:text-white hover:translate-x-1' }}">
                            <i class="bi bi-file-earmark-ruled text-lg"></i>
                            <span class="font-semibold text-sm">OT Policies</span>
                        </a>

                        <a href="{{ route('admin.overtime.assignments.index') }}"
                            class="flex items-center gap-3.5 px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('admin.overtime.assignments.*') ? 'sidebar-active text-white' : 'hover:bg-slate-900/50 hover:text-white hover:translate-x-1' }}">
                            <i class="bi bi-link-45deg text-lg"></i>
                            <span class="font-semibold text-sm">OT Assignments</span>
                        </a>
                    @endif

                    <div class="pt-6 pb-2 px-4 text-[10px] font-bold text-slate-600 uppercase tracking-widest">Payroll &
                        Finance</div>


                    <a href="{{ route('admin.salary.index') }}"
                        class="flex items-center gap-3.5 px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('admin.salary.*') ? 'sidebar-active text-white' : 'hover:bg-slate-900/50 hover:text-white hover:translate-x-1' }}">
                        <i class="bi bi-cash-stack text-lg"></i>
                        <span class="font-semibold text-sm">Salary Packages</span>
                    </a>

                    <a href="{{ route('admin.payroll.index') }}"
                        class="flex items-center gap-3.5 px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('admin.payroll.index') || request()->routeIs('admin.payroll.show') ? 'sidebar-active text-white' : 'hover:bg-slate-900/50 hover:text-white hover:translate-x-1' }}">
                        <i class="bi bi-wallet2 text-lg"></i>
                        <span class="font-semibold text-sm">Payroll Process</span>
                    </a>

                    <a href="{{ route('admin.payroll.reports') }}"
                        class="flex items-center gap-3.5 px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('admin.payroll.reports') ? 'sidebar-active text-white' : 'hover:bg-slate-900/50 hover:text-white hover:translate-x-1' }}">
                        <i class="bi bi-file-earmark-bar-graph text-lg"></i>
                        <span class="font-semibold text-sm">Payroll Audit</span>
                    </a>

                    <a href="{{ route('admin.analytics.dashboard') }}"
                        class="flex items-center gap-3.5 px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('admin.analytics.dashboard') ? 'sidebar-active text-white' : 'hover:bg-slate-900/50 hover:text-white hover:translate-x-1' }}">
                        <i class="bi bi-pie-chart-fill text-lg"></i>
                        <span class="font-semibold text-sm">HR Analytics</span>
                    </a>

                    <div class="pt-6 pb-2 px-4 text-[10px] font-bold text-slate-600 uppercase tracking-widest">Reporting
                        Suite</div>

                    <a href="{{ route('admin.reports.index') }}"
                        class="flex items-center gap-3.5 px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('admin.reports.index') ? 'sidebar-active text-white' : 'hover:bg-slate-900/50 hover:text-white hover:translate-x-1' }}">
                        <i class="bi bi-bar-chart-line-fill text-lg"></i>
                        <span class="font-semibold text-sm">Reports Workspace</span>
                    </a>

                    <a href="{{ route('admin.audit-logs.index') }}"
                        class="flex items-center gap-3.5 px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('admin.audit-logs.index') ? 'sidebar-active text-white' : 'hover:bg-slate-900/50 hover:text-white hover:translate-x-1' }}">
                        <i class="bi bi-shield-lock-fill text-lg"></i>
                        <span class="font-semibold text-sm">Audit Trail</span>
                    </a>
                </nav>

                <!-- Footer: Settings + Sign Out -->
                <div class="mt-auto pt-4 border-t border-slate-900 space-y-1">
                    <a href="{{ route('admin.settings.index') }}"
                        class="flex items-center gap-3.5 px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('admin.settings.index') ? 'sidebar-active text-white' : 'hover:bg-slate-900/50 hover:text-white hover:translate-x-1' }}">
                        <i class="bi bi-gear-fill text-lg"></i>
                        <span class="font-semibold text-sm">System Settings</span>
                    </a>
                    <form action="{{ route('admin.logout') }}" method="POST" id="logout-form" class="hidden">
                        @csrf
                    </form>
                    <button onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                        class="flex items-center gap-3.5 px-4 py-3.5 w-full text-left rounded-xl text-rose-400 hover:bg-rose-950/20 hover:text-rose-300 transition-all duration-300 font-semibold text-sm">
                        <i class="bi bi-box-arrow-right text-lg"></i>
                        <span>Logout</span>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 flex flex-col min-w-0">
            <!-- Frosted Glass Header -->
            <header
                class="h-20 glass-header border-b border-slate-200/50 flex items-center justify-between px-8 sticky top-0 z-10 shadow-sm shadow-slate-100/50">
                <div class="flex items-center gap-2">
                    <div class="w-1.5 h-6 bg-indigo-500 rounded-full"></div>
                    <h1 class="text-lg font-extrabold text-slate-900 tracking-tight">
                        @yield('title', 'Dashboard Overview')
                    </h1>
                </div>

                <!-- Admin Profile block -->
                <div class="flex items-center gap-4">
                    <div class="flex flex-col text-right hidden sm:flex">
                        <span
                            class="text-sm font-bold text-slate-900 leading-none mb-1">{{ auth()->user()->name }}</span>
                        <span
                            class="text-[9px] font-extrabold text-indigo-500 tracking-wider uppercase bg-indigo-50 border border-indigo-100 px-2 py-0.5 rounded-full self-end">
                            {{ auth()->user()->role }}
                        </span>
                    </div>
                    <div
                        class="w-10 h-10 bg-gradient-to-tr from-slate-100 to-slate-200 rounded-full flex items-center justify-center text-slate-600 border border-slate-200 shadow-inner">
                        <i class="bi bi-person text-xl"></i>
                    </div>
                </div>
            </header>

            <!-- Main Page Content canvas -->
            <div class="p-8 flex-1">
                @if(session('success'))
                    <div
                        class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl flex items-center gap-3 shadow-sm animate-fade-in">
                        <div class="w-8 h-8 rounded-lg bg-emerald-500 flex items-center justify-center text-white text-sm">
                            <i class="bi bi-check-lg"></i>
                        </div>
                        <span class="font-semibold text-sm">{{ session('success') }}</span>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    <script>
            // Sidebar scroll position persistence using localStorage
            (function () {
                var sidebarEl = document.querySelector('nav.sidebar-scroll');
                if (!sidebarEl) return;

                var storageKey = 'admin_sidebar_scroll';

                // Restore scroll position on page load
                var savedPos = localStorage.getItem(storageKey);
                if (savedPos !== null) {
                    sidebarEl.scrollTop = parseInt(savedPos, 10);
                }

                // Scroll active item into view if it is not visible
                var activeLink = sidebarEl.querySelector('.sidebar-active');
                if (activeLink) {
                    var navTop = sidebarEl.scrollTop;
                    var navBottom = navTop + sidebarEl.clientHeight;
                    var linkTop = activeLink.offsetTop;
                    var linkBottom = linkTop + activeLink.clientHeight;
                    if (linkTop < navTop || linkBottom > navBottom) {
                        sidebarEl.scrollTop = linkTop - sidebarEl.clientHeight / 2 + activeLink.clientHeight / 2;
                    }
                }

                // Save scroll position on every scroll event
                sidebarEl.addEventListener('scroll', function () {
                    localStorage.setItem(storageKey, sidebarEl.scrollTop);
                }, { passive: true });
            })();
    </script>
    @stack('scripts')
</body>

</html>