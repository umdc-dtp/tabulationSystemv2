@extends('layouts.admin')

@section('title', 'Score sheet · '.$competition->name)
@section('heading', 'Score sheet')

@section('content')
    <nav class="mb-6 flex flex-wrap items-center gap-2 text-sm text-slate-500" aria-label="Breadcrumb">
        <a href="{{ route('events.show', $event) }}" class="font-medium text-maroon-700 hover:underline">{{ $event->name }}</a>
        <span>/</span>
        <a href="{{ route('events.categories.competitions.show', [$event, $category, $competition]) }}" class="font-medium text-maroon-700 hover:underline">{{ $competition->name }}</a>
        <span>/</span>
        <span>Score sheet</span>
    </nav>

    <section class="rounded-2xl bg-gradient-to-br from-maroon-700 to-maroon-950 p-7 text-white shadow-xl shadow-maroon-950/10 sm:p-9">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-maroon-200">{{ $category->name }}</p>
                <h2 class="mt-2 text-3xl font-semibold">{{ $competition->name }} score sheet</h2>
                <p class="mt-2 text-sm text-maroon-100/80">Only competitors added to this game are scored here. Individuals and teams use the same publishing workflow.</p>
            </div>
            <a href="{{ route('events.categories.competitions.show', [$event, $category, $competition]) }}" class="rounded-xl border border-white/30 px-4 py-2 text-sm font-semibold hover:bg-white/10">Manage competitors</a>
        </div>
    </section>

    @if ($errors->any())
        <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-6 py-5">
            <div>
                <h3 class="text-lg font-semibold text-slate-950">Competing entries</h3>
                <p class="mt-1 text-sm text-slate-500">{{ $competition->finalized_at ? 'Editing a published score starts a private correction draft. The public keeps seeing the last published result until you publish corrections.' : 'Enter every competing score, then publish the game.' }}</p>
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $competition->results_open ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">{{ $competition->results_open ? ($competition->finalized_at ? 'Correcting' : 'Draft') : 'Published' }}</span>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse ($competition->entries as $entry)
                <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                    <div>
                        <p class="font-semibold text-slate-900">{{ $entry->participant?->name ?? $entry->team?->name }}</p>
                        <p class="text-xs text-slate-500">{{ $entry->participant?->department?->name ?? $entry->team?->department?->name }}@if ($entry->team) · {{ implode(', ', $entry->team->member_names) }}@endif · {{ $entry->competed ? 'Competed' : 'Did not compete' }}</p>
                        @if ($entry->competed)
                            <p class="mt-1 text-xs text-slate-500">
                                @if ($competition->usesCriteriaScoring())
                                    {{ $entry->judgeScores->count() }} of {{ $competition->criteria->count() * $competition->judge_count }} judge scores
                                    @if ((float) $entry->deduction > 0) · {{ number_format((float) $entry->deduction, 2) }} deduction @endif
                                @else
                                    {{ $entry->win_total === null || $entry->loss_total === null ? 'Record incomplete' : $entry->win_total.' wins · '.$entry->loss_total.' losses' }}
                                @endif
                            </p>
                        @endif
                    </div>
                    @if ($entry->competed)
                        <a href="{{ route('events.categories.competitions.entries.show', [$event, $category, $competition, $entry]) }}" class="inline-flex h-10 items-center rounded-lg bg-maroon-700 px-4 text-sm font-semibold text-white hover:bg-maroon-800">Enter / Edit score</a>
                    @endif
                </div>
            @empty
                <p class="px-6 py-8 text-sm text-slate-500">No competitors entered yet. Add a registered individual or team in game configuration.</p>
            @endforelse
        </div>
        @if ($competition->results_open && $competition->entries->where('competed', true)->isNotEmpty())
            <div class="border-t border-slate-200 bg-slate-50 p-5">
                <form method="POST" action="{{ route('events.categories.competitions.finalize', [$event, $category, $competition]) }}">
                    @csrf
                    <button class="h-11 rounded-xl bg-emerald-700 px-5 text-sm font-semibold text-white hover:bg-emerald-800">{{ $competition->finalized_at ? 'Publish corrections' : 'Finalize results' }}</button>
                </form>
            </div>
        @endif
    </section>

    @if ($draftPreview !== null)
        <section class="mt-6 overflow-hidden rounded-2xl border border-amber-200 bg-white shadow-sm">
            <div class="bg-amber-50 px-6 py-4"><h3 class="font-semibold text-amber-950">Draft standings preview</h3><p class="text-xs text-amber-800">Only admins and auditors can see this draft.</p></div>
            <div class="overflow-x-auto"><table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-6 py-3">Rank</th><th class="px-6 py-3">Competitor</th><th class="px-6 py-3 text-right">{{ $competition->usesCriteriaScoring() ? 'Adjusted score' : 'W–L' }}</th><th class="px-6 py-3 text-right">Points</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($draftPreview as $row)
                        <tr><td class="px-6 py-3 font-semibold">#{{ $row['rank'] }}</td><td class="px-6 py-3">{{ $row['entrant_name'] }}</td><td class="px-6 py-3 text-right">{{ $competition->usesCriteriaScoring() ? number_format((float) $row['result_value'], 2) : $row['result_value'].'–'.$row['loss_total'] }}</td><td class="px-6 py-3 text-right">{{ number_format((float) $row['points'], 2) }}</td></tr>
                    @endforeach
                </tbody>
            </table></div>
        </section>
    @elseif ($draftPreviewError !== null)
        <p class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Draft preview unavailable: {{ $draftPreviewError }}</p>
    @endif

    @if ($competition->finalizedResults->isNotEmpty())
        <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="px-6 py-4"><h3 class="font-semibold text-slate-950">Published standings</h3><p class="text-xs text-slate-500">Last published {{ $competition->finalized_at->format('M j, Y g:i A') }}.</p></div>
            <div class="overflow-x-auto"><table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-6 py-3">Rank</th><th class="px-6 py-3">Competitor</th><th class="px-6 py-3">Department</th><th class="px-6 py-3 text-right">{{ $competition->usesCriteriaScoring() ? 'Adjusted score' : 'W–L' }}</th><th class="px-6 py-3 text-right">Points</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($competition->finalizedResults->sortBy('rank') as $result)
                        <tr><td class="px-6 py-3 font-semibold">#{{ $result->rank }}</td><td class="px-6 py-3">{{ $result->entrant_name }}</td><td class="px-6 py-3">{{ $result->department->name }}</td><td class="px-6 py-3 text-right">{{ $competition->usesCriteriaScoring() ? number_format((float) $result->result_value, 2) : (int) $result->result_value.'–'.$result->loss_total }}</td><td class="px-6 py-3 text-right">{{ number_format((float) $result->points, 2) }}</td></tr>
                    @endforeach
                </tbody>
            </table></div>
        </section>
    @endif
@endsection
