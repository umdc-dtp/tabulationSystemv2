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
                <a href="{{ route('events.leaderboard.show', $event) }}" class="inline-flex h-11 items-center rounded-xl border border-white/30 px-4 text-sm font-semibold text-white hover:bg-white/10">Internal leaderboard</a>
                <a href="{{ route('leaderboards.show', $event) }}" target="_blank" rel="noopener" class="inline-flex h-11 items-center rounded-xl border border-white/30 px-4 text-sm font-semibold text-white hover:bg-white/10">Public page</a>
                <a href="{{ route('events.leaderboard', $event) }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-white/30 bg-maroon-950/30 px-5 text-sm font-semibold text-white transition hover:bg-white/10 focus:outline-none focus-visible:ring-4 focus-visible:ring-white/30">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M4 19V9m6 10V5m6 14v-7m4 7H2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Current leaderboard
                </a>

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

    <section class="mt-6 rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-5">
            <h3 class="text-lg font-semibold text-slate-950">Departments</h3>
            <p class="mt-1 text-sm text-slate-500">Create departments before assigning participants and recording results.</p>
        </div>
        <form method="POST" action="{{ route('events.departments.store', $event) }}" class="flex flex-col gap-3 border-b border-slate-200 p-5 sm:flex-row">
            @csrf
            <input name="name" value="{{ old('name') }}" required maxlength="255" placeholder="Department name" aria-label="Department name" class="h-11 flex-1 rounded-xl border border-slate-300 px-4 text-sm">
            <button class="h-11 rounded-xl bg-maroon-700 px-5 text-sm font-semibold text-white">Add department</button>
        </form>
        @error('name')<p class="px-5 py-2 text-sm text-red-600">{{ $message }}</p>@enderror
        <div class="grid gap-3 p-5 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($event->departments as $department)
                <form method="POST" action="{{ route('events.departments.update', [$event, $department]) }}" class="flex gap-2 rounded-xl bg-slate-50 p-3">
                    @csrf
                    @method('PATCH')
                    <input name="name" value="{{ $department->name }}" required maxlength="255" aria-label="Rename {{ $department->name }}" class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 text-sm">
                    <button class="rounded-lg border border-maroon-200 px-3 text-xs font-semibold text-maroon-700">Save</button>
                </form>
            @empty
                <p class="text-sm text-slate-500">No departments yet.</p>
            @endforelse
        </div>
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-950">Participants & teams</h3>
                        <p class="mt-1 text-sm text-slate-500">Register individuals or department teams for this event.</p>
                    </div>
                    <span class="rounded-full bg-maroon-50 px-2.5 py-1 text-xs font-semibold text-maroon-700">{{ $event->participants->count() + $event->teams->count() }}</span>
                </div>
            </div>

            <div class="border-b border-slate-200 bg-slate-50/70 p-5">
                <div class="mb-4 flex gap-2" role="group" aria-label="Registration type">
                    <button id="register-individual-button" type="button" class="rounded-xl border border-maroon-700 bg-maroon-700 px-4 py-2 text-sm font-semibold text-white" aria-pressed="true">Individual</button>
                    <button id="register-team-button" type="button" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700" aria-pressed="false">Team</button>
                </div>
            <form id="register-individual-form" method="POST" action="{{ route('events.participants.store', $event) }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <input type="hidden" name="registration_type" value="individual">
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
                    <label for="department_id" class="mb-1.5 block text-xs font-semibold text-slate-600">Department</label>
                    <select id="department_id" name="department_id" @required($event->departments->isNotEmpty()) class="h-11 w-full rounded-xl border border-slate-300 bg-white px-4 text-sm">
                        <option value="">Choose a department</option>
                        @foreach ($event->departments as $department)
                            <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                    @error('department_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="participant_image" class="mb-1.5 block text-xs font-semibold text-slate-600">Profile picture <span class="font-normal text-slate-400">(optional)</span></label>
                    <input id="participant_image" name="participant_image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="block w-full rounded-xl border border-slate-300 bg-white text-sm text-slate-500 file:mr-4 file:border-0 file:bg-maroon-50 file:px-4 file:py-3 file:text-sm file:font-semibold file:text-maroon-700 hover:file:bg-maroon-100">
                    <p class="mt-1.5 text-xs text-slate-400">JPG, PNG, or WebP up to 2 MB.</p>
                    @error('participant_image')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="h-11 w-full rounded-xl bg-maroon-700 px-5 text-sm font-semibold text-white transition hover:bg-maroon-800">Add individual</button>
            </form>
                <form id="register-team-form" method="POST" action="{{ route('events.teams.store', $event) }}" class="hidden space-y-3">
                    @csrf
                    <input type="hidden" name="registration_type" value="team">
                    <div><label for="new_team_name" class="mb-1.5 block text-xs font-semibold text-slate-600">Team name</label><input id="new_team_name" name="team_name" value="{{ old('team_name') }}" required class="h-11 w-full rounded-xl border border-slate-300 bg-white px-4 text-sm"></div>
                    <div><label for="new_team_department" class="mb-1.5 block text-xs font-semibold text-slate-600">Department</label><select id="new_team_department" name="department_id" required class="h-11 w-full rounded-xl border border-slate-300 bg-white px-4 text-sm"><option value="">Choose a department</option>@foreach ($event->departments as $department)<option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>@endforeach</select></div>
                    <div><label for="new_team_members" class="mb-1.5 block text-xs font-semibold text-slate-600">Team members (one name per line)</label><textarea id="new_team_members" name="member_names" rows="5" required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm">{{ old('member_names') }}</textarea></div>
                    <button type="submit" class="h-11 w-full rounded-xl bg-maroon-700 px-5 text-sm font-semibold text-white transition hover:bg-maroon-800">Add team</button>
                </form>
            </div>
            <script>
                (() => {
                    const individualButton = document.getElementById('register-individual-button');
                    const teamButton = document.getElementById('register-team-button');
                    const individualForm = document.getElementById('register-individual-form');
                    const teamForm = document.getElementById('register-team-form');
                    function choose(type) {
                        const isTeam = type === 'team';
                        individualForm.classList.toggle('hidden', isTeam);
                        teamForm.classList.toggle('hidden', !isTeam);
                        individualButton.setAttribute('aria-pressed', String(!isTeam));
                        teamButton.setAttribute('aria-pressed', String(isTeam));
                        individualButton.className = isTeam ? 'rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700' : 'rounded-xl border border-maroon-700 bg-maroon-700 px-4 py-2 text-sm font-semibold text-white';
                        teamButton.className = isTeam ? 'rounded-xl border border-maroon-700 bg-maroon-700 px-4 py-2 text-sm font-semibold text-white' : 'rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700';
                    }
                    individualButton.addEventListener('click', () => choose('individual'));
                    teamButton.addEventListener('click', () => choose('team'));
                    choose(@json(old('registration_type', 'individual')));
                })();
            </script>

            <div class="max-h-80 divide-y divide-slate-100 overflow-y-auto overscroll-contain focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-maroon-600" role="region" aria-label="Registered individual participants" tabindex="0">
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
                                <p class="mt-0.5 text-xs text-slate-400">{{ $participant->department?->name ?? 'Department unassigned' }} · {{ $participant->reference_no ?: 'No reference number' }}</p>
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
                                    <label for="department_id_{{ $participant->id }}" class="mb-1 block text-xs font-semibold text-slate-600">Department</label>
                                    <select id="department_id_{{ $participant->id }}" name="department_id" @required($event->departments->isNotEmpty()) class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm">
                                        <option value="">Unassigned</option>
                                        @foreach ($event->departments as $department)
                                            <option value="{{ $department->id }}" @selected($participant->department_id === $department->id)>{{ $department->name }}</option>
                                        @endforeach
                                    </select>
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
            <div class="border-t border-slate-200 px-5 py-4"><h4 class="text-sm font-semibold text-slate-900">Registered teams</h4></div>
            <div class="max-h-80 divide-y divide-slate-100 overflow-y-auto overscroll-contain focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-maroon-600" role="region" aria-label="Registered teams" tabindex="0">
                @forelse ($event->teams as $team)
                    <article class="px-5 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-900">{{ $team->name }}</p>
                                <p class="text-xs text-slate-500">{{ $team->department->name }} · {{ implode(', ', $team->member_names) }}</p>
                            </div>
                            <form method="POST" action="{{ route('events.teams.destroy', [$event, $team]) }}" onsubmit="return confirm('Delete this team and withdraw its results from every game?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-xs font-semibold text-red-600">Delete</button>
                            </form>
                        </div>
                        <details class="mt-3">
                            <summary class="cursor-pointer text-xs font-semibold text-maroon-700">Edit team</summary>
                            <form method="POST" action="{{ route('events.teams.update', [$event, $team]) }}" class="mt-3 space-y-3 rounded-xl bg-slate-50 p-4">
                                @csrf
                                @method('PATCH')
                                <div><label for="registered_team_name_{{ $team->id }}" class="mb-1 block text-xs font-semibold text-slate-600">Team name</label><input id="registered_team_name_{{ $team->id }}" name="team_name" value="{{ $team->name }}" required class="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm"></div>
                                <div><label for="registered_team_department_{{ $team->id }}" class="mb-1 block text-xs font-semibold text-slate-600">Department</label><select id="registered_team_department_{{ $team->id }}" name="department_id" required class="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm">@foreach ($event->departments as $department)<option value="{{ $department->id }}" @selected($team->department_id === $department->id)>{{ $department->name }}</option>@endforeach</select></div>
                                <div><label for="registered_team_members_{{ $team->id }}" class="mb-1 block text-xs font-semibold text-slate-600">Team members (one name per line)</label><textarea id="registered_team_members_{{ $team->id }}" name="member_names" rows="5" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ implode("\n", $team->member_names) }}</textarea></div>
                                <button class="h-10 w-full rounded-lg bg-maroon-700 px-4 text-sm font-semibold text-white">Save team</button>
                            </form>
                        </details>
                    </article>
                @empty
                    <p class="px-5 py-6 text-sm text-slate-400">No teams registered yet.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm xl:grid xl:h-0 xl:min-h-full xl:grid-rows-[auto_auto_minmax(0,1fr)]">
            <div class="border-b border-slate-200 px-6 py-5">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-950">Categories</h3>
                        <p class="mt-1 text-sm text-slate-500">Create divisions that will organize the contest cards below.</p>
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

            <div class="grid max-h-80 gap-3 overflow-y-auto overscroll-contain p-5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-maroon-600 sm:grid-cols-2 xl:max-h-none xl:min-h-0" role="region" aria-label="Categories and contests" tabindex="0">
                @forelse ($event->categories as $category)
                    <article class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                        <span class="grid h-9 w-9 place-items-center rounded-lg bg-maroon-100 text-sm font-bold text-maroon-700">{{ strtoupper(substr($category->name, 0, 1)) }}</span>
                        <h4 class="mt-3 font-semibold text-slate-900">{{ $category->name }}</h4>
                        <p class="mt-1 text-xs text-slate-400">{{ $category->competitions->count() }} {{ Str::plural('contest', $category->competitions->count()) }}</p>
                    </article>
                @empty
                    <p class="py-8 text-center text-sm text-slate-400 sm:col-span-2">Add a category to begin creating contests.</p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-5 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-slate-950">Contests by category</h3>
                <p class="mt-1 text-sm text-slate-500">Configure scoring rules or open the score sheet for each contest.</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">
                <a href="{{ route('events.leaderboard', $event) }}" class="inline-flex h-10 items-center justify-center rounded-xl border border-maroon-200 bg-maroon-50 px-4 text-sm font-semibold text-maroon-700 transition hover:bg-maroon-100">View current leaderboard</a>

                @if (auth()->user()->isAdmin() && $event->categories->contains(fn ($category) => $category->competitions->isNotEmpty()))
                    <form id="reset-contest-scores-form" method="POST" action="{{ route('events.competition-scores.reset', $event) }}" class="flex flex-col gap-2 sm:flex-row" data-loading-text="Resetting contest scores…">
                        @csrf
                        @method('PATCH')
                        <button type="submit" name="reset_scope" value="selected" onclick="return confirm('Reset scores for the selected contests? Score entries and finalization will be cleared, but contest configuration will be preserved.')" class="h-10 rounded-xl border border-amber-300 bg-amber-50 px-4 text-sm font-semibold text-amber-800 transition hover:bg-amber-100">Reset selected</button>
                        <button type="submit" name="reset_scope" value="all" onclick="return confirm('Reset scores for every contest in this event? This cannot be undone.')" class="h-10 rounded-xl border border-red-300 bg-red-50 px-4 text-sm font-semibold text-red-700 transition hover:bg-red-100">Reset all scores</button>
                    </form>
                @endif
            </div>
        </div>

        @if (auth()->user()->isAdmin())
            @error('competition_ids')
                <p class="border-b border-red-200 bg-red-50 px-6 py-3 text-sm font-medium text-red-700">{{ $message }}</p>
            @enderror
            @error('competition_ids.*')
                <p class="border-b border-red-200 bg-red-50 px-6 py-3 text-sm font-medium text-red-700">{{ $message }}</p>
            @enderror
        @endif

        <div class="space-y-6 p-5 sm:p-6">
            @forelse ($event->categories as $category)
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/50">
                    <div class="flex flex-col gap-3 border-b border-slate-200 bg-white px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h4 class="font-semibold text-slate-950">{{ $category->name }}</h4>
                            <p class="mt-0.5 text-xs text-slate-400">{{ $category->competitions->count() }} {{ Str::plural('contest', $category->competitions->count()) }}</p>
                        </div>

                        <form method="POST" action="{{ route('events.categories.competitions.store', [$event, $category]) }}" class="flex flex-col gap-2 sm:flex-row">
                            @csrf
                            <label for="competition_name_{{ $category->id }}" class="sr-only">Contest name for {{ $category->name }}</label>
                            <input id="competition_name_{{ $category->id }}" name="competition_name" type="text" required class="h-10 min-w-0 rounded-lg border border-slate-300 bg-white px-3 text-sm outline-none transition focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10 sm:w-64" placeholder="New contest name">
                            <button type="submit" class="h-10 rounded-lg bg-maroon-700 px-4 text-sm font-semibold text-white transition hover:bg-maroon-800">Add contest</button>
                        </form>
                    </div>

                    <div class="grid gap-4 p-4 md:grid-cols-2 xl:grid-cols-3">
                        @forelse ($category->competitions as $competition)
                            <article class="flex min-h-52 flex-col rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-maroon-50 text-maroon-700">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M8 21h8M12 17v4M7 4h10v4a5 5 0 0 1-10 0V4ZM7 6H4v1a4 4 0 0 0 4 4m9-5h3v1a4 4 0 0 1-4 4" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                        </span>
                                        @if (auth()->user()->isAdmin())
                                            <label class="flex cursor-pointer items-center gap-2 text-xs font-semibold text-slate-500">
                                                <input form="reset-contest-scores-form" name="competition_ids[]" type="checkbox" value="{{ $competition->id }}" class="rounded border-slate-300 text-maroon-700 focus:ring-maroon-600">
                                                Select
                                            </label>
                                        @endif
                                    </div>
                                    <span class="flex flex-col items-end gap-1.5">
                                        <span class="rounded-full bg-maroon-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-maroon-700">{{ $competition->scoring_method->label() }}</span>
                                        @if ($competition->scoresAreFinalized())
                                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-emerald-700">Scores finalized</span>
                                        @endif
                                    </span>
                                </div>
                                <h5 class="mt-4 text-lg font-semibold text-slate-950">{{ $competition->name }}</h5>
                                <p class="mt-1 text-xs text-slate-400">{{ $competition->criteria_count }} {{ Str::plural('criterion', $competition->criteria_count) }} · {{ $competition->results_count }} scored {{ Str::plural('participant', $competition->results_count) }}</p>

                                <div class="mt-auto grid grid-cols-2 gap-2 pt-5">
                                    <a href="{{ route('events.categories.competitions.show', [$event, $category, $competition]) }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-300 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Configure</a>
                                    <a href="{{ route('events.categories.competitions.scores.edit', [$event, $category, $competition]) }}" class="inline-flex h-10 items-center justify-center rounded-lg bg-maroon-700 text-sm font-semibold text-white transition hover:bg-maroon-800">Enter scores</a>
                                    @if (auth()->user()->isAdmin())
                                        <form method="POST" action="{{ route('events.categories.competitions.destroy', [$event, $category, $competition]) }}" class="col-span-2" data-loading-text="Deleting contest…" onsubmit="return confirm('Delete this contest? Its configuration and scores will be permanently removed.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="h-10 w-full rounded-lg border border-red-200 bg-red-50 text-sm font-semibold text-red-700 transition hover:bg-red-100">Delete contest</button>
                                        </form>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div class="rounded-xl border border-dashed border-slate-300 bg-white px-5 py-10 text-center md:col-span-2 xl:col-span-3">
                                <p class="text-sm text-slate-400">No contests in {{ $category->name }} yet.</p>
                            </div>
                        @endforelse
                    </div>
                </section>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 px-6 py-12 text-center">
                    <p class="font-semibold text-slate-700">No categories yet</p>
                    <p class="mt-1 text-sm text-slate-400">Add a category above to begin creating contest cards.</p>
                </div>
            @endforelse
        </div>
    </section>
@endsection
