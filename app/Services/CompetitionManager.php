<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Competition;
use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CompetitionManager
{
    public function __construct(
        private readonly ActivitySubjectCollector $subjects,
    ) {}

    /**
     * @param  list<int|string>  $competitionIds
     */
    public function resetScores(Event $event, string $scope, array $competitionIds = []): int
    {
        $ids = array_values(array_unique(array_map(
            static fn (int|string $id): int => (int) $id,
            $competitionIds,
        )));

        $query = $event->competitions()->orderBy('competitions.id');
        $competitions = $scope === 'all'
            ? $query->get()
            : $query->whereKey($ids)->get();

        if ($scope === 'selected' && $competitions->count() !== count($ids)) {
            throw ValidationException::withMessages([
                'competition_ids' => 'Every selected contest must belong to this event.',
            ]);
        }

        if ($competitions->isEmpty()) {
            throw ValidationException::withMessages([
                'competition_ids' => $scope === 'all'
                    ? 'This event has no contests to reset.'
                    : 'Select at least one contest to reset.',
            ]);
        }

        DB::transaction(function () use ($competitions): void {
            foreach ($competitions as $competition) {
                $competition->entries()->with('judgeScores')->get()->each(function ($entry): void {
                    $entry->judgeScores()->delete();
                    $entry->update(['win_total' => null, 'loss_total' => null, 'deduction' => 0]);
                });
                $competition->finalizedResults()->delete();
                $competition->update(['finalized_at' => null, 'results_open' => true]);

                $this->subjects->capture('reset', $competition);
            }
        });

        return $competitions->count();
    }

    public function delete(Competition $competition): void
    {
        DB::transaction(static function () use ($competition): void {
            $competition->delete();
        });
    }
}
