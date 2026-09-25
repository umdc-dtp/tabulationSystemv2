@extends('layouts.admin')

@section('title', 'Events')
@section('heading', 'Events')

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-maroon-700">Event management</p>
            <h2 class="mt-1 text-3xl font-semibold tracking-tight text-slate-950">Your events</h2>
            <p class="mt-2 text-sm text-slate-500">{{ auth()->user()->isAdmin() ? 'Create an event, then configure its participants, categories, and competitions.' : 'Open an event to manage participants, categories, contests, and scoring.' }}</p>
        </div>

        @if (auth()->user()->isAdmin())
            <button id="open-event-dialog" type="button" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-maroon-700 px-5 text-sm font-semibold text-white shadow-lg shadow-maroon-700/20 transition hover:bg-maroon-800 focus:outline-none focus-visible:ring-4 focus-visible:ring-maroon-600/25">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" stroke-linecap="round" />
                </svg>
                Add event
            </button>
        @endif
    </div>

    @if ($events->isEmpty())
        <section class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
            <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-maroon-50 text-maroon-700">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <rect x="3" y="5" width="18" height="16" rx="2" />
                    <path d="M16 3v4M8 3v4M3 10h18" stroke-linecap="round" />
                </svg>
            </span>
            <h3 class="mt-5 text-lg font-semibold text-slate-950">No events available</h3>
            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">{{ auth()->user()->isAdmin() ? 'Create your first event to begin adding participants, categories, and competitions.' : 'An administrator must create an event before it can be managed.' }}</p>
        </section>
    @else
        <section class="mt-8 grid gap-5 md:grid-cols-2 xl:grid-cols-3" aria-label="Events">
            @foreach ($events as $event)
                @php
                    $today = now()->startOfDay();
                    $status = $today->lt($event->start_date)
                        ? 'Upcoming'
                        : ($today->gt($event->end_date) ? 'Completed' : 'Active');
                    $statusClasses = match ($status) {
                        'Active' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                        'Upcoming' => 'bg-maroon-50 text-maroon-700 ring-maroon-600/20',
                        default => 'bg-slate-100 text-slate-600 ring-slate-500/20',
                    };
                @endphp

                <article class="group flex min-h-64 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-maroon-300 hover:shadow-xl hover:shadow-maroon-950/5 focus-within:ring-4 focus-within:ring-maroon-600/20">
                    <a href="{{ route('events.show', $event) }}" class="flex flex-1 flex-col p-6 focus:outline-none">
                    <div class="flex items-start justify-between gap-4">
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusClasses }}">{{ $status }}</span>

                        @if ($event->leaderboard_frozen)
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-700">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <rect x="5" y="11" width="14" height="10" rx="2" />
                                    <path d="M8 11V7a4 4 0 0 1 8 0v4" />
                                </svg>
                                Frozen
                            </span>
                        @endif
                    </div>

                    <h3 class="mt-5 text-xl font-semibold tracking-tight text-slate-950 transition group-hover:text-maroon-700">{{ $event->name }}</h3>
                    <p class="mt-2 text-sm text-slate-500">{{ $event->start_date->format('M j, Y') }} – {{ $event->end_date->format('M j, Y') }}</p>

                    <div class="mt-5 rounded-xl bg-slate-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Scoring</p>
                        <p class="mt-1 text-sm font-medium text-slate-700">{{ $event->scoringSystemLabel() }}</p>
                    </div>

                    <div class="mt-auto flex items-center justify-between pt-6">
                        <span class="text-xs text-slate-500">{{ $event->participants_count }} participants · {{ $event->categories_count }} categories</span>
                        <span class="inline-flex items-center gap-1 text-sm font-semibold text-maroon-700">
                            Configure
                            <svg class="h-4 w-4 transition group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                    </div>
                    </a>

                    <div class="flex items-center justify-between gap-3 border-t border-slate-100 px-6 py-3">
                        <a href="{{ route('events.leaderboard', $event) }}" class="inline-flex items-center gap-2 rounded-lg px-2 py-1.5 text-xs font-semibold text-maroon-700 transition hover:bg-maroon-50 hover:text-maroon-900">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 19V9m6 10V5m6 14v-7m4 7H2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                            Leaderboard
                        </a>

                        @if (auth()->user()->isAdmin())
                        <form method="POST" action="{{ route('events.destroy', $event) }}" data-loading-text="Deleting event…" onsubmit="return confirm('Delete this event? All participants, categories, contests, and scoring configurations will be permanently removed.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg px-2 py-1.5 text-xs font-semibold text-red-600 transition hover:bg-red-50 hover:text-red-700 focus:outline-none focus-visible:ring-4 focus-visible:ring-red-600/15">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M3 6h18M8 6V4h8v2M19 6l-1 15H6L5 6M10 11v6M14 11v6" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                Delete event
                            </button>
                        </form>
                        @endif
                    </div>
                </article>
            @endforeach
        </section>
    @endif

    @if (auth()->user()->isAdmin())
    <dialog id="event-dialog" class="m-auto w-[calc(100%-2rem)] max-w-xl rounded-2xl bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/70">
        <form method="POST" action="{{ route('events.store') }}">
            @csrf
            <input type="hidden" name="_form" value="event">

            <div class="flex items-start justify-between border-b border-slate-200 px-6 py-5">
                <div>
                    <h2 class="text-xl font-semibold text-slate-950">Add event</h2>
                    <p class="mt-1 text-sm text-slate-500">Set the event details and scoring method.</p>
                </div>
                <button id="close-event-dialog" type="button" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close add event form">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="m6 6 12 12M18 6 6 18" stroke-linecap="round" />
                    </svg>
                </button>
            </div>

            <div class="space-y-5 px-6 py-6">
                @if ($errors->any())
                    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        Please review the highlighted event details.
                    </div>
                @endif

                <div>
                    <label for="event_name" class="mb-2 block text-sm font-semibold text-slate-700">Event name</label>
                    <input id="event_name" name="event_name" type="text" value="{{ old('event_name') }}" required class="h-11 w-full rounded-xl border border-slate-300 px-4 text-sm outline-none transition focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10" placeholder="e.g. Regional Dance Championship">
                    @error('event_name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <fieldset>
                    <legend class="mb-2 text-sm font-semibold text-slate-700">Inclusive dates</legend>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="start_date" class="mb-1.5 block text-xs font-medium text-slate-500">Start date</label>
                            <input id="start_date" name="start_date" type="date" value="{{ old('start_date') }}" required class="h-11 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none transition focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10">
                            @error('start_date')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="end_date" class="mb-1.5 block text-xs font-medium text-slate-500">End date</label>
                            <input id="end_date" name="end_date" type="date" value="{{ old('end_date') }}" required class="h-11 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none transition focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10">
                            @error('end_date')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </fieldset>

                <div>
                    <label for="scoring_system" class="mb-2 block text-sm font-semibold text-slate-700">Scoring system</label>
                    <select id="scoring_system" name="scoring_system" required class="h-11 w-full rounded-xl border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10">
                        <option value="">Select a scoring system</option>
                        @foreach ($scoringSystems as $system)
                            <option value="{{ $system->value }}" @selected(old('scoring_system') === $system->value)>{{ $system->label() }}</option>
                        @endforeach
                    </select>
                    @error('scoring_system')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div id="other-scoring-wrapper" @class(['hidden' => old('scoring_system') !== 'other'])>
                    <label for="other_scoring_system" class="mb-2 block text-sm font-semibold text-slate-700">Describe the scoring system</label>
                    <input id="other_scoring_system" name="other_scoring_system" type="text" value="{{ old('other_scoring_system') }}" class="h-11 w-full rounded-xl border border-slate-300 px-4 text-sm outline-none transition focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10" placeholder="Enter the scoring method">
                    @error('other_scoring_system')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
                <button id="cancel-event-dialog" type="button" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-white">Cancel</button>
                <button type="submit" class="rounded-xl bg-maroon-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-maroon-800">Create event</button>
            </div>
        </form>
    </dialog>
    @endif
@endsection

@if (auth()->user()->isAdmin())
@push('scripts')
    <script>
        const eventDialog = document.getElementById('event-dialog');
        const scoringSystem = document.getElementById('scoring_system');
        const otherScoringWrapper = document.getElementById('other-scoring-wrapper');
        const otherScoringInput = document.getElementById('other_scoring_system');

        const closeEventDialog = () => eventDialog.close();

        document.getElementById('open-event-dialog').addEventListener('click', () => eventDialog.showModal());
        document.getElementById('close-event-dialog').addEventListener('click', closeEventDialog);
        document.getElementById('cancel-event-dialog').addEventListener('click', closeEventDialog);

        eventDialog.addEventListener('click', (event) => {
            if (event.target === eventDialog) {
                closeEventDialog();
            }
        });

        const updateOtherScoring = () => {
            const usesOtherScoring = scoringSystem.value === 'other';

            otherScoringWrapper.classList.toggle('hidden', ! usesOtherScoring);
            otherScoringInput.required = usesOtherScoring;
        };

        scoringSystem.addEventListener('change', updateOtherScoring);
        updateOtherScoring();

        @if ($errors->any())
            eventDialog.showModal();
        @endif
    </script>
@endpush
@endif
