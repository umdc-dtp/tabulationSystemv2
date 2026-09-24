<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Competition;
use App\Models\Event;
use App\Models\Participant;
use Illuminate\Support\Collection;

final class LeaderboardCalculator
{
    /**
     * @return Collection<int, array{
     *     participant: Participant,
     *     rank: int,
     *     gross_score: float,
     *     deduction: float,
     *     raw_score: float,
     *     wins: ?int,
     *     leaderboard_points: float
     * }>
     */
    public function competitionStandings(Competition $competition): Collection
    {
        if (! $this->competitionRelationsAreLoaded($competition)) {
            $competition->load([
                'criteria',
                'rankScores',
                'results.participant',
                'results.criterionScores',
            ]);
        }

        $rows = $competition->results
            ->filter(fn ($result): bool => $competition->usesCriteriaScoring()
                ? $result->criterionScores->isNotEmpty()
                : $result->wins !== null)
            ->map(function ($result) use ($competition): array {
                $wins = $competition->usesCriteriaScoring() ? null : (int) $result->wins;
                $grossScore = $competition->usesCriteriaScoring()
                    ? (float) $result->criterionScores->sum(
                        static fn ($score): float => (float) $score->score,
                    )
                    : (float) $wins;
                $deduction = (float) $result->deduction;

                return [
                    'participant' => $result->participant,
                    'rank' => 0,
                    'gross_score' => $grossScore,
                    'deduction' => $deduction,
                    'raw_score' => max(0.0, $grossScore - $deduction),
                    'wins' => $wins,
                    'leaderboard_points' => 0.0,
                ];
            })
            ->sort(function (array $left, array $right): int {
                $scoreComparison = $right['raw_score'] <=> $left['raw_score'];

                return $scoreComparison !== 0
                    ? $scoreComparison
                    : strcasecmp($left['participant']->name, $right['participant']->name);
            })
            ->values();

        $previousScore = null;
        $rank = 0;

        return $rows->map(function (array $row, int $index) use (
            $competition,
            &$previousScore,
            &$rank,
        ): array {
            if ($previousScore === null || abs($row['raw_score'] - $previousScore) > 0.00001) {
                $rank = $index + 1;
            }

            $rankScore = $competition->rankScores->firstWhere('rank', $rank);
            $row['rank'] = $rank;
            $row['leaderboard_points'] = $rankScore === null
                ? (float) $competition->non_podium_points
                : (float) $rankScore->points;
            $previousScore = $row['raw_score'];

            return $row;
        });
    }

    /**
     * @return Collection<int, array{
     *     participant: Participant,
     *     contest_wins: int,
     *     recorded_wins: int,
     *     leaderboard_points: float,
     *     contests_entered: int
     * }>
     */
    public function overallStandings(Event $event): Collection
    {
        if (! $this->eventRelationsAreLoaded($event)) {
            $event->load([
                'categories.competitions.criteria',
                'categories.competitions.rankScores',
                'categories.competitions.results.participant',
                'categories.competitions.results.criterionScores',
            ]);
        }

        $totals = [];

        foreach ($event->categories as $category) {
            foreach ($category->competitions as $competition) {
                foreach ($this->competitionStandings($competition) as $row) {
                    $participant = $row['participant'];
                    $participantId = (string) $participant->getKey();
                    $totals[$participantId] ??= [
                        'participant' => $participant,
                        'contest_wins' => 0,
                        'recorded_wins' => 0,
                        'leaderboard_points' => 0.0,
                        'contests_entered' => 0,
                    ];

                    $totals[$participantId]['contest_wins'] += $row['rank'] === 1 ? 1 : 0;
                    $totals[$participantId]['recorded_wins'] += $row['wins'] ?? 0;
                    $totals[$participantId]['leaderboard_points'] += $row['leaderboard_points'];
                    $totals[$participantId]['contests_entered']++;
                }
            }
        }

        return collect(array_values($totals))
            ->sort(function (array $left, array $right): int {
                return ($right['contest_wins'] <=> $left['contest_wins'])
                    ?: ($right['recorded_wins'] <=> $left['recorded_wins'])
                    ?: ($right['leaderboard_points'] <=> $left['leaderboard_points'])
                    ?: strcasecmp($left['participant']->name, $right['participant']->name);
            })
            ->values();
    }

    private function competitionRelationsAreLoaded(Competition $competition): bool
    {
        if (! $competition->relationLoaded('criteria')
            || ! $competition->relationLoaded('rankScores')
            || ! $competition->relationLoaded('results')) {
            return false;
        }

        return $competition->results->every(
            static fn ($result): bool => $result->relationLoaded('participant')
                && $result->relationLoaded('criterionScores'),
        );
    }

    private function eventRelationsAreLoaded(Event $event): bool
    {
        if (! $event->relationLoaded('categories')) {
            return false;
        }

        return $event->categories->every(function ($category): bool {
            if (! $category->relationLoaded('competitions')) {
                return false;
            }

            return $category->competitions->every(
                fn (Competition $competition): bool => $this->competitionRelationsAreLoaded(
                    $competition,
                ),
            );
        });
    }
}
