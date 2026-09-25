<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class ActivityLogger
{
    /** @var array<string, string> */
    private const DESCRIPTIONS = [
        'login.store' => 'Submitted a sign-in attempt',
        'events.store' => 'Created an event',
        'events.destroy' => 'Deleted an event',
        'events.tie-ranking.update' => 'Updated event tie ranking rule',
        'events.leaderboard-freeze' => 'Changed leaderboard freeze status',
        'events.participants.store' => 'Added a participant',
        'events.participants.update' => 'Updated a participant',
        'events.participants.destroy' => 'Deleted a participant',
        'events.categories.store' => 'Added a category',
        'events.categories.competitions.store' => 'Added a contest',
        'events.categories.competitions.destroy' => 'Deleted a contest',
        'events.competition-scores.reset' => 'Reset contest scores',
        'events.categories.competitions.scoring-method.update' => 'Changed a contest scoring method',
        'events.categories.competitions.scores.update' => 'Updated participant contest scores',
        'events.categories.competitions.deductions.update' => 'Updated participant score deductions',
        'events.categories.competitions.scores.finalization.update' => 'Changed contest score finalization',
        'events.categories.competitions.criteria.store' => 'Added a scoring criterion',
        'events.categories.competitions.criteria.destroy' => 'Deleted a scoring criterion',
        'events.categories.competitions.rank-scores.store' => 'Added a rank score',
        'events.categories.competitions.rank-scores.destroy' => 'Deleted a rank score',
        'events.categories.competitions.participation-points.update' => 'Updated participation points',
        'users.store' => 'Created a user account',
        'users.update' => 'Updated a user account',
        'users.toggle-status' => 'Changed a user account status',
    ];

    public function __construct(
        private readonly ActivitySubjectCollector $subjects,
    ) {}

    public function record(
        Request $request,
        ?Response $response,
        float $startedAt,
        ?User $actorBeforeRequest = null,
        ?Throwable $failure = null,
    ): void {
        if (! $this->shouldRecord($request)) {
            return;
        }

        $routeName = $request->route()?->getName();
        $actor = $actorBeforeRequest ?? $request->user();
        $statusCode = $response?->getStatusCode() ?? 500;
        $actorUsername = $actor?->username;

        if ($actorUsername === null && $routeName === 'login.store') {
            $actorUsername = $request->string('username')->limit(255)->toString() ?: null;
        }

        ActivityLog::query()->create([
            'user_id' => $actor?->getKey(),
            'actor_name' => $actor?->name,
            'actor_username' => $actorUsername,
            'interaction_type' => $this->interactionType($request, $routeName),
            'action' => $routeName ?? sprintf('%s %s', $request->method(), $request->path()),
            'description' => $this->description($routeName, $request, $statusCode),
            'method' => $request->method(),
            'route_name' => $routeName,
            'path' => '/'.$request->path(),
            'status_code' => $statusCode,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'duration_ms' => max(0, (int) round((microtime(true) - $startedAt) * 1000)),
            'context' => $this->context($request, $failure),
        ]);
    }

    private function shouldRecord(Request $request): bool
    {
        $routeName = $request->route()?->getName();

        if ($routeName === 'login.store') {
            return true;
        }

        if ($routeName === 'logout' || $request->is('_boost/*')) {
            return false;
        }

        return in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }

    private function interactionType(Request $request, ?string $routeName): string
    {
        if ($routeName === 'login.store') {
            return 'authentication';
        }

        return match ($request->method()) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'interaction',
        };
    }

    private function description(?string $routeName, Request $request, int $statusCode): string
    {
        if ($routeName === 'login.store') {
            return $request->user() instanceof User && $statusCode < 400
                ? 'Signed in successfully'
                : 'Sign-in attempt failed';
        }

        $description = self::DESCRIPTIONS[$routeName ?? '']
            ?? sprintf('Changed data through %s', '/'.$request->path());

        if ($statusCode === 403) {
            return 'Access denied: '.lcfirst($description);
        }

        if ($statusCode === 422) {
            return 'Validation failed: '.lcfirst($description);
        }

        if ($statusCode >= 400) {
            return 'Request failed: '.lcfirst($description);
        }

        return $description;
    }

    /** @return array<string, mixed>|null */
    private function context(Request $request, ?Throwable $failure): ?array
    {
        $resources = [];

        foreach ($request->route()?->parameters() ?? [] as $name => $value) {
            if ($value instanceof Model) {
                $resources[$name] = array_filter([
                    'type' => class_basename($value),
                    'id' => $value->getRouteKey(),
                    'label' => $value->getAttribute('name')
                        ?? $value->getAttribute('username'),
                ], static fn (mixed $item): bool => $item !== null && $item !== '');
            } elseif (is_scalar($value)) {
                $resources[$name] = (string) $value;
            }
        }

        $context = array_filter([
            'records' => $this->subjects->all(),
            'resources' => $resources,
            'failure_type' => $failure === null ? null : class_basename($failure),
        ], static fn (mixed $item): bool => $item !== null && $item !== []);

        return $context === [] ? null : $context;
    }
}
