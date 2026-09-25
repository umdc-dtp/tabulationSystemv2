@extends('layouts.admin')

@section('title', 'Scores · '.$competition->name)
@section('heading', 'Participant scores')

@section('content')
    @php
        $isAdmin = auth()->user()->isAdmin();
        $scoresAreFinalized = $competition->scoresAreFinalized();
        $canEditScores = ! $scoresAreFinalized || $isAdmin;
    @endphp

    <nav class="mb-6 flex flex-wrap items-center gap-2 text-sm text-slate-500" aria-label="Breadcrumb">
        <a href="{{ route('events.index') }}" class="font-medium transition hover:text-maroon-700">Events</a>
        <span>/</span>
        <a href="{{ route('events.show', $event) }}" class="font-medium transition hover:text-maroon-700">{{ $event->name }}</a>
        <span>/</span>
        <span>{{ $category->name }}</span>
        <span>/</span>
        <span class="font-medium text-slate-800">{{ $competition->name }} scores</span>
    </nav>

    <section class="overflow-hidden rounded-2xl bg-gradient-to-br from-maroon-700 to-maroon-950 text-white shadow-xl shadow-maroon-950/10">
        <div class="flex flex-col gap-6 p-7 sm:p-9 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-maroon-100 ring-1 ring-inset ring-white/15">{{ $category->name }}</span>
                    <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-maroon-100 ring-1 ring-inset ring-white/15">{{ $competition->scoring_method->label() }}</span>
                    <span @class([
                        'rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset',
                        'bg-emerald-400/15 text-emerald-200 ring-emerald-300/20' => $competition->scoresAreFinalized(),
                        'bg-amber-400/15 text-amber-200 ring-amber-300/20' => ! $competition->scoresAreFinalized(),
                    ])>{{ $competition->scoresAreFinalized() ? 'Scores finalized' : 'Scores in progress' }}</span>
                </div>
                <h2 class="mt-4 text-3xl font-semibold tracking-tight">Enter participant scores</h2>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-maroon-100/80">{{ $competition->name }} · {{ $competition->scoring_method->description() }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('events.categories.competitions.show', [$event, $category, $competition]) }}" class="inline-flex h-10 items-center justify-center rounded-xl border border-white/25 px-4 text-sm font-semibold text-white transition hover:bg-white/10">Configure contest</a>
                <a href="{{ route('events.leaderboard', $event) }}" class="inline-flex h-10 items-center justify-center rounded-xl bg-white px-4 text-sm font-semibold text-maroon-800 transition hover:bg-maroon-50">View leaderboard</a>
            </div>
        </div>
    </section>

    <section @class([
        'mt-6 flex flex-col gap-4 rounded-2xl border p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between',
        'border-emerald-200 bg-emerald-50' => $competition->scoresAreFinalized(),
        'border-amber-200 bg-amber-50' => ! $competition->scoresAreFinalized(),
    ])>
        <div>
            <h3 @class([
                'font-semibold',
                'text-emerald-950' => $competition->scoresAreFinalized(),
                'text-amber-950' => ! $competition->scoresAreFinalized(),
            ])>{{ $competition->scoresAreFinalized() ? 'This score sheet is finalized' : 'Finalize this score sheet when scoring is complete' }}</h3>
            <p @class([
                'mt-1 text-sm',
                'text-emerald-800' => $competition->scoresAreFinalized(),
                'text-amber-800' => ! $competition->scoresAreFinalized(),
            ])>
                @if ($scoresAreFinalized && $isAdmin)
                    Finalized {{ $competition->scores_finalized_at->diffForHumans() }}. Administrator override is enabled for scores and deductions.
                @elseif ($scoresAreFinalized)
                    Finalized {{ $competition->scores_finalized_at->diffForHumans() }}. Scores and deductions are locked for auditor accounts.
                @else
                    Finalized contests are counted toward the event's dashboard progress.
                @endif
            </p>
            @error('finalization')<p class="mt-2 text-sm font-semibold text-red-700">{{ $message }}</p>@enderror
        </div>

        @if (! $scoresAreFinalized || $isAdmin)
            <form method="POST" action="{{ route('events.categories.competitions.scores.finalization.update', [$event, $category, $competition]) }}" data-loading-text="{{ $scoresAreFinalized ? 'Reopening scores…' : 'Finalizing scores…' }}" @if ($scoresAreFinalized) onsubmit="return confirm('Reopen this score sheet for auditors?')" @else onsubmit="return confirm('Finalize these scores? Auditor editing will be locked, while administrators will retain override access.')" @endif>
                @csrf
                @method('PATCH')
                <button type="submit" @class([
                    'inline-flex h-11 shrink-0 items-center justify-center rounded-xl px-5 text-sm font-semibold transition',
                    'border border-emerald-300 bg-white text-emerald-800 hover:bg-emerald-100' => $scoresAreFinalized,
                    'bg-amber-900 text-white hover:bg-amber-950' => ! $scoresAreFinalized,
                ])>{{ $scoresAreFinalized ? 'Reopen scores' : 'Finalize scores' }}</button>
            </form>
        @else
            <span class="inline-flex h-11 items-center rounded-xl border border-emerald-300 bg-white px-5 text-sm font-semibold text-emerald-800">Locked for auditors</span>
        @endif
    </section>

    @if ($competition->usesCriteriaScoring() && $competition->criteria->isEmpty())
        <section class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-6">
            <h3 class="font-semibold text-amber-950">Configure criteria before entering scores</h3>
            <p class="mt-1 text-sm text-amber-800">This contest does not have any judging criteria yet.</p>
            <a href="{{ route('events.categories.competitions.show', [$event, $category, $competition]) }}" class="mt-4 inline-flex h-10 items-center rounded-xl bg-amber-900 px-4 text-sm font-semibold text-white">Open configuration</a>
        </section>
    @elseif ($event->participants->isEmpty())
        <section class="mt-6 rounded-2xl border border-slate-200 bg-white px-6 py-14 text-center shadow-sm">
            <p class="font-semibold text-slate-800">No participants available</p>
            <p class="mt-1 text-sm text-slate-500">Add participants to the event before recording scores.</p>
            <a href="{{ route('events.show', $event) }}" class="mt-4 inline-flex h-10 items-center rounded-xl bg-maroon-700 px-4 text-sm font-semibold text-white">Manage participants</a>
        </section>
    @else
        @php
            $hasSavedScores = $resultsByParticipant->contains(function ($result) use ($competition) {
                return $competition->usesCriteriaScoring()
                    ? $result->criterionScores->isNotEmpty()
                    : $result->wins !== null;
            });
        @endphp

        @if ($canEditScores && $hasSavedScores)
            <form id="deduction-form" method="POST" action="{{ route('events.categories.competitions.deductions.update', [$event, $category, $competition]) }}" class="hidden" data-loading-text="Saving deductions…">
                @csrf
                @method('PATCH')
            </form>
        @endif

        <form method="POST" action="{{ route('events.categories.competitions.scores.update', [$event, $category, $competition]) }}" class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" data-loading-text="Saving participant scores…">
            @csrf
            @method('PATCH')

            <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-950">Score sheet</h3>
                    <p class="mt-1 text-sm text-slate-500">Save a participant's base score first. Their deduction input will then become available.</p>
                </div>
                @if ($canEditScores)
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($scoresAreFinalized)
                            <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Admin override</span>
                        @endif
                        @if ($hasSavedScores)
                            <button form="deduction-form" type="submit" class="h-11 rounded-xl border border-amber-300 bg-amber-50 px-5 text-sm font-semibold text-amber-800 transition hover:bg-amber-100">Save deductions</button>
                        @endif
                        <button type="submit" class="h-11 rounded-xl bg-maroon-700 px-5 text-sm font-semibold text-white transition hover:bg-maroon-800">Save all scores</button>
                    </div>
                @else
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Read only for auditors</span>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="sticky left-0 z-10 bg-slate-50 px-6 py-3.5 font-semibold">Participant</th>
                            @if ($competition->usesCriteriaScoring())
                                @foreach ($competition->criteria as $criterion)
                                    <th class="px-4 py-3.5 text-center font-semibold">
                                        {{ $criterion->name }}
                                        <span class="mt-0.5 block text-[10px] font-normal normal-case tracking-normal text-slate-400">Max {{ number_format((float) $criterion->max_score, 2) }}</span>
                                    </th>
                                @endforeach
                            @else
                                <th class="px-6 py-3.5 font-semibold">Total wins</th>
                            @endif
                            <th class="px-6 py-3.5 text-center font-semibold">Deduction</th>
                            <th class="px-6 py-3.5 text-right font-semibold">Adjusted total</th>
                            @if (! $competition->usesCriteriaScoring())
                                <th class="px-6 py-3.5 text-right font-semibold">Current rank</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($event->participants as $participant)
                            @php
                                $result = $resultsByParticipant->get($participant->id);
                                $criterionScores = $result?->criterionScores->keyBy('criterion_id') ?? collect();
                                $standing = $standings->first(fn ($row) => $row['participant']->is($participant));
                                $hasSavedScore = $competition->usesCriteriaScoring()
                                    ? $criterionScores->isNotEmpty()
                                    : $result?->wins !== null;
                            @endphp
                            <tr class="align-middle hover:bg-slate-50/70">
                                <td class="sticky left-0 z-10 bg-white px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        @if ($participant->profile_picture_path)
                                            <img src="{{ Storage::disk('public')->url($participant->profile_picture_path) }}" alt="" class="h-9 w-9 rounded-full object-cover">
                                        @else
                                            <span class="grid h-9 w-9 place-items-center rounded-full bg-maroon-50 text-xs font-bold text-maroon-700">{{ strtoupper(substr($participant->name, 0, 1)) }}</span>
                                        @endif
                                        <span>
                                            <span class="block font-semibold text-slate-900">{{ $participant->name }}</span>
                                            <span class="block text-xs text-slate-400">{{ $participant->reference_no ?: 'No reference number' }}</span>
                                        </span>
                                    </div>
                                </td>
                                @if ($competition->usesCriteriaScoring())
                                    @foreach ($competition->criteria as $criterion)
                                        @php($field = "scores.{$participant->id}.{$criterion->id}")
                                        <td class="px-4 py-4 text-center">
                                            <input name="scores[{{ $participant->id }}][{{ $criterion->id }}]" type="number" min="0" max="{{ $criterion->max_score }}" step="0.01" value="{{ old($field, $criterionScores->get($criterion->id)?->score) }}" @disabled(! $canEditScores) class="h-10 w-28 rounded-lg border border-slate-300 px-3 text-center text-sm outline-none transition disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500 focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10" aria-label="{{ $criterion->name }} score for {{ $participant->name }}">
                                            @error($field)<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                        </td>
                                    @endforeach
                                @else
                                    @php($field = "wins.{$participant->id}")
                                    <td class="px-6 py-4">
                                        <input name="wins[{{ $participant->id }}]" type="number" min="0" max="1000000" step="1" value="{{ old($field, $result?->wins) }}" @disabled(! $canEditScores) class="h-10 w-36 rounded-lg border border-slate-300 px-3 text-sm outline-none transition disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500 focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10" aria-label="Total wins for {{ $participant->name }}">
                                        @error($field)<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                    </td>
                                @endif
                                <td class="px-6 py-4 text-center">
                                    @if ($hasSavedScore)
                                        @php($deductionField = "deductions.{$participant->id}")
                                        <input form="deduction-form" name="deductions[{{ $participant->id }}]" type="number" min="0" max="{{ min($standing['gross_score'] ?? 0, 999999.99) }}" step="0.01" value="{{ old($deductionField, $result?->deduction ?? 0) }}" @disabled(! $canEditScores) class="h-10 w-28 rounded-lg border border-amber-300 bg-amber-50/50 px-3 text-center text-sm outline-none transition disabled:cursor-not-allowed disabled:border-slate-300 disabled:bg-slate-100 disabled:text-slate-500 focus:border-amber-600 focus:ring-4 focus:ring-amber-600/10" aria-label="Deduction for {{ $participant->name }}">
                                        @error($deductionField)<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                    @else
                                        <span class="text-xs text-slate-400">Save score first</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right font-semibold text-slate-700">{{ $standing ? number_format($standing['raw_score'], 2) : '—' }}</td>
                                @if (! $competition->usesCriteriaScoring())
                                    <td class="px-6 py-4 text-right font-semibold text-slate-700">{{ $standing ? '#'.$standing['rank'] : '—' }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($canEditScores)
                <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200 bg-slate-50 px-6 py-4">
                    @if ($hasSavedScores)
                        <button form="deduction-form" type="submit" class="h-11 rounded-xl border border-amber-300 bg-amber-50 px-6 text-sm font-semibold text-amber-800 transition hover:bg-amber-100">Save deductions</button>
                    @endif
                    <button type="submit" class="h-11 rounded-xl bg-maroon-700 px-6 text-sm font-semibold text-white transition hover:bg-maroon-800">Save all scores</button>
                </div>
            @endif
        </form>
    @endif
@endsection
