<?php

declare(strict_types=1);

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CompetitionController;
use App\Http\Controllers\CompetitionEntryController;
use App\Http\Controllers\CompetitionResultController;
use App\Http\Controllers\CompetitionScoreController;
use App\Http\Controllers\CriterionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventLeaderboardController;
use App\Http\Controllers\EventTeamController;
use App\Http\Controllers\JudgeScoreController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\RankScoreController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('/leaderboards/{event}', [LeaderboardController::class, 'publicPage'])->name('leaderboards.show');
Route::get('/leaderboards/{event}/data', [LeaderboardController::class, 'publicData'])->name('leaderboards.data');

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
        Route::patch('/events/{event}/competition-scores/reset', [CompetitionScoreController::class, 'reset'])
            ->name('events.competition-scores.reset');
        Route::get('/logs', [ActivityLogController::class, 'index'])->name('logs.index');
        Route::get('/logs/download', [ActivityLogController::class, 'download'])
            ->name('logs.download');
    });

    Route::patch('/events/{event}/leaderboard-freeze', [EventController::class, 'toggleLeaderboardFreeze'])
        ->name('events.leaderboard-freeze');
    Route::resource('events', EventController::class)->only(['index', 'show']);
    Route::get('/events/{event}/leaderboard', [LeaderboardController::class, 'internal'])
        ->name('events.leaderboard.show');
    Route::get('/events/{event}/leaderboard/data', [LeaderboardController::class, 'internalData'])
        ->name('events.leaderboard.data');
    Route::get('/events/{event}/current-leaderboard', [EventLeaderboardController::class, 'show'])
        ->name('events.leaderboard');

    Route::scopeBindings()->group(function (): void {
        Route::post('/events/{event}/departments', [DepartmentController::class, 'store'])
            ->name('events.departments.store');
        Route::patch('/events/{event}/departments/{department}', [DepartmentController::class, 'update'])
            ->name('events.departments.update');
        Route::post('/events/{event}/participants', [ParticipantController::class, 'store'])
            ->name('events.participants.store');
        Route::patch('/events/{event}/participants/{participant}', [ParticipantController::class, 'update'])
            ->name('events.participants.update');
        Route::delete('/events/{event}/participants/{participant}', [ParticipantController::class, 'destroy'])
            ->name('events.participants.destroy');
        Route::post('/events/{event}/teams', [EventTeamController::class, 'store'])
            ->name('events.teams.store');
        Route::patch('/events/{event}/teams/{team}', [EventTeamController::class, 'update'])
            ->name('events.teams.update');
        Route::delete('/events/{event}/teams/{team}', [EventTeamController::class, 'destroy'])
            ->name('events.teams.destroy');

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
        Route::patch('/events/{event}/categories/{category}/competitions/{competition}/leaderboard-settings', [CompetitionController::class, 'updateLeaderboardSettings'])
            ->name('events.categories.competitions.leaderboard-settings.update');
        Route::post('/events/{event}/categories/{category}/competitions/{competition}/entries', [CompetitionEntryController::class, 'store'])
            ->name('events.categories.competitions.entries.store');
        Route::get('/events/{event}/categories/{category}/competitions/{competition}/entries/{entry}', [CompetitionEntryController::class, 'show'])
            ->name('events.categories.competitions.entries.show');
        Route::delete('/events/{event}/categories/{category}/competitions/{competition}/entries/{entry}', [CompetitionEntryController::class, 'destroy'])
            ->name('events.categories.competitions.entries.destroy');
        Route::patch('/events/{event}/categories/{category}/competitions/{competition}/entries/{entry}/participation', [CompetitionEntryController::class, 'updateParticipation'])
            ->name('events.categories.competitions.entries.participation.update');
        Route::patch('/events/{event}/categories/{category}/competitions/{competition}/entries/{entry}/result', [JudgeScoreController::class, 'update'])
            ->name('events.categories.competitions.entries.result.update');
        Route::post('/events/{event}/categories/{category}/competitions/{competition}/finalize', [CompetitionResultController::class, 'finalize'])
            ->name('events.categories.competitions.finalize');
        Route::post('/events/{event}/categories/{category}/competitions/{competition}/reopen', [CompetitionResultController::class, 'reopen'])
            ->name('events.categories.competitions.reopen');
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
