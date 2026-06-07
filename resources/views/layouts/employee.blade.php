<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Portal - Attendance System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
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
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-slate-50/80 to-indigo-50/20 text-slate-800 min-h-screen">

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-slate-950 text-slate-400 hidden md:flex flex-col sticky top-0 h-screen z-20 border-r border-slate-900">
            <div class="p-6 flex flex-col h-full">
                <!-- Branding -->
                <div class="flex items-center gap-3 mb-8 px-2">
                    <div class="w-10 h-10 bg-gradient-to-tr from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-white text-xl shadow-lg shadow-indigo-500/30">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <span class="text-xl font-extrabold tracking-wider text-white block leading-none">InOut</span>
                        <span class="text-[9px] font-bold text-indigo-400 tracking-widest uppercase">Self Service</span>
                    </div>
                </div>

                <!-- Nav List -->
                <nav class="space-y-1.5 flex-1 overflow-y-auto sidebar-scroll pr-1">
                    <a href="{{ route('employee.dashboard') }}" class="flex items-center gap-3.5 px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('employee.dashboard') ? 'sidebar-active text-white' : 'hover:bg-slate-900/50 hover:text-white hover:translate-x-1' }}">
                        <i class="bi bi-grid-1x2-fill text-lg"></i>
                        <span class="font-semibold text-sm">Dashboard</span>
                    </a>
                    
                    <a href="{{ route('employee.leaves') }}" class="flex items-center gap-3.5 px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('employee.leaves') ? 'sidebar-active text-white' : 'hover:bg-slate-900/50 hover:text-white hover:translate-x-1' }}">
                        <i class="bi bi-calendar-event-fill text-lg"></i>
                        <span class="font-semibold text-sm">Leaves Portal</span>
                    </a>

                    <a href="{{ route('employee.payslips') }}" class="flex items-center gap-3.5 px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('employee.payslips') || request()->routeIs('employee.payslip.*') ? 'sidebar-active text-white' : 'hover:bg-slate-900/50 hover:text-white hover:translate-x-1' }}">
                        <i class="bi bi-wallet2 text-lg"></i>
                        <span class="font-semibold text-sm">Payslips History</span>
                    </a>
                </nav>

                <!-- Footer Sign Out -->
                <div class="mt-auto pt-6 border-t border-slate-900">
                    <form action="{{ route('admin.logout') }}" method="POST" id="logout-form" class="hidden">
                        @csrf
                    </form>
                    <button onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="flex items-center gap-3.5 px-4 py-3.5 w-full text-left rounded-xl text-rose-400 hover:bg-rose-950/20 hover:text-rose-300 transition-all duration-300 font-semibold text-sm">
                        <i class="bi bi-box-arrow-right text-lg"></i>
                        <span>Logout Portal</span>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col min-w-0">
            <header class="h-20 glass-header border-b border-slate-200/50 flex items-center justify-between px-8 sticky top-0 z-10 shadow-sm shadow-slate-100/50">
                <div class="flex items-center gap-2">
                    <div class="w-1.5 h-6 bg-indigo-500 rounded-full"></div>
                    <h1 class="text-lg font-extrabold text-slate-900 tracking-tight">
                        @yield('title', 'Employee Workspace')
                    </h1>
                </div>

                <div class="flex items-center gap-4">
                    <div class="flex flex-col text-right hidden sm:flex">
                        <span class="text-sm font-bold text-slate-900 leading-none mb-1">{{ auth()->user()->name }}</span>
                        <span class="text-[9px] font-extrabold text-indigo-500 tracking-wider uppercase bg-indigo-50 border border-indigo-100 px-2 py-0.5 rounded-full self-end">
                            {{ auth()->user()->employee_code ?? 'EMPLOYEE' }}
                        </span>
                    </div>
                    <div class="w-10 h-10 bg-gradient-to-tr from-slate-100 to-slate-200 rounded-full flex items-center justify-center text-slate-600 border border-slate-200 shadow-inner">
                        <i class="bi bi-person text-xl"></i>
                    </div>
                </div>
            </header>

            <div class="p-8 flex-1">
                @if(session('success'))
                    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl flex items-center gap-3 shadow-sm">
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
    (function() {
        var sidebarEl = document.querySelector('nav.sidebar-scroll');
        if (!sidebarEl) return;

        var storageKey = 'employee_sidebar_scroll';

        // Restore scroll position on page load
        var savedPos = localStorage.getItem(storageKey);
        if (savedPos !== null) {
            sidebarEl.scrollTop = parseInt(savedPos, 10);
        }

        // Scroll active item into view if not visible
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
        sidebarEl.addEventListener('scroll', function() {
            localStorage.setItem(storageKey, sidebarEl.scrollTop);
        }, { passive: true });
    })();
</script>
</body>
</html>
