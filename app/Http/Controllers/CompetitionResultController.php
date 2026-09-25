<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Competition;
use App\Models\Event;
use App\Services\CompetitionResultCalculator;
use Illuminate\Http\RedirectResponse;

final class CompetitionResultController extends Controller
{
    public function finalize(
        Event $event,
        Category $category,
        Competition $competition,
        CompetitionResultCalculator $calculator,
    ): RedirectResponse {
        abort_unless($competition->results_open, 403);

        $isCorrection = $competition->finalized_at !== null;
        $calculator->finalize($competition);

        return to_route('events.categories.competitions.show', [$event, $category, $competition])
            ->with('status', $isCorrection
                ? 'Corrections published and leaderboard updated.'
                : 'Game results finalized and leaderboard updated.');
    }

    public function reopen(Event $event, Category $category, Competition $competition): RedirectResponse
    {
        abort_unless(! $competition->results_open && $competition->finalized_at !== null, 403);

        $competition->update(['results_open' => true]);

        return to_route('events.categories.competitions.show', [$event, $category, $competition])
            ->with('status', 'Correction draft started. The last published standings remain visible until you publish changes.');
    }
}
