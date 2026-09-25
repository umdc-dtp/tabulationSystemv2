<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Sign in | Tabulation System</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-950 font-sans text-slate-900 antialiased">
        <main class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-10 sm:px-6">
            <div class="absolute inset-0" aria-hidden="true">
                <div class="absolute -left-32 top-1/4 h-80 w-80 rounded-full bg-maroon-600/20 blur-3xl"></div>
                <div class="absolute -right-28 bottom-1/4 h-72 w-72 rounded-full bg-maroon-400/10 blur-3xl"></div>
                <div class="absolute inset-0 opacity-[0.035] [background-image:linear-gradient(rgba(255,255,255,.8)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.8)_1px,transparent_1px)] [background-size:48px_48px]"></div>
            </div>

            <section class="relative grid w-full max-w-5xl overflow-hidden rounded-3xl bg-white shadow-2xl shadow-black/40 lg:grid-cols-[1.05fr_.95fr]">
                <div class="relative hidden min-h-[650px] overflow-hidden bg-maroon-700 p-12 text-white lg:flex lg:flex-col lg:justify-between">
                    <div class="absolute inset-0 bg-gradient-to-br from-maroon-600 to-slate-950"></div>
                    <div class="absolute -right-24 top-20 h-72 w-72 rounded-full border border-white/10"></div>
                    <div class="absolute -right-8 top-36 h-72 w-72 rounded-full border border-white/10"></div>

                    <div class="relative flex items-center gap-3">
                        <div class=" grid h-11 w-11 place-items-center rounded-xl bg-white text-maroon-700 shadow-lg shadow-maroon-950/20">
                            <!-- <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M4 19V9m5 10V5m5 14v-7m5 7V3" stroke-linecap="round" />
                            </svg> -->
                            <img src="{{asset('images/um_logo.png')}}" alt="">
                            <!-- <img src="{{asset('images/pgits_logo.png')}}" alt=""> -->
                        </div>
                        <div>
                            <!-- <p class="text-xs font-semibold uppercase tracking-[0.22em] text-maroon-200">Event management</p> -->
                            <p class="text-lg font-semibold">Tabulation System</p>
                        </div>
                    </div>

                    <div class="relative max-w-md">
                        <p class="mb-4 text-sm font-semibold uppercase tracking-[0.24em] text-maroon-200">Accurate. Fast. Reliable.</p>
                        <h1 class="text-4xl font-semibold leading-tight tracking-tight">Every score accounted for, every result ready.</h1>
                        <p class="mt-5 max-w-sm text-base leading-7 text-maroon-100/80">A focused workspace for managing scores and producing dependable competition results.</p>
                    </div>

                    <p class="relative text-sm text-maroon-200/70">Developed and Maintained by PGITS | Programmers Guild of Information Technology Students ©</p>
                </div>

                <div class="flex items-center px-6 py-12 sm:px-12 lg:px-14">
                    <div class="mx-auto w-full max-w-sm">
                        <div class="mb-9 lg:hidden">
                            <div class="mb-4 grid h-11 w-11 place-items-center rounded-xl bg-maroon-700 text-white">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M4 19V9m5 10V5m5 14v-7m5 7V3" stroke-linecap="round" />
                                </svg>
                            </div>
                            <p class="font-semibold text-maroon-700">Tabulation System</p>
                        </div>

                        <div class="mb-8">
                            <p class="mb-2 text-sm font-semibold text-maroon-700">Welcome back</p>
                            <h2 class="text-3xl font-semibold tracking-tight text-slate-950">Sign in to continue</h2>
                            <p class="mt-3 text-sm leading-6 text-slate-500">Enter the account details provided by your administrator.</p>
                        </div>

                        <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                            @csrf

                            <div>
                                <label for="username" class="mb-2 block text-sm font-semibold text-slate-700">Username</label>
                                <input
                                    id="username"
                                    name="username"
                                    type="text"
                                    value="{{ old('username') }}"
                                    autocomplete="username"
                                    required
                                    autofocus
                                    class="block h-12 w-full rounded-xl border bg-white px-4 text-sm text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10 {{ $errors->has('username') ? 'border-red-400' : 'border-slate-300' }}"
                                    placeholder="Enter your username"
                                    aria-describedby="username-error"
                                >
                                @error('username')
                                    <p id="username-error" class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">Password</label>
                                <div class="relative">
                                    <input
                                        id="password"
                                        name="password"
                                        type="password"
                                        autocomplete="current-password"
                                        required
                                        class="block h-12 w-full rounded-xl border bg-white px-4 pr-20 text-sm text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10 {{ $errors->has('password') ? 'border-red-400' : 'border-slate-300' }}"
                                        placeholder="Enter your password"
                                        aria-describedby="password-error"
                                    >
                                    <button
                                        id="toggle-password"
                                        type="button"
                                        class="absolute inset-y-0 right-0 flex items-center px-4 text-sm font-semibold text-slate-500 transition hover:text-maroon-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-maroon-600"
                                        aria-controls="password"
                                        aria-pressed="false"
                                    >Show</button>
                                </div>
                                @error('password')
                                    <p id="password-error" class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit" class="flex h-12 w-full items-center justify-center rounded-xl bg-maroon-700 px-5 text-sm font-semibold text-white shadow-lg shadow-maroon-700/20 transition hover:bg-maroon-800 focus:outline-none focus-visible:ring-4 focus-visible:ring-maroon-600/25 active:translate-y-px">
                                Sign in
                            </button>
                        </form>

                        <p class="mt-8 text-center text-xs leading-5 text-slate-400">Having trouble signing in? Contact your system administrator.</p>
                    </div>
                </div>
            </section>
        </main>

        <x-loading-indicator />
        <script>
            const togglePassword = document.getElementById('toggle-password');
            const password = document.getElementById('password');

            togglePassword.addEventListener('click', () => {
                const isHidden = password.type === 'password';

                password.type = isHidden ? 'text' : 'password';
                togglePassword.textContent = isHidden ? 'Hide' : 'Show';
                togglePassword.setAttribute('aria-pressed', String(isHidden));
            });
        </script>
    </body>
</html>
