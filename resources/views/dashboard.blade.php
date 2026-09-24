@extends('layouts.admin')

@section('title', 'Dashboard')
@section('heading', 'Home')

@section('content')
    <section class="overflow-hidden rounded-2xl bg-gradient-to-br from-maroon-700 to-maroon-950 text-white shadow-xl shadow-maroon-950/10">
        <div class="flex flex-col gap-7 p-7 sm:p-9 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm font-semibold text-maroon-200">Welcome back</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">{{ auth()->user()->name }}</h2>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-maroon-100/80">Monitor scoring, finalize contest results, and open current leaderboards from one place.</p>
                <div class="mt-5 flex flex-wrap gap-3">
                    <a href="{{ route('events.index') }}" class="inline-flex h-10 items-center rounded-xl bg-white px-4 text-sm font-semibold text-maroon-800 transition hover:bg-maroon-50">Manage events</a>
                    <a href="{{ route('logs.index') }}" class="inline-flex h-10 items-center rounded-xl border border-white/25 px-4 text-sm font-semibold text-white transition hover:bg-white/10">View activity logs</a>
                </div>
            </div>

            <div class="flex items-center gap-5 rounded-2xl border border-white/15 bg-white/10 p-5 backdrop-blur-sm">
                <div class="relative h-28 w-28 shrink-0" role="img" aria-label="{{ $overall_percentage }} percent of events finalized">
                    <svg class="h-full w-full -rotate-90" viewBox="0 0 42 42" aria-hidden="true">
                        <circle cx="21" cy="21" r="16" fill="none" stroke="currentColor" stroke-width="4" class="text-white/15" />
                        <circle cx="21" cy="21" r="16" fill="none" stroke="currentColor" stroke-width="4" pathLength="100" stroke-dasharray="{{ $overall_percentage }} 100" stroke-linecap="round" class="text-white" />
                    </svg>
                    <span class="absolute inset-0 grid place-items-center text-xl font-bold">{{ $overall_percentage }}%</span>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-maroon-200">Overall finalization</p>
                    <p class="mt-1 text-2xl font-semibold">{{ $finalized_events }} of {{ $total_events }}</p>
                    <p class="mt-1 max-w-40 text-xs leading-5 text-maroon-100/75">Events with every contest score sheet finalized.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Dashboard summary">
        @foreach ([
            ['label' => 'Active events', 'value' => $active_events, 'caption' => $total_events.' total events', 'color' => 'text-emerald-700 bg-emerald-50'],
            ['label' => 'Finalized events', 'value' => $finalized_events, 'caption' => 'All contest scores completed', 'color' => 'text-maroon-700 bg-maroon-50'],
            ['label' => 'Registered users', 'value' => $registered_users, 'caption' => 'Admin and auditor accounts', 'color' => 'text-violet-700 bg-violet-50'],
            ['label' => 'Recent activity', 'value' => $recent_activity_count, 'caption' => 'Changes and logins in 24 hours', 'color' => 'text-amber-700 bg-amber-50'],
        ] as $summary)
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-medium text-slate-500">{{ $summary['label'] }}</p>
                    <span class="grid h-9 w-9 place-items-center rounded-lg {{ $summary['color'] }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 19V9m6 10V5m6 14v-7m4 7H2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-semibold text-slate-950">{{ number_format($summary['value']) }}</p>
                <p class="mt-2 text-xs text-slate-400">{{ $summary['caption'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-slate-950">Event scoring progress</h3>
                <p class="mt-1 text-sm text-slate-500">Each circular bar shows finalized contest score sheets over total contests.</p>
            </div>
            <a href="{{ route('events.index') }}" class="text-sm font-semibold text-maroon-700 hover:text-maroon-900">View all events</a>
        </div>

        @if ($events->isNotEmpty())
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($events as $progress)
                    @php
                        $event = $progress['event'];
                        $statusClasses = match ($progress['status']) {
                            'Active' => 'bg-emerald-50 text-emerald-700',
                            'Upcoming' => 'bg-maroon-50 text-maroon-700',
                            default => 'bg-slate-100 text-slate-600',
                        };
                    @endphp
                    <article class="flex items-center gap-5 rounded-2xl border border-slate-200 p-5 transition hover:border-maroon-300 hover:shadow-lg hover:shadow-maroon-950/5">
                        <div class="relative h-24 w-24 shrink-0" role="img" aria-label="{{ $progress['percentage'] }} percent finalized">
                            <svg class="h-full w-full -rotate-90" viewBox="0 0 42 42" aria-hidden="true">
                                <circle cx="21" cy="21" r="16" fill="none" stroke="currentColor" stroke-width="4" class="text-slate-100" />
                                <circle cx="21" cy="21" r="16" fill="none" stroke="currentColor" stroke-width="4" pathLength="100" stroke-dasharray="{{ $progress['percentage'] }} 100" stroke-linecap="round" class="text-maroon-700" />
                            </svg>
                            <span class="absolute inset-0 grid place-items-center text-lg font-bold text-slate-900">{{ $progress['percentage'] }}%</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $statusClasses }}">{{ $progress['status'] }}</span>
                                @if ($progress['percentage'] === 100)
                                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700">Finalized</span>
                                @endif
                            </div>
                            <h4 class="mt-2 truncate font-semibold text-slate-950">{{ $event->name }}</h4>
                            <p class="mt-1 text-xs text-slate-400">{{ $progress['finalized'] }} of {{ $progress['total'] }} {{ Str::plural('contest', $progress['total']) }} finalized</p>
                            <div class="mt-3 flex flex-wrap gap-x-4 gap-y-2 text-xs font-semibold">
                                <a href="{{ route('events.show', $event) }}" class="text-maroon-700 hover:text-maroon-900">Manage event</a>
                                <a href="{{ route('events.leaderboard', $event) }}" class="text-slate-600 hover:text-slate-900">Leaderboard</a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="px-6 py-14 text-center">
                <p class="font-semibold text-slate-700">No events yet</p>
                <p class="mt-1 text-sm text-slate-400">Create an event to begin tracking scoring progress.</p>
                <a href="{{ route('events.index') }}" class="mt-4 inline-flex h-10 items-center rounded-xl bg-maroon-700 px-4 text-sm font-semibold text-white">Create an event</a>
            </div>
        @endif
    </section>

    <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-6 py-5">
            <div>
                <h3 class="text-lg font-semibold text-slate-950">Latest activity</h3>
                <p class="mt-1 text-sm text-slate-500">Recent data changes and sign-ins.</p>
            </div>
            <a href="{{ route('logs.index') }}" class="text-sm font-semibold text-maroon-700 hover:text-maroon-900">Open logs</a>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse ($recent_logs as $log)
                <div class="flex flex-col gap-2 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-800">{{ $log->description }}</p>
                        <p class="mt-1 text-xs text-slate-400">{{ $log->actor_name ?? $log->actor_username ?? 'Guest' }} · {{ $log->created_at->diffForHumans() }}</p>
                    </div>
                    <span class="w-fit rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-slate-600">{{ $log->interaction_type }}</span>
                </div>
            @empty
                <p class="px-6 py-10 text-center text-sm text-slate-400">No activity has been recorded yet.</p>
            @endforelse
        </div>
    </section>
@endsection
