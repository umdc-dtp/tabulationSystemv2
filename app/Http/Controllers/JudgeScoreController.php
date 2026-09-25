<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Competition;
use App\Models\CompetitionEntry;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class JudgeScoreController extends Controller
{
    public function update(
        Request $request,
        Event $event,
        Category $category,
        Competition $competition,
        CompetitionEntry $entry,
    ): RedirectResponse {
        abort_unless($competition->results_open || $competition->finalized_at !== null, 403);

        if ($competition->usesCriteriaScoring()) {
            $competition->load('criteria');
            $rules = [
                'scores' => ['required', 'array'],
                'deduction' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:999999.99'],
            ];

            for ($judgeNumber = 1; $judgeNumber <= $competition->judge_count; $judgeNumber++) {
                foreach ($competition->criteria as $criterion) {
                    $rules["scores.{$judgeNumber}.{$criterion->getKey()}"] = [
                        'nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:'.$criterion->max_score,
                    ];
                }
            }

            $data = $request->validate($rules);

            DB::transaction(function () use ($request, $competition, $entry, $data): void {
                if (! $competition->results_open) {
                    $competition->update(['results_open' => true]);
                }

                $entry->update(['deduction' => $data['deduction'] ?? $entry->deduction]);

                for ($judgeNumber = 1; $judgeNumber <= $competition->judge_count; $judgeNumber++) {
                    foreach ($competition->criteria as $criterion) {
                        $value = $request->input("scores.{$judgeNumber}.{$criterion->getKey()}");

                        if ($value === null || $value === '') {
                            $entry->judgeScores()->where('judge_number', $judgeNumber)
                                ->where('criterion_id', $criterion->getKey())->delete();

                            continue;
                        }

                        $entry->judgeScores()->updateOrCreate(
                            ['judge_number' => $judgeNumber, 'criterion_id' => $criterion->getKey()],
                            ['score' => $value],
                        );
                    }
                }
            });
        } else {
            $data = $request->validate([
                'win_total' => ['nullable', 'integer', 'min:0', 'max:1000000'],
                'loss_total' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            ]);
            DB::transaction(function () use ($competition, $entry, $data): void {
                if (! $competition->results_open) {
                    $competition->update(['results_open' => true]);
                }

                $entry->update([
                    'win_total' => $data['win_total'] ?? null,
                    'loss_total' => $data['loss_total'] ?? null,
                ]);
            });
        }

        return to_route('events.categories.competitions.scores.edit', [$event, $category, $competition])
            ->with('status', 'Draft result saved.');
    }
}
