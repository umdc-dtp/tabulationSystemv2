<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Competition;
use App\Models\CompetitionResult;
use App\Models\Participant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CompetitionScoreManager
{
    /**
     * @param  Collection<int, Participant>  $participants
     * @param  array<string, mixed>  $data
     */
    public function save(Competition $competition, Collection $participants, array $data): void
    {
        DB::transaction(function () use ($competition, $participants, $data): void {
            $competition->load([
                'criteria',
                'results.criterionScores',
            ]);

            if ($competition->usesCriteriaScoring()) {
                $this->saveCriteriaScores(
                    $competition,
                    $participants,
                    $data['scores'] ?? [],
                    $data['entries'] ?? [],
                );

                return;
            }

            $this->saveWins(
                $competition,
                $participants,
                $data['wins'] ?? [],
                $data['entries'] ?? [],
            );
        });
    }

    /** @param array<int|string, mixed> $deductions */
    public function saveDeductions(Competition $competition, array $deductions): void
    {
        DB::transaction(function () use ($competition, $deductions): void {
            $competition->load([
                'results.criterionScores',
            ]);

            foreach ($competition->results as $result) {
                $hasBaseScore = $competition->usesCriteriaScoring()
                    ? $result->criterionScores->isNotEmpty()
                    : $result->wins !== null;

                if (! $result->has_entry
                    || ! $hasBaseScore
                    || ! array_key_exists($result->participant_id, $deductions)) {
                    continue;
                }

                $value = $deductions[$result->participant_id];
                $result->update([
                    'deduction' => $value === null || $value === '' ? 0 : $value,
                ]);
            }
        });
    }

    /** @return bool True when the contest was finalized; false when it was reopened. */
    public function toggleFinalization(Competition $competition): bool
    {
        if ($competition->scoresAreFinalized()) {
            $competition->update(['scores_finalized_at' => null]);

            return false;
        }

        $hasScores = $competition->usesCriteriaScoring()
            ? $competition->results()->where('has_entry', true)->whereHas('criterionScores')->exists()
            : $competition->results()->where('has_entry', true)->whereNotNull('wins')->exists();

        if (! $hasScores) {
            throw ValidationException::withMessages([
                'finalization' => 'Enter at least one participant score before finalizing this contest.',
            ]);
        }

        $competition->update(['scores_finalized_at' => now()]);

        return true;
    }

    /**
     * @param  Collection<int, Participant>  $participants
     * @param  array<int|string, mixed>  $scores
     * @param  array<int|string, mixed>  $entries
     */
    private function saveCriteriaScores(
        Competition $competition,
        Collection $participants,
        array $scores,
        array $entries,
    ): void {
        $criteria = $competition->criteria;
        $results = $competition->results->keyBy('participant_id');

        foreach ($participants as $participant) {
            $participantScores = $scores[$participant->getKey()] ?? [];
            $participantScores = is_array($participantScores) ? $participantScores : [];
            $result = $results->get($participant->getKey());
            $hasEntry = array_key_exists($participant->getKey(), $entries)
                ? (bool) $entries[$participant->getKey()]
                : ($result?->has_entry ?? true);

            $hasScore = $criteria->contains(function ($criterion) use ($participantScores): bool {
                $value = $participantScores[$criterion->getKey()] ?? null;

                return $value !== null && $value !== '';
            });

            if (! $hasScore) {
                if (! $hasEntry) {
                    $result ??= $competition->results()->create([
                        'participant_id' => $participant->getKey(),
                        'has_entry' => false,
                    ]);
                    $results->put($participant->getKey(), $result);
                    $result->criterionScores()->delete();
                    $result->update([
                        'has_entry' => false,
                        'deduction' => 0,
                    ]);

                    continue;
                }

                if ($result instanceof CompetitionResult) {
                    $result->criterionScores()->delete();
                    $result->update(['has_entry' => true]);

                    if ($result->wins === null) {
                        $result->delete();
                    }
                }

                continue;
            }

            $result ??= $competition->results()->create([
                'participant_id' => $participant->getKey(),
            ]);
            $results->put($participant->getKey(), $result);
            $result->update($hasEntry
                ? ['has_entry' => true]
                : ['has_entry' => false, 'deduction' => 0]);

            foreach ($criteria as $criterion) {
                $value = $participantScores[$criterion->getKey()] ?? null;

                if ($value === null || $value === '') {
                    $result->criterionScores()
                        ->where('criterion_id', $criterion->getKey())
                        ->delete();

                    continue;
                }

                $result->criterionScores()->updateOrCreate(
                    ['criterion_id' => $criterion->getKey()],
                    ['score' => $value],
                );
            }
        }
    }

    /**
     * @param  Collection<int, Participant>  $participants
     * @param  array<int|string, mixed>  $wins
     * @param  array<int|string, mixed>  $entries
     */
    private function saveWins(
        Competition $competition,
        Collection $participants,
        array $wins,
        array $entries,
    ): void {
        $results = $competition->results->keyBy('participant_id');

        foreach ($participants as $participant) {
            $value = $wins[$participant->getKey()] ?? null;
            $result = $results->get($participant->getKey());
            $hasEntry = array_key_exists($participant->getKey(), $entries)
                ? (bool) $entries[$participant->getKey()]
                : ($result?->has_entry ?? true);

            if ($value === null || $value === '') {
                if (! $hasEntry) {
                    $result ??= $competition->results()->create([
                        'participant_id' => $participant->getKey(),
                        'has_entry' => false,
                    ]);
                    $results->put($participant->getKey(), $result);
                    $result->update([
                        'has_entry' => false,
                        'wins' => null,
                        'deduction' => 0,
                    ]);

                    continue;
                }

                if ($result instanceof CompetitionResult) {
                    $result->update([
                        'has_entry' => true,
                        'wins' => null,
                    ]);

                    if ($result->criterionScores->isEmpty()) {
                        $result->delete();
                    }
                }

                continue;
            }

            if ($result instanceof CompetitionResult) {
                $result->update($hasEntry
                    ? ['has_entry' => true, 'wins' => (int) $value]
                    : ['has_entry' => false, 'wins' => (int) $value, 'deduction' => 0]);

                continue;
            }

            $result = $competition->results()->create([
                'participant_id' => $participant->getKey(),
                'has_entry' => $hasEntry,
                'wins' => (int) $value,
            ]);
            $results->put($participant->getKey(), $result);
        }
    }
}
