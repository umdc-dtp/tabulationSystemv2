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
        abort_unless($competition->results_open, 403);

        if ($competition->usesCriteriaScoring()) {
            $competition->load('criteria');
            $rules = ['scores' => ['required', 'array']];

            for ($judgeNumber = 1; $judgeNumber <= $competition->judge_count; $judgeNumber++) {
                foreach ($competition->criteria as $criterion) {
                    $rules["scores.{$judgeNumber}.{$criterion->getKey()}"] = [
                        'nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:'.$criterion->max_score,
                    ];
                }
            }

            $request->validate($rules);

            DB::transaction(function () use ($request, $competition, $entry): void {
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
            $entry->update([
                'win_total' => $data['win_total'] ?? null,
                'loss_total' => $data['loss_total'] ?? null,
            ]);
        }

        return to_route('events.categories.competitions.entries.show', [$event, $category, $competition, $entry])
            ->with('status', 'Draft result saved.');
    }
}
