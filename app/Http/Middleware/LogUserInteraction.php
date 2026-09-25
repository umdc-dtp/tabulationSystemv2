<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ActivitySubjectCollector;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class LogUserInteraction
{
    public function __construct(
        private readonly ActivityLogger $logger,
        private readonly ActivitySubjectCollector $subjects,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->subjects->begin();

        $startedAt = microtime(true);
        $actorBeforeRequest = $request->user();
        $response = null;
        $failure = null;

        try {
            $response = $next($request);

            return $response;
        } catch (Throwable $exception) {
            $failure = $exception;

            throw $exception;
        } finally {
            try {
                $this->logger->record(
                    request: $request,
                    response: $response,
                    startedAt: $startedAt,
                    actorBeforeRequest: $actorBeforeRequest instanceof User
                        ? $actorBeforeRequest
                        : null,
                    failure: $failure,
                );
            } catch (Throwable $loggingFailure) {
                report($loggingFailure);
            } finally {
                $this->subjects->reset();
            }
        }
    }
}
