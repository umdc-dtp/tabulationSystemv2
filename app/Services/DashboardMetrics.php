<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Collection;

final class DashboardMetrics
{
    /**
     * @return array{
     *     events: Collection<int, array{
     *         event: Event,
     *         finalized: int,
     *         total: int,
     *         percentage: int,
     *         status: string
     *     }>,
     *     total_events: int,
     *     active_events: int,
     *     finalized_events: int,
     *     overall_percentage: int,
     *     registered_users: int,
     *     recent_activity_count: int,
     *     recent_logs: Collection<int, ActivityLog>
     * }
     */
    public function forAdmin(): array
    {
        $events = Event::query()
            ->withCount([
                'participants',
                'competitions',
                'competitions as finalized_competitions_count' => static fn ($query) => $query
                    ->whereNotNull('finalized_at'),
            ])
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();

        $eventProgress = $this->progress($events);

        $totalEvents = $events->count();
        $finalizedEvents = $eventProgress->filter(
            static fn (array $progress): bool => $progress['total'] > 0
                && $progress['finalized'] === $progress['total'],
        )->count();

        $logQuery = ActivityLog::query()
            ->whereIn('interaction_type', ['authentication', 'create', 'update', 'delete'])
            ->where('action', '!=', 'logout');

        return [
            'events' => $eventProgress,
            'total_events' => $totalEvents,
            'active_events' => $eventProgress->where('status', 'Active')->count(),
            'finalized_events' => $finalizedEvents,
            'overall_percentage' => $totalEvents === 0
                ? 0
                : (int) round(($finalizedEvents / $totalEvents) * 100),
            'registered_users' => User::query()->count(),
            'recent_activity_count' => (clone $logQuery)
                ->where('created_at', '>=', now()->subDay())
                ->count(),
            'recent_logs' => $logQuery
                ->latest()
                ->limit(6)
                ->get(),
        ];
    }

    /**
     * @return array{
     *     events: Collection<int, array{
     *         event: Event,
     *         finalized: int,
     *         total: int,
     *         percentage: int,
     *         status: string
     *     }>,
     *     event_count: int,
     *     participants_count: int,
     *     categories_count: int,
     *     competitions_count: int,
     *     frozen_count: int
     * }
     */
    public function forAuditor(): array
    {
        $today = today();
        $events = Event::query()
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->withCount([
                'participants',
                'categories',
                'competitions',
                'competitions as finalized_competitions_count' => static fn ($query) => $query
                    ->whereNotNull('finalized_at'),
            ])
            ->orderBy('end_date')
            ->orderBy('id')
            ->get();

        return [
            'events' => $this->progress($events),
            'event_count' => $events->count(),
            'participants_count' => (int) $events->sum('participants_count'),
            'categories_count' => (int) $events->sum('categories_count'),
            'competitions_count' => (int) $events->sum('competitions_count'),
            'frozen_count' => $events->where('leaderboard_frozen', true)->count(),
        ];
    }

    /**
     * @param  Collection<int, Event>  $events
     * @return Collection<int, array{
     *     event: Event,
     *     finalized: int,
     *     total: int,
     *     percentage: int,
     *     status: string
     * }>
     */
    private function progress(Collection $events): Collection
    {
        $today = today();

        return $events->map(function (Event $event) use ($today): array {
            $total = (int) $event->competitions_count;
            $finalized = (int) $event->finalized_competitions_count;

            return [
                'event' => $event,
                'finalized' => $finalized,
                'total' => $total,
                'percentage' => $total === 0
                    ? 0
                    : (int) round(($finalized / $total) * 100),
                'status' => $today->lt($event->start_date)
                    ? 'Upcoming'
                    : ($today->gt($event->end_date) ? 'Completed' : 'Active'),
            ];
        });
    }
}
