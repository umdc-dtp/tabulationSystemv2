<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <script>
            try {
                document.documentElement.dataset.sidebarCollapsed = localStorage.getItem('tabulation.sidebar.collapsed') === 'true' ? 'true' : 'false';
            } catch {}
        </script>

        <title>@yield('title', 'Admin') | Tabulation System</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-100 font-sans text-slate-900 antialiased">
        <div class="min-h-screen lg:flex">
            <aside id="admin-sidebar" class="admin-sidebar relative z-20 border-b border-slate-800 bg-slate-950 text-white lg:fixed lg:inset-y-0 lg:left-0 lg:flex lg:w-72 lg:flex-col lg:border-b-0 lg:border-r">
                <button id="sidebar-toggle" type="button" class="sidebar-toggle absolute right-0 top-8 z-30 hidden h-9 w-9 translate-x-1/2 place-items-center rounded-full border border-slate-700 bg-slate-950 text-slate-300 shadow-lg transition hover:border-maroon-500 hover:bg-maroon-800 hover:text-white focus:outline-none focus-visible:ring-4 focus-visible:ring-maroon-500/30 lg:grid" aria-controls="admin-sidebar" aria-expanded="true" aria-label="Collapse sidebar" title="Collapse sidebar">
                    <svg class="sidebar-toggle-icon h-4 w-4 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" aria-hidden="true">
                        <path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <span class="sr-only">Toggle sidebar</span>
                </button>

                <div class="sidebar-header flex items-center justify-between px-5 py-5 lg:px-7 lg:py-7">
                    <a href="{{ route('dashboard') }}" class="sidebar-brand flex items-center gap-3" title="Tabulation management portal">
                        <span class="grid h-11 w-11 place-items-center rounded-xl bg-maroon-600 text-white shadow-lg shadow-maroon-950/40">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M4 19V9m5 10V5m5 14v-7m5 7V3" stroke-linecap="round" />
                            </svg>
                        </span>
                        <span class="sidebar-brand-copy">
                            <span class="block text-xs font-semibold uppercase tracking-[0.18em] text-maroon-300">Tabulation</span>
                            <span class="block text-base font-semibold">Management Portal</span>
                        </span>
                    </a>

                    <span class="rounded-full bg-maroon-500/15 px-3 py-1 text-xs font-semibold text-maroon-300 lg:hidden">{{ auth()->user()->role->label() }}</span>
                </div>

                <nav class="admin-sidebar-nav overflow-x-auto px-4 pb-4 lg:flex-1 lg:overflow-visible lg:px-5 lg:pb-6" aria-label="Management navigation">
                    <p class="sidebar-section-title mb-3 hidden px-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 lg:block">Main menu</p>

                    <div class="flex min-w-max gap-2 lg:min-w-0 lg:flex-col">
                        <a href="{{ route('dashboard') }}" title="Home" @if(request()->routeIs('dashboard')) aria-current="page" @endif @class([
                            'admin-sidebar-link flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition',
                            'bg-maroon-600 text-white shadow-lg shadow-maroon-950/30' => request()->routeIs('dashboard'),
                            'text-slate-300 hover:bg-slate-900 hover:text-white' => ! request()->routeIs('dashboard'),
                        ])>
                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="m3 11 9-8 9 8" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M5 10v10h14V10M9 20v-6h6v6" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <span class="sidebar-label">Home</span>
                        </a>

                        <a href="{{ route('events.index') }}" title="Events" @if(request()->routeIs('events.*')) aria-current="page" @endif @class([
                            'admin-sidebar-link flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition',
                            'bg-maroon-600 text-white shadow-lg shadow-maroon-950/30' => request()->routeIs('events.*'),
                            'text-slate-300 hover:bg-slate-900 hover:text-white' => ! request()->routeIs('events.*'),
                        ])>
                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <rect x="3" y="5" width="18" height="16" rx="2" />
                                <path d="M16 3v4M8 3v4M3 10h18" stroke-linecap="round" />
                            </svg>
                            <span class="sidebar-label">Events</span>
                        </a>

                        @if (auth()->user()->isAdmin())
                            <a href="{{ route('users.index') }}" title="Users" @if(request()->routeIs('users.*')) aria-current="page" @endif @class([
                                'admin-sidebar-link flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition',
                                'bg-maroon-600 text-white shadow-lg shadow-maroon-950/30' => request()->routeIs('users.*'),
                                'text-slate-300 hover:bg-slate-900 hover:text-white' => ! request()->routeIs('users.*'),
                            ])>
                                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke-linecap="round" />
                                    <circle cx="9" cy="7" r="4" />
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke-linecap="round" />
                                </svg>
                                <span class="sidebar-label">Users</span>
                            </a>
                        @endif

                        @if (auth()->user()->isAdmin())
                            <a href="{{ route('logs.index') }}" title="Logs" @if(request()->routeIs('logs.*')) aria-current="page" @endif @class([
                                'admin-sidebar-link flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition',
                                'bg-maroon-600 text-white shadow-lg shadow-maroon-950/30' => request()->routeIs('logs.*'),
                                'text-slate-300 hover:bg-slate-900 hover:text-white' => ! request()->routeIs('logs.*'),
                            ])>
                                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M9 5h10M9 12h10M9 19h10M5 5h.01M5 12h.01M5 19h.01" stroke-linecap="round" />
                                </svg>
                                <span class="sidebar-label">Logs</span>
                            </a>

                            <a href="#" title="Settings" class="admin-sidebar-link flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-slate-300 transition hover:bg-slate-900 hover:text-white">
                                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <circle cx="12" cy="12" r="3" />
                                    <path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21h-4v-.09A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.6-1H3v-4h.09A1.7 1.7 0 0 0 4.6 9a1.7 1.7 0 0 0-.34-1.88l-.06-.06 2.83-2.83.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-1.6V3h4v.09A1.7 1.7 0 0 0 15 4.6a1.7 1.7 0 0 0 1.88-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.4 9a1.7 1.7 0 0 0 1.6 1h.09v4H21a1.7 1.7 0 0 0-1.6 1Z" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <span class="sidebar-label">Settings</span>
                            </a>
                        @endif
                    </div>
                </nav>

                <div class="sidebar-footer hidden border-t border-slate-800 p-5 lg:block">
                    <div class="sidebar-user-card mb-4 flex items-center gap-3 rounded-xl bg-slate-900 p-3">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-maroon-500/15 text-sm font-bold text-maroon-300">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </span>
                        <span class="sidebar-user-copy min-w-0">
                            <span class="block truncate text-sm font-semibold">{{ auth()->user()->name }}</span>
                            <span class="block text-xs text-slate-400">{{ auth()->user()->role->label() }}</span>
                        </span>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" title="Sign out" class="admin-sidebar-link flex w-full items-center justify-center gap-2 rounded-xl border border-slate-700 px-4 py-2.5 text-sm font-semibold text-slate-300 transition hover:border-slate-600 hover:bg-slate-900 hover:text-white">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M10 17l5-5-5-5M15 12H3M21 19V5a2 2 0 0 0-2-2h-6" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <span class="sidebar-label">Sign out</span>
                        </button>
                    </form>
                </div>
            </aside>

            <div class="admin-shell-content min-w-0 flex-1 lg:ml-72">
                <header class="border-b border-slate-200 bg-white">
                    <div class="flex items-center justify-between px-5 py-4 sm:px-8 lg:px-10">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-maroon-700">Management dashboard</p>
                            <h1 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">@yield('heading', 'Home')</h1>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="hidden text-right sm:block">
                                <span class="block text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</span>
                                <span class="block text-xs text-slate-500">{{ auth()->user()->role->label() }}</span>
                            </span>

                            <form method="POST" action="{{ route('logout') }}" class="lg:hidden">
                                @csrf
                                <button type="submit" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Sign out</button>
                            </form>
                        </div>
                    </div>
                </header>

                <main class="px-5 py-8 sm:px-8 lg:px-10 lg:py-10">
                    @if (session('status'))
                        <div class="mb-6 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                            <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="m5 12 4 4L19 6" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            {{ session('status') }}
                        </div>
                    @endif

                    @yield('content')
                </main>
            </div>
        </div>

        <x-loading-indicator />
        @stack('scripts')
    </body>
</html>
