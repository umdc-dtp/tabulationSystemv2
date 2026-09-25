<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Competition;
use App\Models\CompetitionEntry;
use App\Models\Event;
use App\Services\CompetitionResultCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class CompetitionEntryController extends Controller
{
    public function store(Request $request, Event $event, Category $category, Competition $competition, CompetitionResultCalculator $calculator): RedirectResponse
    {
        $data = $request->validate(['competitor' => ['required', 'string', 'regex:/^(participant|team):[1-9][0-9]*$/']]);
        [$type, $identifier] = explode(':', $data['competitor']);

        DB::transaction(function () use ($type, $identifier, $event, $competition, $calculator): void {
            if ($type === 'team') {
                $team = $event->teams()->find($identifier);

                if ($team === null) {
                    throw ValidationException::withMessages(['competitor' => 'Select a team registered for this event.']);
                }

                if ($competition->entries()->whereHas('team', fn ($query) => $query->where('department_id', $team->department_id))->exists()) {
                    throw ValidationException::withMessages(['competitor' => 'This department already has a team in the game.']);
                }

                $competition->entries()->create(['event_team_id' => $team->getKey(), 'competed' => true]);
            } else {
                $participant = $event->participants()->find($identifier);

                if ($participant === null) {
                    throw ValidationException::withMessages(['competitor' => 'Select a participant registered for this event.']);
                }

                if ($participant->department_id === null) {
                    throw ValidationException::withMessages(['competitor' => 'Assign this participant to a department first.']);
                }

                if ($competition->entries()->where('participant_id', $participant->getKey())->exists()) {
                    throw ValidationException::withMessages(['competitor' => 'This participant is already entered in the game.']);
                }

                $competition->entries()->create(['participant_id' => $participant->getKey(), 'competed' => true]);
            }

            if ($competition->finalized_at !== null) {
                $calculator->invalidate($competition);
            }
        });

        return to_route('events.categories.competitions.show', [$event, $category, $competition])
            ->with('status', 'Competitor added to the game.');
    }

    public function show(Event $event, Category $category, Competition $competition, CompetitionEntry $entry): View
    {
        $competition->load(['criteria' => fn ($query) => $query->orderBy('name')]);
        $entry->load(['participant.department', 'team.department', 'judgeScores']);

        return view('events.competitions.entry', compact('event', 'category', 'competition', 'entry'));
    }

    public function updateParticipation(
        Request $request,
        Event $event,
        Category $category,
        Competition $competition,
        CompetitionEntry $entry,
        CompetitionResultCalculator $calculator,
    ): RedirectResponse {
        $data = $request->validate(['competed' => ['required', 'boolean']]);
        DB::transaction(function () use ($entry, $data, $competition, $calculator): void {
            $entry->update(['competed' => $data['competed']]);

            if ($competition->finalized_at !== null) {
                $calculator->invalidate($competition);
            }
        });

        return to_route('events.categories.competitions.show', [$event, $category, $competition])
            ->with('status', 'Participation updated.');
    }

    public function destroy(Event $event, Category $category, Competition $competition, CompetitionEntry $entry, CompetitionResultCalculator $calculator): RedirectResponse
    {
        DB::transaction(function () use ($entry, $competition, $calculator): void {
            $entry->delete();
            $calculator->invalidate($competition);
        });

        return to_route('events.categories.competitions.show', [$event, $category, $competition])
            ->with('status', 'Competitor removed from the game.');
    }
}
