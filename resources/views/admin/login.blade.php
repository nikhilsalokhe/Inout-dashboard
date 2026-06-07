<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InOut Admin Portal - Sign In</title>
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
    </style>
</head>
<body class="bg-[#0b0f19] min-h-screen flex items-center justify-center p-6 relative overflow-hidden">

    <!-- Atmospheric Neon Ambient Radial Lights -->
    <div class="absolute w-[400px] h-[400px] rounded-full bg-indigo-600/10 blur-[120px] -top-20 -left-20 pointer-events-none"></div>
    <div class="absolute w-[450px] h-[450px] rounded-full bg-purple-600/10 blur-[150px] -bottom-20 -right-20 pointer-events-none"></div>

    <div class="w-full max-w-md z-10">
        <!-- Glassmorphic Login Card -->
        <div class="bg-slate-900/50 backdrop-blur-xl rounded-3xl border border-slate-800/80 shadow-2xl shadow-black/40 overflow-hidden">
            <!-- Header Brand Branding -->
            <div class="p-8 text-center border-b border-slate-800/40 relative">
                <div class="absolute inset-0 bg-gradient-to-b from-indigo-500/5 to-transparent pointer-events-none"></div>
                
                <div class="w-14 h-14 bg-gradient-to-tr from-indigo-500 to-purple-600 rounded-2xl flex items-center justify-center text-white text-2xl mx-auto mb-4 shadow-lg shadow-indigo-500/30">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <h2 class="text-2xl font-extrabold text-white tracking-tight">Admin Gateway</h2>
                <p class="text-slate-400 text-xs mt-1.5 uppercase tracking-widest font-semibold">InOut Attendance System</p>
            </div>

            <!-- Login Form -->
            <form action="{{ route('admin.login.submit') }}" method="POST" class="p-8 space-y-6">
                @csrf
                
                <!-- Email Address Input -->
                <div>
                    <label for="email" class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Corporate Email</label>
                    <div class="relative group">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 transition-colors group-focus-within:text-indigo-400">
                            <i class="bi bi-envelope-fill"></i>
                        </span>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                            class="w-full bg-slate-950/80 pl-12 pr-4 py-3.5 rounded-xl border border-slate-800 text-white placeholder:text-slate-600 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all duration-300"
                            placeholder="admin@inout.com">
                    </div>
                    @error('email') <p class="text-rose-400 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                </div>

                <!-- Password Input -->
                <div>
                    <label for="password" class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Security Key</label>
                    <div class="relative group">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 transition-colors group-focus-within:text-indigo-400">
                            <i class="bi bi-key-fill"></i>
                        </span>
                        <input type="password" name="password" id="password" required
                            class="w-full bg-slate-950/80 pl-12 pr-4 py-3.5 rounded-xl border border-slate-800 text-white placeholder:text-slate-600 focus:border-indigo-500/80 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all duration-300"
                            placeholder="••••••••">
                    </div>
                </div>

                <!-- Remember Me Checkbox -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2.5 cursor-pointer group">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-800 bg-slate-950 text-indigo-500 focus:ring-indigo-500/20 focus:ring-offset-0 outline-none">
                        <span class="text-xs font-semibold text-slate-400 group-hover:text-slate-300 transition-colors">Keep my session active</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full py-4 bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-extrabold rounded-xl shadow-lg shadow-indigo-500/20 hover:shadow-indigo-500/40 hover:from-indigo-600 hover:to-purple-700 hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] transition-all duration-300">
                    Authenticate Suite
                </button>
            </form>
        </div>
        
        <!-- Footer info -->
        <p class="text-center text-slate-600 text-xs mt-8 font-medium tracking-wide">
            &copy; {{ date('Y') }} InOut Security Systems. All rights reserved.
        </p>
    </div>

</body>
</html>
