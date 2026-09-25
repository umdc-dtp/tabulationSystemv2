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
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('events.show', $event) }}" class="inline-flex h-10 items-center justify-center rounded-xl border border-white/25 px-4 text-sm font-semibold text-white transition hover:bg-white/10">Back to event</a>
                <a href="{{ route('events.categories.competitions.scores.edit', [$event, $category, $competition]) }}" class="inline-flex h-10 items-center justify-center rounded-xl bg-white px-4 text-sm font-semibold text-maroon-800 transition hover:bg-maroon-50">Enter scores</a>
                @if (auth()->user()->isAdmin())
                    <form method="POST" action="{{ route('events.categories.competitions.destroy', [$event, $category, $competition]) }}" data-loading-text="Deleting contest…" onsubmit="return confirm('Delete this contest? Its configuration and scores will be permanently removed.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="h-10 rounded-xl border border-red-200/60 bg-red-600 px-4 text-sm font-semibold text-white transition hover:bg-red-700">Delete contest</button>
                    </form>
                @endif
            </div>
        </div>
    </section>

    @if ($errors->any())
        <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700" role="alert">{{ $errors->first() }}</div>
    @endif

    @if ($competition->usesCriteriaScoring())
    <section class="mt-6 rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-5">
            <h3 class="text-lg font-semibold text-slate-950">Leaderboard game setup</h3>
            <p class="mt-1 text-sm text-slate-500">Set the number of judges for criteria-based scoring.</p>
        </div>
        <form method="POST" action="{{ route('events.categories.competitions.leaderboard-settings.update', [$event, $category, $competition]) }}" class="flex flex-wrap items-end gap-4 p-5">
            @csrf
            @method('PATCH')
            <div>
                <label for="judge_count" class="mb-1 block text-sm font-semibold text-slate-700">Judge slots</label>
                <input id="judge_count" name="judge_count" type="number" min="1" max="20" value="{{ $competition->judge_count }}" class="h-11 w-full rounded-xl border border-slate-300 px-3 text-sm">
            </div>
            <button class="h-11 rounded-xl bg-maroon-700 px-5 text-sm font-semibold text-white">Save setup</button>
        </form>
    </section>
    @endif

    <section class="mt-6 rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-6 py-5">
            <div>
                <h3 class="text-lg font-semibold text-slate-950">Competitors and results</h3>
                <p class="mt-1 text-sm text-slate-500">{{ $competition->results_open ? ($competition->finalized_at ? 'Edit draft scores, then publish corrections together. The last published standings stay public.' : 'Draft competitors and scores can be edited. Public results are hidden until finalized.') : 'Results are published. Start a correction draft to update scores.' }}</p>
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $competition->results_open ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">{{ $competition->results_open ? ($competition->finalized_at ? 'Correcting' : 'Draft') : 'Published' }}</span>
        </div>

        @if ($competition->results_open)
            <div class="border-b border-slate-200 bg-slate-50 p-5">
                <form method="POST" action="{{ route('events.categories.competitions.entries.store', [$event, $category, $competition]) }}" class="grid gap-3 sm:grid-cols-[1fr_auto] sm:items-end">
                    @csrf
                    <div>
                        <label for="registered_competitor" class="mb-1 block text-xs font-semibold text-slate-600">Registered participant or team</label>
                        <select id="registered_competitor" name="competitor" required class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm">
                            <option value="">Choose a registered competitor</option>
                            <optgroup label="Individuals">
                            @foreach ($event->participants->whereNotNull('department_id') as $participant)
                                <option value="participant:{{ $participant->id }}" @selected(old('competitor') === 'participant:'.$participant->id)>{{ $participant->name }} — {{ $participant->department->name }}</option>
                            @endforeach
                            </optgroup>
                            <optgroup label="Teams">
                            @foreach ($event->teams as $team)
                                <option value="team:{{ $team->id }}" @selected(old('competitor') === 'team:'.$team->id)>{{ $team->name }} — {{ $team->department->name }}</option>
                            @endforeach
                            </optgroup>
                        </select>
                    </div>
                    <button class="h-11 rounded-xl bg-maroon-700 px-5 text-sm font-semibold text-white">Add competitor</button>
                </form>
                <p class="mt-2 text-xs text-slate-500">Register individuals and teams on the event page first. Changing competitors or participation withdraws the previously published standings until you publish again.</p>
            </div>
        @endif

        <div class="divide-y divide-slate-100">
            @forelse ($competition->entries as $entry)
                <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                    <div>
                        <p class="font-semibold text-slate-900">{{ $entry->participant?->name ?? $entry->team?->name }}</p>
                        <p class="text-xs text-slate-500">{{ $entry->participant?->department?->name ?? $entry->team?->department?->name }}@if ($entry->team) · {{ implode(', ', $entry->team->member_names) }}@endif · {{ $entry->competed ? 'Competed' : 'Did not compete' }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('events.categories.competitions.entries.show', [$event, $category, $competition, $entry]) }}" class="text-sm font-semibold text-maroon-700">{{ $competition->results_open ? 'Enter result' : 'View result' }}</a>
                        @if ($competition->results_open)
                            <form method="POST" action="{{ route('events.categories.competitions.entries.participation.update', [$event, $category, $competition, $entry]) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="competed" value="{{ $entry->competed ? '0' : '1' }}">
                                <button class="text-xs font-semibold text-slate-600">{{ $entry->competed ? 'Mark absent' : 'Mark competed' }}</button>
                            </form>
                            <form method="POST" action="{{ route('events.categories.competitions.entries.destroy', [$event, $category, $competition, $entry]) }}" onsubmit="return confirm('Remove this competitor and draft scores?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-xs font-semibold text-red-600">Remove</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <p class="px-6 py-8 text-sm text-slate-500">No competitors entered yet.</p>
            @endforelse
        </div>

        <div class="flex flex-wrap items-center gap-3 border-t border-slate-200 p-5">
            @if ($competition->results_open)
                <form method="POST" action="{{ route('events.categories.competitions.finalize', [$event, $category, $competition]) }}">
                    @csrf
                    <button class="h-11 rounded-xl bg-emerald-700 px-5 text-sm font-semibold text-white">{{ $competition->finalized_at ? 'Publish corrections' : 'Finalize results' }}</button>
                </form>
            @else
                <form method="POST" action="{{ route('events.categories.competitions.reopen', [$event, $category, $competition]) }}">
                    @csrf
                    <button class="h-11 rounded-xl border border-amber-300 px-5 text-sm font-semibold text-amber-900">Correct results</button>
                </form>
            @endif
            @if ($competition->finalized_at)<p class="text-xs text-slate-500">Last published {{ $competition->finalized_at->format('M j, Y g:i A') }}. Score corrections remain a private draft until you publish them.</p>@endif
        </div>

        @if ($competition->results_open && $draftPreview !== null)
            <div class="overflow-x-auto border-t border-slate-200">
                <div class="bg-amber-50 px-6 py-4">
                    <h4 class="text-sm font-semibold text-amber-950">Draft standings preview</h4>
                    <p class="mt-1 text-xs text-amber-800">These recalculated ranks and points are visible only to auditors until you publish.</p>
                </div>
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-6 py-3">Rank</th><th class="px-6 py-3">Competitor</th><th class="px-6 py-3 text-right">{{ $competition->usesCriteriaScoring() ? 'Score' : 'W–L' }}</th><th class="px-6 py-3 text-right">Points</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($draftPreview as $row)
                            <tr><td class="px-6 py-3 font-semibold">#{{ $row['rank'] }}</td><td class="px-6 py-3">{{ $row['entrant_name'] }}</td><td class="px-6 py-3 text-right">{{ $competition->usesCriteriaScoring() ? number_format((float) $row['result_value'], 2) : $row['result_value'].'–'.$row['loss_total'] }}</td><td class="px-6 py-3 text-right">{{ number_format((float) $row['points'], 2) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @elseif ($competition->results_open && $draftPreviewError !== null)
            <p class="border-t border-amber-200 bg-amber-50 px-6 py-4 text-sm text-amber-900">Draft preview unavailable: {{ $draftPreviewError }}</p>
        @endif

        @if ($competition->finalizedResults->isNotEmpty())
            <div class="overflow-x-auto border-t border-slate-200">
                <div class="px-6 py-4">
                    <h4 class="text-sm font-semibold text-slate-900">Published standings</h4>
                    @if ($competition->results_open)<p class="mt-1 text-xs text-slate-500">These are still visible to the public while you correct scores.</p>@endif
                </div>
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-6 py-3">Rank</th><th class="px-6 py-3">Competitor</th><th class="px-6 py-3">Department</th><th class="px-6 py-3 text-right">{{ $competition->usesCriteriaScoring() ? 'Score' : 'W–L' }}</th><th class="px-6 py-3 text-right">Points</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($competition->finalizedResults->sortBy('rank') as $result)
                            <tr><td class="px-6 py-3 font-semibold">#{{ $result->rank }}</td><td class="px-6 py-3">{{ $result->entrant_name }}@if ($result->member_names)<span class="mt-1 block text-xs text-slate-500">{{ implode(', ', $result->member_names) }}</span>@endif</td><td class="px-6 py-3">{{ $result->department->name }}</td><td class="px-6 py-3 text-right">{{ $competition->usesCriteriaScoring() ? number_format((float) $result->result_value, 2) : (int) $result->result_value.'–'.($result->loss_total ?? '—') }}</td><td class="px-6 py-3 text-right">{{ number_format((float) $result->points, 2) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
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
