<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\CompetitionEntry;
use App\Models\Event;
use App\Models\EventTeam;
use App\Services\CompetitionResultCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class EventTeamController extends Controller
{
    public function store(Request $request, Event $event): RedirectResponse
    {
        $data = $this->validatedTeam($request, $event);

        $event->teams()->create($data);

        return to_route('events.show', $event)->with('status', 'Team registered successfully.');
    }

    public function update(Request $request, Event $event, EventTeam $team, CompetitionResultCalculator $calculator): RedirectResponse
    {
        $data = $this->validatedTeam($request, $event);
        $team->fill($data);

        if ($team->isDirty()) {
            DB::transaction(function () use ($team, $calculator): void {
                $competitionIds = $team->entries()->pluck('competition_id');

                if ($team->isDirty('department_id') && CompetitionEntry::query()
                    ->whereIn('competition_id', $competitionIds)
                    ->where('event_team_id', '!=', $team->getKey())
                    ->whereHas('team', fn ($query) => $query->where('department_id', $team->department_id))
                    ->exists()) {
                    throw ValidationException::withMessages(['department_id' => 'Another team from that department is already entered in one of this team’s games.']);
                }

                $team->save();

                Competition::query()->whereIn('id', $competitionIds)->get()
                    ->each(fn (Competition $competition) => $calculator->invalidate($competition));
            });
        }

        return to_route('events.show', $event)
            ->with('status', $team->wasChanged() ? 'Team updated. Games using it must be finalized again.' : 'No team changes were needed.');
    }

    public function destroy(Event $event, EventTeam $team, CompetitionResultCalculator $calculator): RedirectResponse
    {
        DB::transaction(function () use ($team, $calculator): void {
            $competitionIds = $team->entries()->pluck('competition_id');
            $team->delete();

            Competition::query()->whereIn('id', $competitionIds)->get()
                ->each(fn (Competition $competition) => $calculator->invalidate($competition));
        });

        return to_route('events.show', $event)
            ->with('status', 'Team deleted and its game results withdrawn.');
    }

    /** @return array{department_id: int|string, name: string, member_names: list<string>} */
    private function validatedTeam(Request $request, Event $event): array
    {
        $data = $request->validate([
            'team_name' => ['required', 'string', 'max:255'],
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')->where('event_id', $event->getKey())],
            'member_names' => ['required', 'string', 'max:10000'],
        ]);

        $names = collect(preg_split('/\R/u', $data['member_names']) ?: [])
            ->map(fn (string $name): string => trim($name))
            ->filter(fn (string $name): bool => $name !== '')
            ->values()
            ->all();

        if ($names === [] || count($names) > 100 || collect($names)->contains(fn (string $name): bool => Str::length($name) > 255)) {
            throw ValidationException::withMessages(['member_names' => 'Enter 1 to 100 member names, one per line (up to 255 characters each).']);
        }

        return [
            'name' => $data['team_name'],
            'department_id' => $data['department_id'],
            'member_names' => $names,
        ];
    }
}
