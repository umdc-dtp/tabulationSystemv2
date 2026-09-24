<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\ActivityLog;
use App\Services\ActivitySubjectCollector;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(
            ActivitySubjectCollector::class,
            static fn (): ActivitySubjectCollector => new ActivitySubjectCollector,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach (['created', 'updated', 'deleted'] as $operation) {
            Event::listen(
                "eloquent.{$operation}: *",
                function (string $_eventName, array $models) use ($operation): void {
                    $model = $models[0] ?? null;

                    if ($model instanceof Model && ! $model instanceof ActivityLog) {
                        app(ActivitySubjectCollector::class)->capture($operation, $model);
                    }
                },
            );
        }
    }
}
