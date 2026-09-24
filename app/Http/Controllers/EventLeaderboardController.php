<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\LeaderboardCalculator;
use Illuminate\View\View;

final class EventLeaderboardController extends Controller
{
    public function __construct(
        private readonly LeaderboardCalculator $leaderboard,
    ) {}

    public function show(Event $event): View
    {
        $event->load([
            'categories' => fn ($query) => $query->orderBy('name'),
            'categories.competitions' => fn ($query) => $query->orderBy('name'),
            'categories.competitions.criteria',
            'categories.competitions.rankScores',
            'categories.competitions.results.participant',
            'categories.competitions.results.criterionScores',
        ]);

        $standingsByCompetition = [];

        foreach ($event->categories as $category) {
            foreach ($category->competitions as $competition) {
                $standingsByCompetition[$competition->getKey()] = $this->leaderboard
                    ->competitionStandings($competition);
            }
        }

        return view('events.leaderboard', [
            'event' => $event,
            'overallStandings' => $this->leaderboard->overallStandings($event),
            'standingsByCompetition' => $standingsByCompetition,
        ]);
    }
}
