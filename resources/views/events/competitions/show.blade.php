@extends('layouts.admin')

@section('title', $competition->name)
@section('heading', 'Contest configuration')

@section('content')
    <nav class="mb-6 flex flex-wrap items-center gap-2 text-sm text-slate-500" aria-label="Breadcrumb">
        <a href="{{ route('events.index') }}" class="font-medium transition hover:text-maroon-700">Events</a>
        <span>/</span>
        <a href="{{ route('events.show', $event) }}" class="font-medium transition hover:text-maroon-700">{{ $event->name }}</a>
        <span>/</span>
        <span>{{ $category->name }}</span>
        <span>/</span>
        <span class="font-medium text-slate-800">{{ $competition->name }}</span>
    </nav>

    <section class="rounded-2xl bg-gradient-to-br from-maroon-700 to-maroon-950 p-7 text-white shadow-xl shadow-maroon-950/10 sm:p-9">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-maroon-200">{{ $category->name }}</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight">{{ $competition->name }}</h2>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-maroon-100/80">{{ $competition->scoring_method->description() }} Configure the points awarded to each final rank.</p>
            </div>
            <a href="{{ route('events.show', $event) }}" class="inline-flex h-10 items-center justify-center rounded-xl bg-white px-4 text-sm font-semibold text-maroon-800 transition hover:bg-maroon-50">Back to event</a>
        </div>
    </section>

    <section class="mt-6 rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-5">
            <h3 class="text-lg font-semibold text-slate-950">Winner determination</h3>
            <p class="mt-1 text-sm text-slate-500">Choose how winners are determined for this contest.</p>
        </div>

        <form method="POST" action="{{ route('events.categories.competitions.scoring-method.update', [$event, $category, $competition]) }}" class="p-5">
            @csrf
            @method('PATCH')

            <div class="grid gap-3 md:grid-cols-2">
                @foreach (\App\Enums\CompetitionScoringMethod::cases() as $method)
                    <label @class([
                        'relative flex cursor-pointer gap-3 rounded-xl border p-4 transition',
                        'border-maroon-600 bg-maroon-50 ring-2 ring-maroon-600/10' => old('scoring_method', $competition->scoring_method->value) === $method->value,
                        'border-slate-200 hover:border-maroon-300 hover:bg-maroon-50/40' => old('scoring_method', $competition->scoring_method->value) !== $method->value,
                    ])>
                        <input name="scoring_method" type="radio" value="{{ $method->value }}" @checked(old('scoring_method', $competition->scoring_method->value) === $method->value) class="mt-1 border-slate-300 text-maroon-700 focus:ring-maroon-600">
                        <span>
                            <span class="block text-sm font-semibold text-slate-900">{{ $method->label() }}</span>
                            <span class="mt-1 block text-xs leading-5 text-slate-500">{{ $method->description() }}</span>
                        </span>
                    </label>
                @endforeach
            </div>

            @error('scoring_method')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror

            <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs text-slate-500">
                    @if ($competition->usesCriteriaScoring())
                        Criteria configuration is active for this contest.
                    @else
                        Wins-based standings are active. Existing criteria are preserved but paused.
                    @endif
                </p>
                <button type="submit" class="h-11 rounded-xl bg-maroon-700 px-5 text-sm font-semibold text-white transition hover:bg-maroon-800">Save scoring method</button>
            </div>
        </form>
    </section>

    <div @class(['mt-6 grid gap-6', 'xl:grid-cols-2' => $competition->usesCriteriaScoring()])>
        @if ($competition->usesCriteriaScoring())
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-950">Judging criteria</h3>
                        <p class="mt-1 text-sm text-slate-500">Define the items judges will score.</p>
                    </div>
                    <span class="rounded-full bg-maroon-50 px-2.5 py-1 text-xs font-semibold text-maroon-700">{{ $competition->criteria->count() }}</span>
                </div>
            </div>

            <form method="POST" action="{{ route('events.categories.competitions.criteria.store', [$event, $category, $competition]) }}" class="grid gap-3 border-b border-slate-200 bg-slate-50/70 p-5 sm:grid-cols-[1fr_9rem_auto]">
                @csrf
                <div>
                    <label for="criterion_name" class="sr-only">Criterion name</label>
                    <input id="criterion_name" name="criterion_name" type="text" value="{{ old('criterion_name') }}" required class="h-11 w-full rounded-xl border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10" placeholder="e.g. Technique">
                    @error('criterion_name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="max_score" class="sr-only">Maximum score</label>
                    <input id="max_score" name="max_score" type="number" min="0.01" max="999999.99" step="0.01" value="{{ old('max_score', 100) }}" required class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none transition focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10" placeholder="Max score">
                    @error('max_score')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="h-11 rounded-xl bg-maroon-700 px-4 text-sm font-semibold text-white transition hover:bg-maroon-800">Add</button>
            </form>

            <div class="divide-y divide-slate-100">
                @forelse ($competition->criteria as $criterion)
                    <div class="flex items-center justify-between gap-4 px-6 py-4">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $criterion->name }}</p>
                            <p class="mt-0.5 text-xs text-slate-400">Maximum score: {{ number_format((float) $criterion->max_score, 2) }}</p>
                        </div>
                        <form method="POST" action="{{ route('events.categories.competitions.criteria.destroy', [$event, $category, $competition, $criterion]) }}" onsubmit="return confirm('Delete this criterion?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-lg p-2 text-slate-400 transition hover:bg-red-50 hover:text-red-700" aria-label="Delete {{ $criterion->name }}">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M3 6h18M8 6V4h8v2M19 6l-1 15H6L5 6" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="px-6 py-10 text-center text-sm text-slate-400">No judging criteria configured.</p>
                @endforelse
            </div>
        </section>
        @else
            <section class="rounded-2xl border border-maroon-200 bg-maroon-50/60 p-6 shadow-sm">
                <div class="flex items-start gap-4">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-maroon-700 text-white">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M8 21h8M12 17v4M7 4h10v4a5 5 0 0 1-10 0V4Z" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M7 6H4v1a4 4 0 0 0 4 4M17 6h3v1a4 4 0 0 1-4 4" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-lg font-semibold text-maroon-950">Wins-based scoring active</h3>
                        <p class="mt-1 text-sm leading-6 text-maroon-900/70">Participant or team standings will be ordered by total wins. Judging criteria are not used in this mode.</p>
                    </div>
                </div>
            </section>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-950">Leaderboard points per rank</h3>
                        <p class="mt-1 text-sm text-slate-500">Set the event leaderboard points awarded to each final placement.</p>
                    </div>
                    <span class="rounded-full bg-maroon-50 px-2.5 py-1 text-xs font-semibold text-maroon-700">{{ $competition->rankScores->count() }}</span>
                </div>
            </div>

            <form method="POST" action="{{ route('events.categories.competitions.rank-scores.store', [$event, $category, $competition]) }}" class="grid gap-3 border-b border-slate-200 bg-slate-50/70 p-5 sm:grid-cols-[8rem_1fr_auto]">
                @csrf
                <div>
                    <label for="rank" class="sr-only">Rank</label>
                    <input id="rank" name="rank" type="number" min="1" max="1000" value="{{ old('rank') }}" required class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none transition focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10" placeholder="Rank">
                    @error('rank')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="points" class="sr-only">Points</label>
                    <input id="points" name="points" type="number" min="0" max="999999.99" step="0.01" value="{{ old('points') }}" required class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none transition focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10" placeholder="Points awarded">
                    @error('points')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="h-11 rounded-xl bg-maroon-700 px-4 text-sm font-semibold text-white transition hover:bg-maroon-800">Save</button>
                <p class="text-xs text-slate-400 sm:col-span-3">Saving an existing rank updates its points.</p>
            </form>

            <form method="POST" action="{{ route('events.categories.competitions.participation-points.update', [$event, $category, $competition]) }}" class="border-b border-slate-200 bg-maroon-50/50 p-5">
                @csrf
                @method('PATCH')
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                    <div class="min-w-0 flex-1">
                        <label for="non_podium_points" class="block text-sm font-semibold text-slate-800">Non-podium participation points</label>
                        <p class="mt-1 text-xs leading-5 text-slate-500">Points awarded to participants who compete but do not finish in a configured ranked place. Set to 0 to award none.</p>
                    </div>
                    <div class="flex gap-2">
                        <div>
                            <label for="non_podium_points" class="sr-only">Non-podium points</label>
                            <input id="non_podium_points" name="non_podium_points" type="number" min="0" max="999999.99" step="0.01" value="{{ old('non_podium_points', $competition->non_podium_points) }}" required class="h-11 w-32 rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none transition focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10" placeholder="Points">
                        </div>
                        <button type="submit" class="h-11 rounded-xl border border-maroon-200 bg-white px-4 text-sm font-semibold text-maroon-700 transition hover:bg-maroon-100">Save</button>
                    </div>
                </div>
                @error('non_podium_points')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </form>

            <div class="overflow-x-auto">
                @if ($competition->rankScores->isNotEmpty())
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-6 py-3 font-semibold">Rank</th>
                                <th class="px-6 py-3 font-semibold">Points</th>
                                <th class="px-6 py-3 text-right font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($competition->rankScores as $rankScore)
                                <tr>
                                    <td class="px-6 py-4 font-semibold text-slate-900">#{{ $rankScore->rank }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ number_format((float) $rankScore->points, 2) }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <form method="POST" action="{{ route('events.categories.competitions.rank-scores.destroy', [$event, $category, $competition, $rankScore]) }}" onsubmit="return confirm('Remove scoring for this rank?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-800">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="px-6 py-10 text-center text-sm text-slate-400">No rank scoring configured.</p>
                @endif
            </div>
        </section>
    </div>
@endsection
