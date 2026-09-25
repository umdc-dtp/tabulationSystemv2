<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompetitionRequest;
use App\Http\Requests\UpdateCompetitionScoringMethodRequest;
use App\Models\Category;
use App\Models\Competition;
use App\Models\Event;
use App\Services\CompetitionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class CompetitionController extends Controller
{
    public function __construct(
        private readonly CompetitionManager $competitionManager,
    ) {}

    public function store(
        StoreCompetitionRequest $request,
        Event $event,
        Category $category,
    ): RedirectResponse {
        $category->competitions()->create([
            'name' => $request->validated('competition_name'),
        ]);

        return to_route('events.show', $event)
            ->with('status', 'Contest added successfully.');
    }

    public function show(
        Event $event,
        Category $category,
        Competition $competition,
    ): View {
        $competition->load([
            'criteria' => fn ($query) => $query->orderBy('name'),
            'rankScores' => fn ($query) => $query->orderBy('rank'),
            'entries.participant.department',
            'entries.team.department',
        ]);

        $event->load([
            'departments' => fn ($query) => $query->orderBy('name'),
            'participants' => fn ($query) => $query->with('department')->orderBy('name'),
            'teams' => fn ($query) => $query->with('department')->orderBy('name'),
        ]);

        return view('events.competitions.show', [
            'event' => $event,
            'category' => $category,
            'competition' => $competition,
        ]);
    }

    public function updateScoringMethod(
        UpdateCompetitionScoringMethodRequest $request,
        Event $event,
        Category $category,
        Competition $competition,
    ): RedirectResponse {
        if ($competition->entries()->exists() || $competition->finalized_at !== null) {
            throw ValidationException::withMessages(['scoring_method' => 'Remove entries before changing the scoring method.']);
        }

        $competition->update([
            'scoring_method' => $request->validated('scoring_method'),
        ]);

        return to_route('events.categories.competitions.show', [
            $event,
            $category,
            $competition,
        ])->with('status', 'Contest scoring method updated successfully.');
    }

    public function updateLeaderboardSettings(
        Request $request,
        Event $event,
        Category $category,
        Competition $competition,
    ): RedirectResponse {
        $data = $request->validate([
            'judge_count' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        if (($competition->entries()->exists() || $competition->finalized_at !== null)
            && $competition->judge_count !== (int) $data['judge_count']) {
            throw ValidationException::withMessages(['judge_count' => 'Remove entries before changing judge slots.']);
        }

        $competition->update($data);

        return to_route('events.categories.competitions.show', [$event, $category, $competition])
            ->with('status', 'Leaderboard settings updated.');
    }

    public function destroy(
        Event $event,
        Category $category,
        Competition $competition,
    ): RedirectResponse {
        $competitionName = $competition->name;

        $this->competitionManager->delete($competition);

        return to_route('events.show', $event)
            ->with('status', "{$competitionName} was deleted successfully.");
    }
}
