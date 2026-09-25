<?php

declare(strict_types=1);

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CompetitionController;
use App\Http\Controllers\CompetitionScoreController;
use App\Http\Controllers\CriterionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventLeaderboardController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\RankScoreController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('admin')->group(function (): void {
        Route::resource('users', UserController::class)->only(['index', 'store', 'update']);
        Route::patch('/users/{user}/status', [UserController::class, 'toggleStatus'])
            ->name('users.toggle-status');
        Route::post('/events', [EventController::class, 'store'])->name('events.store');
        Route::delete('/events/{event}', [EventController::class, 'destroy'])
            ->name('events.destroy');
        Route::patch('/events/{event}/leaderboard-freeze', [EventController::class, 'toggleLeaderboardFreeze'])
            ->name('events.leaderboard-freeze');
        Route::patch('/events/{event}/competition-scores/reset', [CompetitionScoreController::class, 'reset'])
            ->name('events.competition-scores.reset');
        Route::get('/logs', [ActivityLogController::class, 'index'])->name('logs.index');
        Route::get('/logs/download', [ActivityLogController::class, 'download'])
            ->name('logs.download');
    });

    Route::resource('events', EventController::class)->only(['index', 'show']);
    Route::patch('/events/{event}/tie-ranking', [EventController::class, 'updateTieRanking'])
        ->name('events.tie-ranking.update');
    Route::get('/events/{event}/leaderboard', [EventLeaderboardController::class, 'show'])
        ->name('events.leaderboard');

    Route::scopeBindings()->group(function (): void {
        Route::post('/events/{event}/participants', [ParticipantController::class, 'store'])
            ->name('events.participants.store');
        Route::patch('/events/{event}/participants/{participant}', [ParticipantController::class, 'update'])
            ->name('events.participants.update');
        Route::delete('/events/{event}/participants/{participant}', [ParticipantController::class, 'destroy'])
            ->name('events.participants.destroy');

        Route::post('/events/{event}/categories', [CategoryController::class, 'store'])
            ->name('events.categories.store');
        Route::post(
            '/events/{event}/categories/{category}/competitions',
            [CompetitionController::class, 'store'],
        )->name('events.categories.competitions.store');
        Route::delete(
            '/events/{event}/categories/{category}/competitions/{competition}',
            [CompetitionController::class, 'destroy'],
        )->middleware('admin')->name('events.categories.competitions.destroy');
        Route::get(
            '/events/{event}/categories/{category}/competitions/{competition}',
            [CompetitionController::class, 'show'],
        )->name('events.categories.competitions.show');
        Route::patch(
            '/events/{event}/categories/{category}/competitions/{competition}/scoring-method',
            [CompetitionController::class, 'updateScoringMethod'],
        )->name('events.categories.competitions.scoring-method.update');
        Route::get(
            '/events/{event}/categories/{category}/competitions/{competition}/scores',
            [CompetitionScoreController::class, 'edit'],
        )->name('events.categories.competitions.scores.edit');
        Route::patch(
            '/events/{event}/categories/{category}/competitions/{competition}/scores',
            [CompetitionScoreController::class, 'update'],
        )->name('events.categories.competitions.scores.update');
        Route::patch(
            '/events/{event}/categories/{category}/competitions/{competition}/deductions',
            [CompetitionScoreController::class, 'updateDeductions'],
        )->name('events.categories.competitions.deductions.update');
        Route::patch(
            '/events/{event}/categories/{category}/competitions/{competition}/scores/finalization',
            [CompetitionScoreController::class, 'updateFinalization'],
        )->name('events.categories.competitions.scores.finalization.update');

        Route::post(
            '/events/{event}/categories/{category}/competitions/{competition}/criteria',
            [CriterionController::class, 'store'],
        )->name('events.categories.competitions.criteria.store');
        Route::delete(
            '/events/{event}/categories/{category}/competitions/{competition}/criteria/{criterion}',
            [CriterionController::class, 'destroy'],
        )->name('events.categories.competitions.criteria.destroy');

        Route::post(
            '/events/{event}/categories/{category}/competitions/{competition}/rank-scores',
            [RankScoreController::class, 'store'],
        )->name('events.categories.competitions.rank-scores.store');
        Route::patch(
            '/events/{event}/categories/{category}/competitions/{competition}/participation-points',
            [RankScoreController::class, 'updateParticipationPoints'],
        )->name('events.categories.competitions.participation-points.update');
        Route::delete(
            '/events/{event}/categories/{category}/competitions/{competition}/rank-scores/{rankScore}',
            [RankScoreController::class, 'destroy'],
        )->name('events.categories.competitions.rank-scores.destroy');
    });

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
