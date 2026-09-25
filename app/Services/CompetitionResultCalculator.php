<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Competition;
use App\Models\CompetitionEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CompetitionResultCalculator
{
    public function invalidate(Competition $competition): void
    {
        $competition->finalizedResults()->delete();
        $competition->update(['finalized_at' => null, 'results_open' => true]);
    }

    public function finalize(Competition $competition): void
    {
        DB::transaction(function () use ($competition): void {
            $competition = Competition::query()->lockForUpdate()->findOrFail($competition->getKey());
            abort_unless($competition->results_open, 403);

            $rows = $this->rankedRows($competition);
            $competition->finalizedResults()->delete();

            foreach ($rows as $row) {
                $competition->finalizedResults()->create($row);
            }

            $competition->update(['finalized_at' => now(), 'results_open' => false]);
        });
    }

    /** @return list<array{department_id: int, entrant_name: string, member_names: ?array, rank: int, result_value: float|int, loss_total: ?int, deduction: float, points: string|float|int}> */
    public function preview(Competition $competition): array
    {
        return $this->rankedRows($competition);
    }

    /** @return list<array{department_id: int, entrant_name: string, member_names: ?array, rank: int, result_value: float|int, loss_total: ?int, deduction: float, points: string|float|int}> */
    private function rankedRows(Competition $competition): array
    {
        $competition->load([
            'criteria', 'rankScores', 'entries.participant.department',
            'entries.team.department', 'entries.judgeScores',
        ]);

        $entries = $competition->entries->where('competed', true);

        if ($entries->isEmpty()) {
            throw ValidationException::withMessages(['results' => 'Select at least one competitor who competed.']);
        }

        if ($competition->usesCriteriaScoring() && $competition->criteria->isEmpty()) {
            throw ValidationException::withMessages(['results' => 'Add judging criteria before finalizing.']);
        }

        $rows = $entries->map(function (CompetitionEntry $entry) use ($competition): array {
            $department = $entry->participant?->department ?? $entry->team?->department;

            if ($department === null) {
                throw ValidationException::withMessages(['results' => 'Every competing entry needs a department.']);
            }

            $memberNames = $entry->team?->member_names;

            if ($entry->team !== null && $memberNames === []) {
                throw ValidationException::withMessages(['results' => 'Every competing team needs at least one member.']);
            }

            if ($competition->usesCriteriaScoring()) {
                $expected = $competition->criteria->count() * $competition->judge_count;

                if ($entry->judgeScores->count() !== $expected) {
                    throw ValidationException::withMessages(['results' => 'Every competitor needs a score from every judge for every criterion.']);
                }

                foreach ($entry->judgeScores as $score) {
                    $criterion = $competition->criteria->firstWhere('id', $score->criterion_id);

                    if ($criterion === null || $score->judge_number < 1 || $score->judge_number > $competition->judge_count || (float) $score->score > (float) $criterion->max_score) {
                        throw ValidationException::withMessages(['results' => 'A saved judge score is outside the current game criteria.']);
                    }
                }

                $totalCents = $entry->judgeScores->sum(fn ($score): int => (int) round((float) $score->score * 100));
                $deductionCents = (int) round((float) $entry->deduction * 100);
                $sortValue = max(0, (int) round($totalCents / $competition->judge_count) - $deductionCents);
                $displayValue = $sortValue / 100;
                $lossTotal = null;
            } else {
                if ($entry->win_total === null || $entry->loss_total === null) {
                    throw ValidationException::withMessages(['results' => 'Every competitor needs final win and loss totals.']);
                }

                $sortValue = $entry->win_total;
                $displayValue = $entry->win_total;
                $lossTotal = $entry->loss_total;
            }

            return [
                'department_id' => $department->getKey(),
                'entrant_name' => $entry->participant?->name ?? $entry->team->name,
                'member_names' => $memberNames,
                'result_value' => $displayValue,
                'sort_value' => $sortValue,
                'loss_total' => $lossTotal,
                'deduction' => $competition->usesCriteriaScoring() ? (float) $entry->deduction : 0.0,
            ];
        });

        $rows = ($competition->usesCriteriaScoring()
            ? $rows->sortByDesc('sort_value')
            : $rows->sort(fn (array $first, array $second): int => ($second['sort_value'] <=> $first['sort_value'])
                ?: ($first['loss_total'] <=> $second['loss_total'])))
            ->values();

        $rank = 0;
        $previousValue = null;
        $rankedRows = [];

        foreach ($rows as $row) {
            $rankKey = $competition->usesCriteriaScoring()
                ? (string) $row['sort_value']
                : $row['sort_value'].':'.$row['loss_total'];

            if ($previousValue !== $rankKey) {
                $rank++;
                $previousValue = $rankKey;
            }

            $points = $competition->rankScores->firstWhere('rank', $rank)?->points
                ?? $competition->non_podium_points;

            $rankedRows[] = [
                'department_id' => $row['department_id'],
                'entrant_name' => $row['entrant_name'],
                'member_names' => $row['member_names'],
                'rank' => $rank,
                'result_value' => $row['result_value'],
                'loss_total' => $row['loss_total'],
                'deduction' => $row['deduction'],
                'points' => $points,
            ];
        }

        return $rankedRows;
    }
}
