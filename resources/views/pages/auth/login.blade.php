<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light">
<head>
    @include('partials.head', ['title' => __('Log in')])
</head>
<body class="min-h-screen bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-100 antialiased font-sans">
    <div class="grid lg:grid-cols-12 min-h-screen">
        
        <!-- Left Side: Promo Panel (Desktop Only) -->
        <div class="lg:col-span-7 xl:col-span-8 bg-gradient-to-br from-slate-950 via-slate-900 to-blue-950 text-white relative hidden lg:flex flex-col justify-between p-12 overflow-hidden border-r border-slate-800">
            <!-- Decorative light effects -->
            <div class="absolute top-0 left-0 w-[500px] h-[500px] bg-blue-600/10 rounded-full blur-3xl pointer-events-none -translate-x-1/2 -translate-y-1/2"></div>
            <div class="absolute bottom-0 right-0 w-[600px] h-[600px] bg-indigo-500/10 rounded-full blur-3xl pointer-events-none translate-x-1/3 translate-y-1/3"></div>

            <!-- Top Header -->
            <div class="relative z-10 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-600 shadow-lg shadow-blue-500/30">
                        <!-- Custom POS & Shopping Cart SVG Logo -->
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
                        <p class="text-xs text-slate-400 font-medium tracking-wider uppercase mt-0.5">Retail & Wholesale Management</p>
                    </div>
                </div>
            </div>

            <!-- Central Content: Visual Render + System Types Grid -->
            <div class="my-auto relative z-10 flex flex-col items-center gap-8">
                <!-- System Category Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 w-full max-w-2xl mt-4">
                    <div class="bg-white/5 backdrop-blur-md border border-white/10 rounded-2xl p-4 flex flex-col items-center text-center gap-2 hover:bg-white/10 transition group">
                        <div class="p-2.5 rounded-xl bg-blue-500/20 text-blue-400 group-hover:scale-110 transition duration-300">
                            <!-- Retail POS Icon -->
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                <polyline points="9 22 9 12 15 12 15 22"/>
                            </svg>
                        </div>
                        <span class="text-sm font-semibold tracking-wide text-slate-200">Retail POS</span>
                    </div>

                    <div class="bg-white/5 backdrop-blur-md border border-white/10 rounded-2xl p-4 flex flex-col items-center text-center gap-2 hover:bg-white/10 transition group">
                        <div class="p-2.5 rounded-xl bg-blue-500/20 text-blue-400 group-hover:scale-110 transition duration-300">
                            <!-- Wholesale POS Icon -->
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                            </svg>
                        </div>
                        <span class="text-sm font-semibold tracking-wide text-slate-200">Wholesale POS</span>
                    </div>

                    <div class="bg-white/5 backdrop-blur-md border border-white/10 rounded-2xl p-4 flex flex-col items-center text-center gap-2 hover:bg-white/10 transition group">
                        <div class="p-2.5 rounded-xl bg-blue-500/20 text-blue-400 group-hover:scale-110 transition duration-300">
                            <!-- Inventory Icon -->
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                                <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                                <line x1="12" y1="22.08" x2="12" y2="12"/>
                            </svg>
                        </div>
                        <span class="text-sm font-semibold tracking-wide text-slate-200">Inventory</span>
                    </div>

                    <div class="bg-white/5 backdrop-blur-md border border-white/10 rounded-2xl p-4 flex flex-col items-center text-center gap-2 hover:bg-white/10 transition group">
                        <div class="p-2.5 rounded-xl bg-blue-500/20 text-blue-400 group-hover:scale-110 transition duration-300">
                            <!-- Accounting Icon -->
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <line x1="18" y1="20" x2="18" y2="10"/>
                                <line x1="12" y1="20" x2="12" y2="4"/>
                                <line x1="6" y1="20" x2="6" y2="14"/>
                            </svg>
                        </div>
                        <span class="text-sm font-semibold tracking-wide text-slate-200">Accounting</span>
                    </div>
                </div>

                <!-- Hero POS Rendering Image -->
                <div class="relative w-full max-w-2xl rounded-2xl overflow-hidden shadow-2xl border border-slate-800/80 bg-slate-950/60 p-2 group">
                    <img src="/images/pos_promo_banner.png" alt="POS System Mockup" class="rounded-xl w-full h-auto max-h-[360px] object-cover opacity-90 group-hover:opacity-100 transition duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/40 to-transparent pointer-events-none"></div>
                </div>
            </div>

            <!-- Bottom Features Bar -->
            <div class="relative z-10 grid grid-cols-2 xl:grid-cols-4 gap-6 pt-6 border-t border-white/10 w-full">
                <div class="flex gap-3 items-start">
                    <div class="p-2 bg-white/5 border border-white/10 rounded-lg text-blue-400 mt-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-white tracking-wide">Fast & Easy POS</h4>
                        <p class="text-[10px] text-slate-400">Speed up your sales</p>
                    </div>
                </div>
                <div class="flex gap-3 items-start">
                    <div class="p-2 bg-white/5 border border-white/10 rounded-lg text-blue-400 mt-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path d="M21.21 15.89A10 10 0 1 1 8 2.83M22 12A10 10 0 0 0 12 2v10z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-white tracking-wide">Real-time Reports</h4>
                        <p class="text-[10px] text-slate-400">Make better decisions</p>
                    </div>
                </div>
                <div class="flex gap-3 items-start">
                    <div class="p-2 bg-white/5 border border-white/10 rounded-lg text-blue-400 mt-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-white tracking-wide">Secure & Reliable</h4>
                        <p class="text-[10px] text-slate-400">Your data is protected</p>
                    </div>
                </div>
                <div class="flex gap-3 items-start">
                    <div class="p-2 bg-white/5 border border-white/10 rounded-lg text-blue-400 mt-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                            <line x1="8" y1="21" x2="16" y2="21"/>
                            <line x1="12" y1="17" x2="12" y2="21"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-white tracking-wide">Multi Device</h4>
                        <p class="text-[10px] text-slate-400">Works on all devices</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="lg:col-span-5 xl:col-span-4 flex flex-col justify-between p-6 sm:p-12 md:p-16 min-h-screen bg-white dark:bg-slate-900">
            <!-- Top Mobile Logo & App Title (Hidden on desktop) -->
            <div class="lg:hidden flex flex-col items-center text-center gap-2 mb-8">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-600 shadow-lg shadow-blue-500/30">
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
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Phone Shop POS</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Retail & Wholesale Management System</p>
                </div>
            </div>

            <div class="my-auto w-full max-w-md mx-auto">
                <!-- Login Greeting (Desktop Logo and Title) -->
                <div class="hidden lg:flex flex-col items-center text-center gap-4 mb-8">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-600 shadow-xl shadow-blue-500/30">
                        <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <rect x="2" y="3" width="20" height="12" rx="2" />
                            <path d="M12 15v4" />
                            <path d="M9 19h6" />
                            <path d="M19 8h-2l-1.5-2H8.5L7 8H5" />
                            <circle cx="10" cy="8" r="1" />
                            <circle cx="14" cy="8" r="1" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Welcome Back</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Please enter your credentials to log in.</p>
                    </div>
                </div>

                <!-- Secure Login Badge -->
                <div class="flex items-center justify-center gap-2 mb-6 py-2 px-3 bg-blue-50 dark:bg-blue-950/30 border border-blue-100/60 dark:border-blue-900/30 rounded-full w-fit mx-auto">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M2.166 4.9L10 1.154l7.834 3.746a1 1 0 0 1 .613.916v5.82a9 9 0 0 1-5.176 8.161l-3.003 1.44a1 1 0 0 1-.536 0l-3.003-1.44A9 9 0 0 1 1.553 11.63v-5.82a1 1 0 0 1 .613-.916zM10 3.12L3.5 6.23v4.613a7.001 7.001 0 0 0 4.148 6.398L10 18.337l2.352-1.096a7.001 7.001 0 0 0 4.148-6.398v-4.63L10 3.12zM10 7a1 1 0 0 1 1 1v3a1 1 0 1 1-2 0V8a1 1 0 0 1 1-1zm0 7a1 1 0 1 0 0-2 1 1 0 0 0 0 2z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-xs font-semibold text-blue-800 dark:text-blue-300 uppercase tracking-wider">Secure login for authorized users</span>
                </div>

                <!-- Session Status Alerts -->
                @if (session('status'))
                    <div class="p-4 mb-5 text-sm text-emerald-800 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/30" role="alert" data-test="session-status">
                        <div class="flex items-center gap-2 font-semibold">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>{{ session('status') }}</span>
                        </div>
                    </div>
                @endif

                <!-- Validation Errors Alerts -->
                @if ($errors->any())
                    <div class="p-4 mb-5 text-sm text-red-800 rounded-xl bg-red-50 dark:bg-red-950/40 dark:text-red-400 border border-red-100 dark:border-red-900/30" role="alert" data-test="validation-errors">
                        <div class="flex items-center gap-2 font-semibold mb-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <span>Please check the errors:</span>
                        </div>
                        <ul class="list-disc list-inside space-y-0.5 ml-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Login Form -->
                <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                    @csrf

                    <!-- Username / Email Input -->
                    <div>
                        <label for="email-input" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Username or Email</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
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
                                placeholder="Enter username or email" 
                                class="w-full pl-11 pr-4 py-3 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-slate-900 dark:text-white text-sm transition shadow-inner"
                            />
                        </div>
                    </div>

                    <!-- Password Input -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password-input" class="text-sm font-semibold text-slate-700 dark:text-slate-300">Password</label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:text-blue-700 hover:underline">
                                    Forgot password?
                                </a>
                            @endif
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
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
                                placeholder="Enter password" 
                                class="w-full pl-11 pr-11 py-3 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-slate-900 dark:text-white text-sm transition shadow-inner"
                            />
                            <!-- Show/Hide Password Icon Toggle Button -->
                            <button 
                                type="button" 
                                onclick="togglePasswordVisibility()" 
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-400 transition focus:outline-none"
                                title="Toggle Password Visibility"
                            >
                                <!-- Eye Open Icon -->
                                <svg id="eye-open-icon" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                <!-- Eye Closed Icon -->
                                <svg id="eye-closed-icon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                    <line x1="1" y1="1" x2="23" y2="23"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me Checkbox -->
                    <div class="flex items-center">
                        <input 
                            id="remember-checkbox" 
                            name="remember" 
                            type="checkbox" 
                            class="h-4.5 w-4.5 text-blue-600 focus:ring-blue-500 border-slate-300 dark:border-slate-700 rounded-md bg-slate-50 dark:bg-slate-800 transition"
                        />
                        <label for="remember-checkbox" class="ml-2.5 block text-sm font-semibold text-slate-700 dark:text-slate-300 select-none">
                            Remember me
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button 
                            id="login-btn"
                            type="submit" 
                            class="w-full py-3.5 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow-lg shadow-blue-500/20 hover:shadow-blue-500/30 transition duration-150 flex items-center justify-center gap-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 cursor-pointer"
                            data-test="login-button"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Log in</span>
                        </button>
                    </div>
                </form>

                <!-- Interactive Quick-Login Credentials Fill Card -->
                <div 
                    onclick="fillDemoCredentials()" 
                    class="mt-6 p-4 rounded-xl border border-blue-100 dark:border-blue-950 bg-blue-50/50 dark:bg-blue-950/20 hover:bg-blue-100/50 dark:hover:bg-blue-950/40 text-blue-800 dark:text-blue-300 transition duration-150 cursor-pointer group flex items-start gap-3 shadow-xs"
                    title="Click to automatically fill admin credentials"
                >
                    <div class="p-2 rounded-lg bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 group-hover:scale-105 transition mt-0.5">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path d="M15 7h3a5 5 0 0 1 5 5 5 5 0 0 1-5 5h-3m-6 0H6a5 5 0 0 1-5-5 5 5 0 0 1 5-5h3"/>
                            <line x1="8" y1="12" x2="16" y2="12"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wider text-blue-900 dark:text-blue-400">Quick-Fill Admin Login</div>
                        <div class="text-xs mt-1 text-blue-700/90 dark:text-blue-300/80 font-medium">
                            Username: <code class="bg-blue-100/60 dark:bg-blue-900/60 px-1.5 py-0.5 rounded font-mono font-bold">admin</code> &nbsp;&bull;&nbsp; Password: <code class="bg-blue-100/60 dark:bg-blue-900/60 px-1.5 py-0.5 rounded font-mono font-bold">password</code>
                        </div>
                        <div class="text-[10px] text-blue-500/80 dark:text-blue-400/70 mt-1.5 italic group-hover:underline">Click anywhere on this card to automatically fill credentials</div>
                    </div>
                </div>
            </div>

            <!-- Footer: Powered By with Env Info -->
            @php
                $devName = env('DEVELOPER_NAME', 'twinsofte.com');
                $devPhone = env('DEVELOPER_PHONE', '0762472822');
                $devWeb = env('DEVELOPER_WEB', 'twinsofte.com');
                
                // Add https scheme if not present in url
                $devUrl = $devWeb;
                if (!str_starts_with($devUrl, 'http://') && !str_starts_with($devUrl, 'https://')) {
                    $devUrl = 'https://' . $devUrl;
                }
            @endphp
            <div class="text-center text-xs text-slate-400 dark:text-slate-500 mt-8 border-t border-slate-100 dark:border-slate-800 pt-5">
                <span>Powered by </span>
                <a href="{{ $devUrl }}" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 font-semibold hover:underline hover:text-blue-700 dark:hover:text-blue-300 transition">
                    {{ $devName }}
                </a>
                <span class="mx-1">|</span>
                <span class="font-medium">{{ $devPhone }}</span>
            </div>
        </div>
    </div>

    <!-- Client-side Scripts -->
    <script>
        // Force light mode
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

        // Fill credentials for demo
        function fillDemoCredentials() {
            document.getElementById('email-input').value = 'admin';
            document.getElementById('password-input').value = 'password';
            
            // Set password input field back to password type and eye open visible (for default behavior)
            const passwordInput = document.getElementById('password-input');
            passwordInput.type = 'password';
            document.getElementById('eye-open-icon').classList.remove('hidden');
            document.getElementById('eye-closed-icon').classList.add('hidden');
            
            // Focus on the login submit button
            document.getElementById('login-btn').focus();
        }
    </script>
    @fluxScripts
</body>
</html>
