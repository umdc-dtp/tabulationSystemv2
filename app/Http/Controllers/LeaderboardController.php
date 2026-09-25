<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\Event;
use App\Models\FinalizedResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class LeaderboardController extends Controller
{
    public function internal(Event $event): View
    {
        return $this->page($event, true);
    }

    public function publicPage(Event $event): View
    {
        return $this->page($event, false);
    }

    public function internalData(Request $request, Event $event): JsonResponse
    {
        return $this->data($request, $event, true);
    }

    public function publicData(Request $request, Event $event): JsonResponse
    {
        return $this->data($request, $event, false);
    }

    private function page(Event $event, bool $internal): View
    {
        $event->load(['categories' => fn ($query) => $query->with('competitions')->orderBy('name')]);

        return view('events.leaderboard', [
            'event' => $event,
            'internal' => $internal,
            'dataUrl' => $internal
                ? route('events.leaderboard.data', $event)
                : route('leaderboards.data', $event),
        ]);
    }

    private function data(Request $request, Event $event, bool $internal): JsonResponse
    {
        $input = $request->validate([
            'scope' => ['nullable', 'in:overall,category,game'],
            'category_id' => ['nullable', 'integer'],
            'competition_id' => ['nullable', 'integer'],
        ]);

        if (! $internal && $event->leaderboard_frozen) {
            return response()->json(['frozen' => true, 'rows' => []])
                ->header('Cache-Control', 'no-store, private');
        }

        $scope = $input['scope'] ?? 'overall';
        $category = null;
        $competition = null;

        if ($scope === 'category') {
            $category = $event->categories()->findOrFail($input['category_id'] ?? null);
        }

        if ($scope === 'game') {
            $competition = Competition::query()
                ->whereHas('category', fn ($query) => $query->where('event_id', $event->getKey()))
                ->findOrFail($input['competition_id'] ?? null);
        }

        $results = FinalizedResult::query()
            ->with('department')
            ->whereHas('competition.category', fn ($query) => $query->where('event_id', $event->getKey()))
            ->when($category !== null, fn ($query) => $query->whereHas('competition', fn ($competitionQuery) => $competitionQuery->where('category_id', $category->getKey())))
            ->when($competition !== null, fn ($query) => $query->where('competition_id', $competition->getKey()))
            ->get();

        if ($scope === 'game') {
            $rows = $results->sortBy([['rank', 'asc'], ['entrant_name', 'asc']])->values()
                ->map(fn (FinalizedResult $result): array => [
                    'rank' => $result->rank,
                    'name' => $result->entrant_name,
                    'department' => $result->department->name,
                    'members' => $result->member_names ?? [],
                    'result' => $result->result_value,
                    'record' => $competition->usesCriteriaScoring()
                        ? null
                        : (int) $result->result_value.'–'.($result->loss_total ?? '—'),
                    'points' => $result->points,
                ])->all();
        } elseif ($results->isEmpty()) {
            $rows = [];
        } else {
            $totals = $results->groupBy('department_id')
                ->map(fn ($departmentResults): int => $departmentResults->sum(fn ($result): int => (int) round((float) $result->points * 100)));

            $departments = $event->departments()->orderBy('name')->get();
            $sorted = $departments->map(fn ($department): array => [
                'name' => $department->name,
                'total_cents' => $totals->get($department->getKey(), 0),
            ])->sort(fn (array $a, array $b): int => $b['total_cents'] <=> $a['total_cents'] ?: strcmp($a['name'], $b['name']))->values();

            $rank = 0;
            $previous = null;
            $rows = $sorted->map(function (array $row) use (&$rank, &$previous): array {
                if ($previous !== $row['total_cents']) {
                    $rank++;
                    $previous = $row['total_cents'];
                }

                return ['rank' => $rank, 'name' => $row['name'], 'points' => number_format($row['total_cents'] / 100, 2, '.', '')];
            })->all();
        }

        return response()->json(['frozen' => false, 'rows' => $rows])
            ->header('Cache-Control', 'no-store, private');
    }
}
