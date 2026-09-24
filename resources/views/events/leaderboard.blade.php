@extends('layouts.admin')

@section('title', 'Leaderboard · '.$event->name)
@section('heading', 'Current leaderboard')

@section('content')
    <nav class="mb-6 flex items-center gap-2 text-sm text-slate-500" aria-label="Breadcrumb">
        <a href="{{ route('events.index') }}" class="font-medium transition hover:text-maroon-700">Events</a>
        <span>/</span>
        <a href="{{ route('events.show', $event) }}" class="font-medium transition hover:text-maroon-700">{{ $event->name }}</a>
        <span>/</span>
        <span class="font-medium text-slate-800">Leaderboard</span>
    </nav>

    <section class="overflow-hidden rounded-2xl bg-gradient-to-br from-maroon-700 to-maroon-950 text-white shadow-xl shadow-maroon-950/10">
        <div class="flex flex-col gap-6 p-7 sm:p-9 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-maroon-100 ring-1 ring-inset ring-white/15">Current scoring</span>
                    <span @class([
                        'rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset',
                        'bg-amber-400/15 text-amber-200 ring-amber-300/20' => $event->leaderboard_frozen,
                        'bg-emerald-400/15 text-emerald-200 ring-emerald-300/20' => ! $event->leaderboard_frozen,
                    ])>Public leaderboard {{ $event->leaderboard_frozen ? 'frozen' : 'live' }}</span>
                </div>
                <h2 class="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">{{ $event->name }}</h2>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-maroon-100/80">See each contest's current ranking and the overall standings calculated from contest wins.</p>
            </div>
            <a href="{{ route('events.show', $event) }}" class="inline-flex h-11 items-center justify-center rounded-xl bg-white px-5 text-sm font-semibold text-maroon-800 transition hover:bg-maroon-50">Back to event</a>
        </div>
    </section>

    <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-5">
            <p class="text-sm font-semibold text-maroon-700">Overall winner</p>
            <h3 class="mt-1 text-xl font-semibold text-slate-950">Overall standings by contest wins</h3>
            <p class="mt-1 text-sm text-slate-500">First-place finishes are summed across contests. Recorded match wins and leaderboard points break ties.</p>
        </div>

        @if ($overallStandings->isNotEmpty())
            @php($winner = $overallStandings->first())
            <div class="border-b border-maroon-100 bg-maroon-50/60 px-6 py-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <span class="grid h-12 w-12 place-items-center rounded-xl bg-maroon-700 text-lg font-bold text-white">1</span>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-maroon-600">Current overall leader</p>
                            <p class="mt-1 text-xl font-semibold text-maroon-950">{{ $winner['participant']->name }}</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-3 text-sm">
                        <span class="rounded-xl bg-white px-4 py-2 font-semibold text-maroon-900 shadow-sm">{{ $winner['contest_wins'] }} {{ Str::plural('contest win', $winner['contest_wins']) }}</span>
                        <span class="rounded-xl bg-white px-4 py-2 font-semibold text-slate-700 shadow-sm">{{ number_format($winner['leaderboard_points'], 2) }} pts</span>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-6 py-3.5 font-semibold">Rank</th>
                            <th class="px-5 py-3.5 font-semibold">Participant</th>
                            <th class="px-5 py-3.5 text-center font-semibold">Contest wins</th>
                            <th class="px-5 py-3.5 text-center font-semibold">Recorded wins</th>
                            <th class="px-5 py-3.5 text-center font-semibold">Contests entered</th>
                            <th class="px-6 py-3.5 text-right font-semibold">Leaderboard points</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($overallStandings as $index => $row)
                            <tr @class(['bg-maroon-50/30' => $index === 0])>
                                <td class="px-6 py-4 font-bold text-slate-700">#{{ $index + 1 }}</td>
                                <td class="px-5 py-4 font-semibold text-slate-900">{{ $row['participant']->name }}</td>
                                <td class="px-5 py-4 text-center font-semibold text-maroon-700">{{ $row['contest_wins'] }}</td>
                                <td class="px-5 py-4 text-center text-slate-600">{{ $row['recorded_wins'] }}</td>
                                <td class="px-5 py-4 text-center text-slate-600">{{ $row['contests_entered'] }}</td>
                                <td class="px-6 py-4 text-right font-semibold text-slate-700">{{ number_format($row['leaderboard_points'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-6 py-14 text-center">
                <p class="font-semibold text-slate-700">No scores have been entered yet.</p>
                <p class="mt-1 text-sm text-slate-400">Use a contest's Enter scores button to begin building the leaderboard.</p>
            </div>
        @endif
    </section>

    <div class="mt-6 space-y-6">
        @forelse ($event->categories as $category)
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
                    <h3 class="text-lg font-semibold text-slate-950">{{ $category->name }}</h3>
                    <p class="mt-1 text-sm text-slate-500">Current scores for {{ $category->competitions->count() }} {{ Str::plural('contest', $category->competitions->count()) }}.</p>
                </div>

                <div class="grid gap-5 p-5 xl:grid-cols-2">
                    @forelse ($category->competitions as $competition)
                        @php($standings = $standingsByCompetition[$competition->id])
                        <article class="overflow-hidden rounded-xl border border-slate-200">
                            <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                                <div>
                                    <h4 class="font-semibold text-slate-900">{{ $competition->name }}</h4>
                                    <p class="mt-0.5 text-xs text-slate-400">{{ $competition->scoring_method->label() }}</p>
                                </div>
                                <a href="{{ route('events.categories.competitions.scores.edit', [$event, $category, $competition]) }}" class="shrink-0 text-xs font-semibold text-maroon-700 hover:text-maroon-900">Enter scores</a>
                            </div>

                            @if ($standings->isNotEmpty())
                                <div class="divide-y divide-slate-100">
                                    @foreach ($standings as $row)
                                        <div class="grid grid-cols-[2.5rem_minmax(0,1fr)_auto] items-center gap-3 px-5 py-3">
                                            <span class="font-bold text-slate-500">#{{ $row['rank'] }}</span>
                                            <span class="min-w-0">
                                                <span class="block truncate font-medium text-slate-800">{{ $row['participant']->name }}</span>
                                                <span class="block text-xs text-slate-400">
                                                    @if ($row['deduction'] > 0)
                                                        {{ number_format($row['gross_score'], 2) }} − {{ number_format($row['deduction'], 2) }} deduction = {{ number_format($row['raw_score'], 2) }}
                                                    @elseif ($competition->usesCriteriaScoring())
                                                        {{ number_format($row['raw_score'], 2) }} score
                                                    @else
                                                        {{ $row['wins'] }} {{ Str::plural('win', $row['wins']) }}
                                                    @endif
                                                </span>
                                            </span>
                                            <span class="font-semibold text-maroon-700">{{ number_format($row['leaderboard_points'], 2) }} pts</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="px-5 py-8 text-center text-sm text-slate-400">No scores entered.</p>
                            @endif
                        </article>
                    @empty
                        <p class="text-sm text-slate-400">No contests in this category.</p>
                    @endforelse
                </div>
            </section>
        @empty
            <section class="rounded-2xl border border-slate-200 bg-white px-6 py-14 text-center shadow-sm">
                <p class="font-semibold text-slate-700">No categories or contests configured.</p>
            </section>
        @endforelse
    </div>
@endsection
