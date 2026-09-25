@extends('layouts.admin')

@section('title', 'Enter result')
@section('heading', 'Game result entry')

@section('content')
    <nav class="mb-6 text-sm text-slate-500" aria-label="Breadcrumb">
        <a href="{{ route('events.show', $event) }}" class="font-semibold text-maroon-700">{{ $event->name }}</a>
        <span class="px-2">/</span>
        <a href="{{ route('events.categories.competitions.show', [$event, $category, $competition]) }}" class="font-semibold text-maroon-700">{{ $competition->name }}</a>
        <span class="px-2">/</span>
        <span>Result</span>
    </nav>

    <section class="rounded-2xl bg-gradient-to-br from-maroon-700 to-maroon-950 p-7 text-white shadow-xl shadow-maroon-950/10">
        <p class="text-sm font-semibold text-maroon-200">{{ $category->name }} · {{ $competition->name }}</p>
        <h2 class="mt-2 text-3xl font-semibold">{{ $entry->participant?->name ?? $entry->team?->name }}</h2>
        <p class="mt-2 text-sm text-maroon-100/80">{{ $entry->participant?->department?->name ?? $entry->team?->department?->name }}@if ($entry->team) · Members: {{ implode(', ', $entry->team->member_names) }}@endif</p>
    </section>

    @if ($errors->any())
        <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('events.categories.competitions.entries.result.update', [$event, $category, $competition, $entry]) }}" class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @csrf
        @method('PATCH')
        <div class="border-b border-slate-200 p-6">
            <h3 class="text-lg font-semibold text-slate-950">{{ $competition->usesCriteriaScoring() ? 'Judges’ scores' : 'Final win–loss record' }}</h3>
            <p class="mt-1 text-sm text-slate-500">{{ $competition->results_open ? ($competition->finalized_at ? 'Save draft changes here, then publish all corrections from the game page. The current standings stay public until then.' : 'Save a draft, then finalize the game once every competitor is complete.') : 'Results are locked. Select Correct results on the game page to update them.' }}</p>
        </div>

        @if ($competition->usesCriteriaScoring())
            <div class="grid gap-5 p-6 lg:grid-cols-2">
                @for ($judgeNumber = 1; $judgeNumber <= $competition->judge_count; $judgeNumber++)
                    <fieldset class="rounded-xl border border-slate-200 p-5">
                        <legend class="px-2 text-sm font-bold text-maroon-800">Judge {{ $judgeNumber }}</legend>
                        <div class="space-y-4">
                            @foreach ($competition->criteria as $criterion)
                                @php($existingScore = $entry->judgeScores->first(fn ($score) => $score->judge_number === $judgeNumber && $score->criterion_id === $criterion->id)?->score)
                                <div>
                                    <label for="score_{{ $judgeNumber }}_{{ $criterion->id }}" class="mb-1 block text-sm font-semibold text-slate-700">{{ $criterion->name }} <span class="font-normal text-slate-400">/ {{ $criterion->max_score }}</span></label>
                                    <input id="score_{{ $judgeNumber }}_{{ $criterion->id }}" name="scores[{{ $judgeNumber }}][{{ $criterion->id }}]" type="number" min="0" max="{{ $criterion->max_score }}" step="0.01" value="{{ old('scores.'.$judgeNumber.'.'.$criterion->id, $existingScore) }}" @disabled(! $competition->results_open) class="h-11 w-full rounded-xl border border-slate-300 px-3 text-sm focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10">
                                    @error('scores.'.$judgeNumber.'.'.$criterion->id)<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                            @endforeach
                        </div>
                    </fieldset>
                @endfor
            </div>
        @else
            <div class="grid gap-5 p-6 sm:grid-cols-2">
                <div>
                    <label for="win_total" class="mb-1 block text-sm font-semibold text-slate-700">Wins</label>
                    <input id="win_total" name="win_total" type="number" min="0" step="1" value="{{ old('win_total', $entry->win_total) }}" @disabled(! $competition->results_open) class="h-11 w-full rounded-xl border border-slate-300 px-3 text-sm">
                    @error('win_total')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="loss_total" class="mb-1 block text-sm font-semibold text-slate-700">Losses</label>
                    <input id="loss_total" name="loss_total" type="number" min="0" step="1" value="{{ old('loss_total', $entry->loss_total) }}" @disabled(! $competition->results_open) class="h-11 w-full rounded-xl border border-slate-300 px-3 text-sm">
                    @error('loss_total')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        @endif

        <div class="flex flex-wrap items-center gap-3 border-t border-slate-200 bg-slate-50 p-6">
            @if ($competition->results_open)
                <button class="h-11 rounded-xl bg-maroon-700 px-5 text-sm font-semibold text-white">Save draft result</button>
            @endif
            <a href="{{ route('events.categories.competitions.show', [$event, $category, $competition]) }}" class="h-11 rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">Back to game</a>
        </div>
    </form>
@endsection
