<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ResetCompetitionScoresRequest;
use App\Http\Requests\UpdateCompetitionDeductionsRequest;
use App\Http\Requests\UpdateCompetitionFinalizationRequest;
use App\Http\Requests\UpdateCompetitionScoresRequest;
use App\Models\Category;
use App\Models\Competition;
use App\Models\Event;
use App\Services\CompetitionManager;
use App\Services\CompetitionScoreManager;
use App\Services\LeaderboardCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class CompetitionScoreController extends Controller
{
    public function __construct(
        private readonly CompetitionScoreManager $scoreManager,
        private readonly CompetitionManager $competitionManager,
        private readonly LeaderboardCalculator $leaderboard,
    ) {}

    public function edit(
        Event $event,
        Category $category,
        Competition $competition,
    ): View {
        $event->load([
            'participants' => fn ($query) => $query->orderBy('name'),
        ]);
        $competition->load([
            'criteria' => fn ($query) => $query->orderBy('name'),
            'rankScores',
            'results.participant',
            'results.criterionScores',
        ]);

        return view('events.competitions.scores', [
            'event' => $event,
            'category' => $category,
            'competition' => $competition,
            'resultsByParticipant' => $competition->results->keyBy('participant_id'),
            'standings' => $this->leaderboard->competitionStandings($competition, $event),
        ]);
    }

    public function update(
        UpdateCompetitionScoresRequest $request,
        Event $event,
        Category $category,
        Competition $competition,
    ): RedirectResponse {
        $event->load([
            'participants' => fn ($query) => $query->orderBy('name'),
        ]);

        $this->scoreManager->save(
            $competition,
            $event->participants,
            $request->validated(),
        );

        return to_route('events.categories.competitions.scores.edit', [
            $event,
            $category,
            $competition,
        ])->with('status', 'Participant scores saved successfully.');
    }

    public function updateFinalization(
        UpdateCompetitionFinalizationRequest $request,
        Event $event,
        Category $category,
        Competition $competition,
    ): RedirectResponse {
        $finalized = $this->scoreManager->toggleFinalization($competition);

        return to_route('events.categories.competitions.scores.edit', [
            $event,
            $category,
            $competition,
        ])->with(
            'status',
            $finalized
                ? 'Contest scores finalized successfully.'
                : 'Contest scores reopened for editing.',
        );
    }

    public function updateDeductions(
        UpdateCompetitionDeductionsRequest $request,
        Event $event,
        Category $category,
        Competition $competition,
    ): RedirectResponse {
        $this->scoreManager->saveDeductions(
            $competition,
            $request->validated('deductions'),
        );

        return to_route('events.categories.competitions.scores.edit', [
            $event,
            $category,
            $competition,
        ])->with('status', 'Participant deductions saved successfully.');
    }

    public function reset(
        ResetCompetitionScoresRequest $request,
        Event $event,
    ): RedirectResponse {
        $data = $request->validated();
        $count = $this->competitionManager->resetScores(
            $event,
            $data['reset_scope'],
            $data['competition_ids'] ?? [],
        );

        $label = $count === 1 ? 'contest' : 'contests';

        return to_route('events.show', $event)
            ->with('status', "Scores were reset for {$count} {$label}.");
    }
}
