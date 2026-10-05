<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light">
<head>
    @include('partials.head', ['title' => __('Log in')])
    <style>
        /* Custom styles for animations if needed */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
    </style>
</head>
<body class="min-h-screen bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 antialiased font-sans flex items-center justify-center p-4 sm:p-8 relative overflow-hidden">

    <!-- Background Decor -->
    <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none z-0">
        <!-- Dot Pattern Overlay -->
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0Ij48Y2lyY2xlIGN4PSIyIiBjeT0iMiIgcj0iMS41IiBmaWxsPSIjY2JkNWUxIiBmaWxsLW9wYWNpdHk9IjAuNSIgLz48L3N2Zz4=')] [mask-image:radial-gradient(ellipse_at_center,black_40%,transparent_80%)] dark:[mask-image:radial-gradient(ellipse_at_center,white_40%,transparent_80%)]"></div>

        <!-- Animated Mesh Gradients -->
        <div class="absolute -top-[10%] -left-[5%] w-[50%] h-[50%] rounded-full bg-blue-400/30 dark:bg-blue-600/20 blur-[100px] mix-blend-multiply dark:mix-blend-screen animate-float"></div>
        <div class="absolute top-[30%] -right-[10%] w-[45%] h-[55%] rounded-full bg-indigo-300/40 dark:bg-indigo-500/20 blur-[120px] mix-blend-multiply dark:mix-blend-screen animate-float" style="animation-delay: -2s;"></div>
        <div class="absolute -bottom-[15%] left-[15%] w-[50%] h-[50%] rounded-full bg-purple-300/30 dark:bg-purple-600/20 blur-[100px] mix-blend-multiply dark:mix-blend-screen animate-float" style="animation-delay: -4s;"></div>
    </div>

    <!-- Main Card -->
    <div class="w-full max-w-6xl bg-white dark:bg-slate-800 rounded-[2.5rem] shadow-2xl overflow-hidden flex flex-col md:flex-row border border-slate-100 dark:border-slate-700/50 relative z-10">

        <!-- Left Side: Login Form -->
        <div class="w-full md:w-1/2 p-8 sm:p-12 lg:p-16 flex flex-col justify-center bg-white dark:bg-slate-800">

            <div class="max-w-md w-full mx-auto">
                <!-- Mobile Logo (shows only on small screens) -->
                <div class="md:hidden flex items-center gap-3 mb-8">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 shadow-lg shadow-blue-500/30">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <rect x="2" y="3" width="20" height="12" rx="2" />
                            <path d="M12 15v4" />
                            <path d="M9 19h6" />
                            <path d="M19 8h-2l-1.5-2H8.5L7 8H5" />
                            <circle cx="10" cy="8" r="1" />
                            <circle cx="14" cy="8" r="1" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">Phone Shop POS</h1>
                    </div>
                </div>

                <div class="mb-10">
                    <h2 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight mb-2">Welcome Back</h2>
                    <p class="text-slate-500 dark:text-slate-400">Please sign in to your account to continue.</p>
                </div>

                <!-- Session Status Alerts -->
                @if (session('status'))
                    <div class="p-4 mb-6 text-sm text-emerald-700 bg-emerald-50 dark:bg-emerald-500/10 dark:text-emerald-400 rounded-xl border border-emerald-200 dark:border-emerald-500/20 flex items-center gap-3">
                        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="font-medium">{{ session('status') }}</span>
                    </div>
                @endif

                <!-- Validation Errors Alerts -->
                @if ($errors->any())
                    <div class="p-4 mb-6 text-sm text-red-700 bg-red-50 dark:bg-red-500/10 dark:text-red-400 rounded-xl border border-red-200 dark:border-red-500/20">
                        <div class="flex items-center gap-2 font-semibold mb-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <span>Authentication failed</span>
                        </div>
                        <ul class="list-disc list-inside space-y-1 ml-1 text-red-600 dark:text-red-400/80">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Login Form -->
                <form method="POST" action="{{ route('login.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label for="email-input" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Username or Email</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                    <circle cx="9" cy="7" r="4" />
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                </svg>
                            </div>
                            <input
                                id="email-input"
                                name="email"
                                type="text"
                                required
                                autofocus
                                value="{{ old('email') }}"
                                placeholder="Enter your username or email"
                                class="w-full pl-12 pr-4 py-3.5 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 text-slate-900 dark:text-white text-sm transition-all shadow-sm"
                            />
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label for="password-input" class="text-sm font-semibold text-slate-700 dark:text-slate-300">Password</label>
                            <button type="button" onclick="showDeveloperContact()" class="text-sm font-semibold text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 hover:underline transition-all">
                                Forgot password?
                            </button>
                        </div>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>
                            </div>
                            <input
                                id="password-input"
                                name="password"
                                type="password"
                                required
                                autocomplete="current-password"
                                placeholder="Enter your password"
                                class="w-full pl-12 pr-12 py-3.5 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 text-slate-900 dark:text-white text-sm transition-all shadow-sm"
                            />
                            <!-- Show/Hide Password Toggle -->
                            <button
                                type="button"
                                onclick="togglePasswordVisibility()"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors focus:outline-none"
                                title="Toggle Password Visibility"
                            >
                                <svg id="eye-open-icon" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                <svg id="eye-closed-icon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                    <line x1="1" y1="1" x2="23" y2="23"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <div class="relative flex items-center">
                            <input
                                id="remember-checkbox"
                                name="remember"
                                type="checkbox"
                                class="peer h-5 w-5 cursor-pointer appearance-none rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 checked:border-blue-600 checked:bg-blue-600 transition-all"
                            />
                            <svg class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-3.5 h-3.5 pointer-events-none opacity-0 peer-checked:opacity-100 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </div>
                        <label for="remember-checkbox" class="ml-3 block text-sm font-medium text-slate-700 dark:text-slate-300 cursor-pointer select-none">
                            Keep me logged in
                        </label>
                    </div>

                    <button
                        id="login-btn"
                        type="submit"
                        class="w-full py-3.5 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow-lg shadow-blue-500/30 hover:shadow-blue-600/40 hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-center gap-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800"
                    >
                        <span>Sign in securely</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </button>
                </form>

            </div>

            <!-- Mobile Footer Env Info -->
            @php
                $devName = env('DEVELOPER_NAME', 'twinsofte.com');
                $devPhone = env('DEVELOPER_PHONE', '0762472822');
                $devWeb = env('DEVELOPER_WEB', 'twinsofte.com');

                $devUrl = $devWeb;
                if (!str_starts_with($devUrl, 'http://') && !str_starts_with($devUrl, 'https://')) {
                    $devUrl = 'https://' . $devUrl;
                }
            @endphp
            <div class="md:hidden text-center text-xs text-slate-500 mt-10">
                <span>Powered by </span>
                <a href="{{ $devUrl }}" target="_blank" rel="noopener" class="text-blue-600 font-semibold hover:underline">
                    {{ $devName }}
                </a>
            </div>
        </div>

        <!-- Right Side: Beautiful Promo Panel -->
        <div class="w-full md:w-1/2 bg-gradient-to-br from-slate-900 via-blue-950 to-indigo-950 text-white p-12 lg:p-16 flex-col justify-between relative overflow-hidden hidden md:flex">
            <!-- Decorative shapes -->
            <div class="absolute top-0 right-0 w-full h-full bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjIiIGZpbGw9IiNmZmZmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNSIvPjwvc3ZnPg==')] opacity-30"></div>
            <div class="absolute -bottom-[20%] -right-[10%] w-[70%] h-[70%] rounded-full bg-blue-500/20 blur-[80px]"></div>
            <div class="absolute top-[10%] -left-[20%] w-[50%] h-[50%] rounded-full bg-indigo-500/20 blur-[80px]"></div>

            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-12">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/10 backdrop-blur-md border border-white/20 shadow-xl">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <rect x="2" y="3" width="20" height="12" rx="2" />
                            <path d="M12 15v4" />
                            <path d="M9 19h6" />
                            <path d="M19 8h-2l-1.5-2H8.5L7 8H5" />
                            <circle cx="10" cy="8" r="1" />
                            <circle cx="14" cy="8" r="1" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-white">Phone Shop POS</h1>
                        <p class="text-xs text-blue-200 font-medium tracking-wider uppercase mt-1">{{ \App\Support\Features::moduleLabel() }}</p>
                    </div>
                </div>

                <h2 class="text-4xl lg:text-5xl font-bold leading-tight mb-6">
                    Manage your store <br/>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-indigo-300">like a pro.</span>
                </h2>

                <p class="text-blue-100/80 text-lg max-w-md mb-12 leading-relaxed">
                    A complete point of sale solution designed specifically for phone shops. Handle {{ \App\Support\Features::moduleLabel() }}, inventory, and accounting in one powerful platform.
                </p>

                <!-- Floating Stats/Feature Card -->
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-6 max-w-md animate-float shadow-2xl">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <div class="flex items-center gap-2 text-blue-300 mb-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                                <span class="text-xs font-semibold uppercase tracking-wider">Fast POS</span>
                            </div>
                            <p class="text-sm text-white font-medium">Quick checkout</p>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 text-blue-300 mb-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                                <span class="text-xs font-semibold uppercase tracking-wider">Real-time</span>
                            </div>
                            <p class="text-sm text-white font-medium">Live inventory</p>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 text-blue-300 mb-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                <span class="text-xs font-semibold uppercase tracking-wider">Secure</span>
                            </div>
                            <p class="text-sm text-white font-medium">Cloud backed</p>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 text-blue-300 mb-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                                <span class="text-xs font-semibold uppercase tracking-wider">Anywhere</span>
                            </div>
                            <p class="text-sm text-white font-medium">Multi-device</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="relative z-10 flex items-center justify-between text-xs text-white/60 font-medium">
                <div>
                    <span>Powered by </span>
                    <a href="{{ $devUrl }}" target="_blank" rel="noopener" class="text-white hover:text-blue-300 transition-colors">
                        {{ $devName }}
                    </a>
                </div>
                <div>{{ $devPhone }}</div>
            </div>
        </div>
    </div>

    <!-- Client-side Scripts -->
    <script>
        // Force light mode for the login page specifically if requested,
        // though we support dark mode gracefully in the new design.
        function forceLightTheme() {
            document.documentElement.classList.remove('dark');
            document.documentElement.classList.add('light');
            localStorage.setItem('flux-appearance', 'light');
            localStorage.setItem('theme', 'light');
        }
        forceLightTheme();
        window.addEventListener('DOMContentLoaded', forceLightTheme);
        window.addEventListener('livewire:navigated', forceLightTheme);

        // Toggle password input visibility
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password-input');
            const eyeOpen = document.getElementById('eye-open-icon');
            const eyeClosed = document.getElementById('eye-closed-icon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeOpen.classList.add('hidden');
                eyeClosed.classList.remove('hidden');
            } else {
                passwordInput.type = 'password';
                eyeOpen.classList.remove('hidden');
                eyeClosed.classList.add('hidden');
            }
        }

        // Developer Contact SweetAlert popup
        function showDeveloperContact() {
            Swal.fire({
                title: 'Password Reset',
                html: `
                    <div class="text-sm text-slate-600 dark:text-slate-400 mb-6 font-medium">
                        For security reasons, please contact your system administrator or developer to reset your password.
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-800 p-5 rounded-xl border border-slate-100 dark:border-slate-700 text-left space-y-3">
                        <div class="flex items-center gap-3 text-sm">
                            <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 uppercase font-bold tracking-wider">Developer</p>
                                <p class="font-semibold text-slate-900 dark:text-white">{{ $devName }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 text-sm">
                            <div class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 uppercase font-bold tracking-wider">Phone</p>
                                <a href="tel:{{ $devPhone }}" class="font-semibold text-blue-600 dark:text-blue-400 hover:underline">{{ $devPhone }}</a>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 text-sm">
                            <div class="w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900/50 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 uppercase font-bold tracking-wider">Website</p>
                                <a href="{{ $devUrl }}" target="_blank" class="font-semibold text-blue-600 dark:text-blue-400 hover:underline break-all">{{ $devWeb }}</a>
                            </div>
                        </div>
                    </div>
                `,
                icon: 'info',
                iconColor: '#3b82f6',
                confirmButtonText: 'Understood',
                confirmButtonColor: '#2563EB',
                background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#ffffff',
                color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#0f172a',
                customClass: {
                    popup: 'rounded-[2rem] shadow-2xl border border-slate-100 dark:border-slate-700',
                    confirmButton: 'rounded-xl shadow-lg shadow-blue-500/30 px-6 py-2.5 font-semibold transition-all hover:bg-blue-700',
                    title: 'text-xl font-bold mt-2'
                }
            });
        }
    </script>
    @fluxScripts
</body>
</html>
