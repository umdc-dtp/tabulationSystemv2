<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AccountRole;
use App\Enums\CompetitionScoringMethod;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_real_metrics_and_circular_event_progress(): void
    {
        $user = User::factory()->create(['role' => AccountRole::Admin]);
        $partiallyFinalized = Event::factory()->for($user, 'creator')->create([
            'name' => 'Regional Games',
            'start_date' => today()->subDay(),
            'end_date' => today()->addDay(),
        ]);
        $category = $partiallyFinalized->categories()->create(['name' => 'Senior']);
        $category->competitions()->create([
            'name' => 'Contest One',
            'scores_finalized_at' => now(),
        ]);
        $category->competitions()->create(['name' => 'Contest Two']);

        $completed = Event::factory()->for($user, 'creator')->create([
            'name' => 'Completed Games',
        ]);
        $completedCategory = $completed->categories()->create(['name' => 'Open']);
        $completedCategory->competitions()->create([
            'name' => 'Final Contest',
            'scores_finalized_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Regional Games')
            ->assertSee('1 of 2 contests finalized')
            ->assertSee('Completed Games')
            ->assertSee('1 of 1 contest finalized')
            ->assertSee('50 percent of events finalized', false)
            ->assertSee('Event scoring progress');
    }

    public function test_contest_scores_can_be_finalized_and_reopened(): void
    {
        $user = User::factory()->create(['role' => AccountRole::Admin]);
        $event = Event::factory()->for($user, 'creator')->create();
        $participant = $event->participants()->create(['name' => 'Alex Santos']);
        $category = $event->categories()->create(['name' => 'Sports']);
        $competition = $category->competitions()->create([
            'name' => 'Basketball',
            'scoring_method' => CompetitionScoringMethod::Wins,
        ]);
        $competition->results()->create([
            'participant_id' => $participant->id,
            'wins' => 4,
        ]);

        $route = route('events.categories.competitions.scores.finalization.update', [
            $event,
            $category,
            $competition,
        ]);

        $this->actingAs($user)
            ->patch($route)
            ->assertRedirect(route('events.categories.competitions.scores.edit', [
                $event,
                $category,
                $competition,
            ]));

        $this->assertNotNull($competition->refresh()->scores_finalized_at);

        $this->actingAs($user)
            ->patch($route)
            ->assertRedirect();

        $this->assertNull($competition->refresh()->scores_finalized_at);
    }

    public function test_contest_without_scores_cannot_be_finalized(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'creator')->create();
        $category = $event->categories()->create(['name' => 'Sports']);
        $competition = $category->competitions()->create([
            'name' => 'Basketball',
            'scoring_method' => CompetitionScoringMethod::Wins,
        ]);

        $this->actingAs($user)
            ->patch(route('events.categories.competitions.scores.finalization.update', [
                $event,
                $category,
                $competition,
            ]))
            ->assertSessionHasErrors('finalization');

        $this->assertNull($competition->refresh()->scores_finalized_at);
    }

    public function test_finalized_contest_scores_are_locked_for_auditors(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'creator')->create();
        $participant = $event->participants()->create(['name' => 'Alex Santos']);
        $category = $event->categories()->create(['name' => 'Sports']);
        $competition = $category->competitions()->create([
            'name' => 'Basketball',
            'scoring_method' => CompetitionScoringMethod::Wins,
            'scores_finalized_at' => now(),
        ]);

        $this->actingAs($user)
            ->patch(route('events.categories.competitions.scores.update', [
                $event,
                $category,
                $competition,
            ]), [
                'wins' => [$participant->id => 5],
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->patch(route('events.categories.competitions.deductions.update', [
                $event,
                $category,
                $competition,
            ]), [
                'deductions' => [$participant->id => 1],
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->patch(route('events.categories.competitions.scores.finalization.update', [
                $event,
                $category,
                $competition,
            ]))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('events.categories.competitions.scores.edit', [
                $event,
                $category,
                $competition,
            ]))
            ->assertOk()
            ->assertSee('Locked for auditors')
            ->assertSee('Read only for auditors')
            ->assertDontSee('Reopen scores');

        $this->assertDatabaseCount('competition_results', 0);
    }
}
