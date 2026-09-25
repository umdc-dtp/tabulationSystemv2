<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ResetCompetitionScoresRequest;
use App\Models\Category;
use App\Models\Competition;
use App\Models\Event;
use App\Services\CompetitionManager;
use App\Services\CompetitionResultCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class CompetitionScoreController extends Controller
{
    public function edit(
        Event $event,
        Category $category,
        Competition $competition,
        CompetitionResultCalculator $calculator,
    ): View {
        $competition->load([
            'criteria' => fn ($query) => $query->orderBy('name'),
            'entries.participant.department',
            'entries.team.department',
            'entries.judgeScores',
            'finalizedResults.department',
        ]);

        $draftPreview = null;
        $draftPreviewError = null;

        if ($competition->results_open && $competition->entries->isNotEmpty()) {
            try {
                $draftPreview = $calculator->preview($competition);
            } catch (ValidationException $exception) {
                $draftPreviewError = $exception->errors()['results'][0] ?? 'Complete all results to preview standings.';
            }
        }

        return view('events.competitions.scores', compact(
            'event',
            'category',
            'competition',
            'draftPreview',
            'draftPreviewError',
        ));
    }

    public function reset(
        ResetCompetitionScoresRequest $request,
        Event $event,
        CompetitionManager $manager,
    ): RedirectResponse {
        $data = $request->validated();
        $count = $manager->resetScores(
            $event,
            $data['reset_scope'],
            $data['competition_ids'] ?? [],
        );

        $label = $count === 1 ? 'contest' : 'contests';

        return to_route('events.show', $event)
            ->with('status', "Scores were reset for {$count} {$label}.");
    }
}
