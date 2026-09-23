@extends('layouts.admin')

@section('title', $event->name)
@section('heading', 'Event configuration')

@section('content')
    <nav class="mb-6 flex items-center gap-2 text-sm text-slate-500" aria-label="Breadcrumb">
        <a href="{{ route('events.index') }}" class="font-medium transition hover:text-maroon-700">Events</a>
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        <span class="truncate text-slate-700">{{ $event->name }}</span>
    </nav>

    <section class="overflow-hidden rounded-2xl bg-gradient-to-br from-maroon-700 to-maroon-950 text-white shadow-xl shadow-maroon-950/10">
        <div class="flex flex-col gap-6 p-7 sm:p-9 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-maroon-100 ring-1 ring-inset ring-white/15">{{ $event->scoringSystemLabel() }}</span>
                    <span @class([
                        'rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset',
                        'bg-amber-400/15 text-amber-200 ring-amber-300/20' => $event->leaderboard_frozen,
                        'bg-emerald-400/15 text-emerald-200 ring-emerald-300/20' => ! $event->leaderboard_frozen,
                    ])>
                        Leaderboard {{ $event->leaderboard_frozen ? 'frozen' : 'live' }}
                    </span>
                </div>
                <h2 class="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">{{ $event->name }}</h2>
                <p class="mt-3 text-sm text-maroon-100/80">{{ $event->start_date->format('F j, Y') }} – {{ $event->end_date->format('F j, Y') }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <form method="POST" action="{{ route('events.leaderboard-freeze', $event) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" @class([
                        'inline-flex h-11 items-center justify-center gap-2 rounded-xl px-5 text-sm font-semibold transition focus:outline-none focus-visible:ring-4 focus-visible:ring-white/30',
                        'bg-white text-maroon-800 hover:bg-maroon-50' => ! $event->leaderboard_frozen,
                        'bg-amber-300 text-amber-950 hover:bg-amber-200' => $event->leaderboard_frozen,
                    ])>
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <rect x="5" y="11" width="14" height="10" rx="2" />
                            <path d="M8 11V7a4 4 0 0 1 8 0v4" stroke-linecap="round" />
                        </svg>
                        {{ $event->leaderboard_frozen ? 'Unfreeze leaderboard' : 'Freeze leaderboard' }}
                    </button>
                </form>

                @if (auth()->user()->isAdmin())
                    <form method="POST" action="{{ route('events.destroy', $event) }}" data-loading-text="Deleting event…" onsubmit="return confirm('Delete this event? All event data will be permanently removed.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-white/30 bg-maroon-950/30 px-5 text-sm font-semibold text-white transition hover:border-red-200 hover:bg-red-700 focus:outline-none focus-visible:ring-4 focus-visible:ring-white/30">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 15H6L5 6M10 11v6M14 11v6" stroke-linecap="round" stroke-linejoin="round" /></svg>
                            Delete event
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-950">Participants</h3>
                        <p class="mt-1 text-sm text-slate-500">Manage competitors and optional profile photos.</p>
                    </div>
                    <span class="rounded-full bg-maroon-50 px-2.5 py-1 text-xs font-semibold text-maroon-700">{{ $event->participants->count() }}</span>
                </div>
            </div>

            <form method="POST" action="{{ route('events.participants.store', $event) }}" enctype="multipart/form-data" class="space-y-3 border-b border-slate-200 bg-slate-50/70 p-5">
                @csrf
                <div>
                    <label for="participant_name" class="mb-1.5 block text-xs font-semibold text-slate-600">Participant name</label>
                    <input id="participant_name" name="participant_name" type="text" value="{{ old('participant_name') }}" required class="h-11 w-full rounded-xl border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10" placeholder="Participant name">
                    @error('participant_name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="participant_reference" class="mb-1.5 block text-xs font-semibold text-slate-600">Reference number <span class="font-normal text-slate-400">(optional)</span></label>
                    <input id="participant_reference" name="participant_reference" type="text" value="{{ old('participant_reference') }}" class="h-11 w-full rounded-xl border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10" placeholder="e.g. P-001">
                    @error('participant_reference')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="participant_image" class="mb-1.5 block text-xs font-semibold text-slate-600">Profile picture <span class="font-normal text-slate-400">(optional)</span></label>
                    <input id="participant_image" name="participant_image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="block w-full rounded-xl border border-slate-300 bg-white text-sm text-slate-500 file:mr-4 file:border-0 file:bg-maroon-50 file:px-4 file:py-3 file:text-sm file:font-semibold file:text-maroon-700 hover:file:bg-maroon-100">
                    <p class="mt-1.5 text-xs text-slate-400">JPG, PNG, or WebP up to 2 MB.</p>
                    @error('participant_image')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="h-11 w-full rounded-xl bg-maroon-700 px-5 text-sm font-semibold text-white transition hover:bg-maroon-800">Add participant</button>
            </form>

            <div class="divide-y divide-slate-100">
                @forelse ($event->participants as $participant)
                    <article class="px-5 py-5">
                        <div class="flex items-start gap-3">
                            @if ($participant->profile_picture_path)
                                <img src="{{ Storage::disk('public')->url($participant->profile_picture_path) }}" alt="{{ $participant->name }}" class="h-12 w-12 shrink-0 rounded-full object-cover ring-2 ring-slate-100">
                            @else
                                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-maroon-50 text-sm font-bold text-maroon-700">{{ strtoupper(substr($participant->name, 0, 1)) }}</span>
                            @endif

                            <div class="min-w-0 flex-1 pt-1">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $participant->name }}</p>
                                <p class="mt-0.5 text-xs text-slate-400">{{ $participant->reference_no ?: 'No reference number' }}</p>
                            </div>

                            <form method="POST" action="{{ route('events.participants.destroy', [$event, $participant]) }}" onsubmit="return confirm('Delete this participant? This action cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg p-2 text-slate-400 transition hover:bg-red-50 hover:text-red-700" aria-label="Delete {{ $participant->name }}">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="M3 6h18M8 6V4h8v2M19 6l-1 15H6L5 6M10 11v6M14 11v6" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </button>
                            </form>
                        </div>

                        <details class="group mt-3">
                            <summary class="ml-15 inline-flex cursor-pointer list-none items-center gap-1 text-xs font-semibold text-maroon-700 hover:text-maroon-800 [&::-webkit-details-marker]:hidden">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                Edit participant
                            </summary>

                            <form method="POST" action="{{ route('events.participants.update', [$event, $participant]) }}" enctype="multipart/form-data" class="mt-3 space-y-3 rounded-xl bg-slate-50 p-4">
                                @csrf
                                @method('PATCH')

                                <div>
                                    <label for="participant_name_{{ $participant->id }}" class="mb-1 block text-xs font-semibold text-slate-600">Name</label>
                                    <input id="participant_name_{{ $participant->id }}" name="participant_name" type="text" value="{{ $participant->name }}" required class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm outline-none focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10">
                                </div>
                                <div>
                                    <label for="participant_reference_{{ $participant->id }}" class="mb-1 block text-xs font-semibold text-slate-600">Reference number</label>
                                    <input id="participant_reference_{{ $participant->id }}" name="participant_reference" type="text" value="{{ $participant->reference_no }}" class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm outline-none focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10">
                                </div>
                                <div>
                                    <label for="participant_image_{{ $participant->id }}" class="mb-1 block text-xs font-semibold text-slate-600">Replace profile picture</label>
                                    <input id="participant_image_{{ $participant->id }}" name="participant_image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="block w-full text-xs text-slate-500">
                                </div>
                                @if ($participant->profile_picture_path)
                                    <label class="flex items-center gap-2 text-xs text-slate-600">
                                        <input name="remove_profile_picture" type="checkbox" value="1" class="rounded border-slate-300 text-maroon-700 focus:ring-maroon-600">
                                        Remove current profile picture
                                    </label>
                                @endif
                                <button type="submit" class="h-10 w-full rounded-lg bg-maroon-700 px-4 text-sm font-semibold text-white transition hover:bg-maroon-800">Save changes</button>
                            </form>
                        </details>
                    </article>
                @empty
                    <p class="px-6 py-10 text-center text-sm text-slate-400">No participants have been added.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-950">Categories & contests</h3>
                        <p class="mt-1 text-sm text-slate-500">Create contests, choose criteria- or wins-based scoring, then configure leaderboard points.</p>
                    </div>
                    <span class="rounded-full bg-maroon-50 px-2.5 py-1 text-xs font-semibold text-maroon-700">{{ $event->categories->count() }}</span>
                </div>
            </div>

            <form method="POST" action="{{ route('events.categories.store', $event) }}" class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50/70 p-5 sm:flex-row">
                @csrf
                <div class="min-w-0 flex-1">
                    <label for="category_name" class="sr-only">Category name</label>
                    <input id="category_name" name="category_name" type="text" value="{{ old('category_name') }}" required class="h-11 w-full rounded-xl border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10" placeholder="Category name">
                    @error('category_name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="h-11 rounded-xl bg-maroon-700 px-5 text-sm font-semibold text-white transition hover:bg-maroon-800">Add category</button>
            </form>

            <div class="space-y-4 p-5">
                @forelse ($event->categories as $category)
                    <article class="rounded-xl border border-slate-200">
                        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                            <h4 class="font-semibold text-slate-900">{{ $category->name }}</h4>
                            <span class="text-xs text-slate-400">{{ $category->competitions->count() }} contests</span>
                        </div>

                        <div class="p-4">
                            <form method="POST" action="{{ route('events.categories.competitions.store', [$event, $category]) }}" class="flex flex-col gap-2 sm:flex-row">
                                @csrf
                                <label for="competition_name_{{ $category->id }}" class="sr-only">Contest name for {{ $category->name }}</label>
                                <input id="competition_name_{{ $category->id }}" name="competition_name" type="text" required class="h-10 min-w-0 flex-1 rounded-lg border border-slate-300 px-3 text-sm outline-none transition focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10" placeholder="Contest name">
                                <button type="submit" class="h-10 rounded-lg border border-maroon-200 bg-maroon-50 px-4 text-sm font-semibold text-maroon-700 transition hover:bg-maroon-100">Add contest</button>
                            </form>
                            @error('competition_name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror

                            @if ($category->competitions->isNotEmpty())
                                <ul class="mt-4 space-y-2">
                                    @foreach ($category->competitions as $competition)
                                        <li class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2.5">
                                            <span class="flex min-w-0 items-center gap-2 text-sm font-medium text-slate-700">
                                                <span class="h-2 w-2 shrink-0 rounded-full bg-maroon-500"></span>
                                                <span class="truncate">{{ $competition->name }}</span>
                                                <span class="shrink-0 rounded-full bg-white px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-maroon-700 ring-1 ring-inset ring-maroon-100">{{ $competition->scoring_method->label() }}</span>
                                            </span>
                                            <a href="{{ route('events.categories.competitions.show', [$event, $category, $competition]) }}" class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-maroon-200 bg-white px-3 py-1.5 text-xs font-semibold text-maroon-700 transition hover:bg-maroon-50">
                                                Configure
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                    <path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="mt-4 text-xs text-slate-400">No contests in this category yet.</p>
                            @endif
                        </div>
                    </article>
                @empty
                    <p class="py-8 text-center text-sm text-slate-400">Add a category to begin creating contests.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
