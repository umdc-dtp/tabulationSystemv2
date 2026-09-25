@extends('layouts.admin')

@section('title', 'Auditor Dashboard')
@section('heading', 'Auditor workspace')

@section('content')
    <section class="overflow-hidden rounded-2xl bg-gradient-to-br from-maroon-700 to-maroon-950 text-white shadow-xl shadow-maroon-950/10">
        <div class="flex flex-col gap-6 p-7 sm:p-9 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold text-maroon-200">Event operations</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Welcome, {{ auth()->user()->name }}</h2>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-maroon-100/80">Manage active events, participants, categories, contests, and participant scoring.</p>
            </div>
            <a href="{{ route('events.index') }}" class="inline-flex h-11 items-center justify-center rounded-xl bg-white px-5 text-sm font-semibold text-maroon-800 transition hover:bg-maroon-50">Open all events</a>
        </div>
    </section>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Current active event statistics">
        @foreach ([
            ['label' => 'Active events', 'value' => $event_count, 'caption' => 'Currently within event dates'],
            ['label' => 'Participants', 'value' => $participants_count, 'caption' => 'Across active events'],
            ['label' => 'Categories', 'value' => $categories_count, 'caption' => 'Across active events'],
            ['label' => 'Contests', 'value' => $competitions_count, 'caption' => $frozen_count.' frozen '.Str::plural('leaderboard', $frozen_count)],
        ] as $stat)
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">{{ $stat['label'] }}</p>
                <p class="mt-3 text-3xl font-semibold text-slate-950">{{ number_format($stat['value']) }}</p>
                <p class="mt-2 text-xs text-slate-400">{{ $stat['caption'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-5">
            <h3 class="text-lg font-semibold text-slate-950">Current active events</h3>
            <p class="mt-1 text-sm text-slate-500">Use the event workspace to add participants, categories, contests, and participant scores.</p>
        </div>

        @if ($events->isNotEmpty())
            <div class="grid gap-5 p-5 xl:grid-cols-2">
                @foreach ($events as $progress)
                    @php($event = $progress['event'])
                    <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex flex-col gap-5 p-5 sm:flex-row sm:items-center">
                            <div class="relative h-28 w-28 shrink-0" role="img" aria-label="{{ $progress['percentage'] }} percent of contest scores finalized">
                                <svg class="h-full w-full -rotate-90" viewBox="0 0 42 42" aria-hidden="true">
                                    <circle cx="21" cy="21" r="16" fill="none" stroke="currentColor" stroke-width="4" class="text-slate-100" />
                                    <circle cx="21" cy="21" r="16" fill="none" stroke="currentColor" stroke-width="4" pathLength="100" stroke-dasharray="{{ $progress['percentage'] }} 100" stroke-linecap="round" class="text-maroon-700" />
                                </svg>
                                <span class="absolute inset-0 grid place-items-center text-xl font-bold text-slate-900">{{ $progress['percentage'] }}%</span>
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-emerald-700">Active</span>
                                    <span @class([
                                        'rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide',
                                        'bg-amber-50 text-amber-700' => $event->leaderboard_frozen,
                                        'bg-sky-50 text-sky-700' => ! $event->leaderboard_frozen,
                                    ])>Leaderboard {{ $event->leaderboard_frozen ? 'frozen' : 'live' }}</span>
                                </div>
                                <h4 class="mt-3 truncate text-xl font-semibold text-slate-950">{{ $event->name }}</h4>
                                <p class="mt-1 text-sm text-slate-500">{{ $event->start_date->format('M j') }} – {{ $event->end_date->format('M j, Y') }}</p>
                                <p class="mt-2 text-xs text-slate-400">{{ $progress['finalized'] }} of {{ $progress['total'] }} {{ Str::plural('contest', $progress['total']) }} finalized · {{ $event->participants_count }} {{ Str::plural('participant', $event->participants_count) }}</p>
                            </div>
                        </div>

                        <div class="grid gap-2 border-t border-slate-200 bg-slate-50 p-4 sm:grid-cols-2">
                            <a href="{{ route('events.show', $event) }}" class="inline-flex h-10 items-center justify-center rounded-lg bg-maroon-700 px-3 text-sm font-semibold text-white transition hover:bg-maroon-800">Manage event</a>
                            <a href="{{ route('events.leaderboard', $event) }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Leaderboard</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="px-6 py-14 text-center">
                <span class="mx-auto grid h-12 w-12 place-items-center rounded-xl bg-slate-100 text-slate-400">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 0 1 2 2v15H3V6a2 2 0 0 1 2-2Z" stroke-linecap="round" stroke-linejoin="round" /></svg>
                </span>
                <p class="mt-4 font-semibold text-slate-700">No active event today</p>
                <p class="mt-1 text-sm text-slate-400">You can still open Events to prepare upcoming events or review completed ones.</p>
                <a href="{{ route('events.index') }}" class="mt-4 inline-flex h-10 items-center rounded-xl bg-maroon-700 px-4 text-sm font-semibold text-white">Browse events</a>
            </div>
        @endif
    </section>

    <section class="mt-6 rounded-2xl border border-maroon-200 bg-maroon-50/60 p-6">
        <h3 class="font-semibold text-maroon-950">Auditor access</h3>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-maroon-900/75">You can manage event participants, categories, contests, contest configuration, participant scores, score finalization, and view leaderboards. Event creation and deletion, leaderboard freezing, users, logs, and settings remain administrator-only.</p>
    </section>
@endsection
