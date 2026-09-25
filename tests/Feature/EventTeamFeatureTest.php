<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CompetitionScoringMethod;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EventTeamFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_auditor_registers_a_team_once_and_selects_it_for_multiple_games(): void
    {
        $this->withoutVite();

        $auditor = User::factory()->create();
        $event = Event::factory()->for($auditor, 'creator')->create();
        $department = $event->departments()->create(['name' => 'Arts']);
        $category = $event->categories()->create(['name' => 'Sports']);
        $firstGame = $category->competitions()->create(['name' => 'Volleyball']);
        $secondGame = $category->competitions()->create(['name' => 'Relay']);

        $this->actingAs($auditor)->post(route('events.teams.store', $event), [
            'team_name' => 'Falcons', 'department_id' => $department->id, 'member_names' => "Bea\nCal",
        ])->assertRedirect();

        $team = $event->teams()->sole();
        $this->assertSame(['Bea', 'Cal'], $team->member_names);
        $this->actingAs($auditor)->get(route('events.show', $event))
            ->assertSee('Add individual')
            ->assertSee('Add team')
            ->assertSee('Bea, Cal');

        foreach ([$firstGame, $secondGame] as $game) {
            $this->actingAs($auditor)->post(route('events.categories.competitions.entries.store', [$event, $category, $game]), [
                'competitor' => 'team:'.$team->id,
            ])->assertRedirect();
        }

        $this->assertDatabaseCount('event_teams', 1);
        $this->assertDatabaseHas('competition_entries', ['competition_id' => $firstGame->id, 'event_team_id' => $team->id]);
        $this->assertDatabaseHas('competition_entries', ['competition_id' => $secondGame->id, 'event_team_id' => $team->id]);
        $this->actingAs($auditor)->get(route('events.categories.competitions.show', [$event, $category, $firstGame]))
            ->assertSee('Falcons')
            ->assertSee('Bea, Cal')
            ->assertDontSee('Team members (one name per line)');
    }

    public function test_editing_a_registered_team_invalidates_all_finalized_games_using_it(): void
    {
        $auditor = User::factory()->create();
        $event = Event::factory()->for($auditor, 'creator')->create();
        $department = $event->departments()->create(['name' => 'Arts']);
        $category = $event->categories()->create(['name' => 'Sports']);
        $team = $event->teams()->create(['name' => 'Falcons', 'department_id' => $department->id, 'member_names' => ['Bea']]);
        $games = collect(['Volleyball', 'Relay'])->map(function (string $name) use ($category, $team) {
            $game = $category->competitions()->create(['name' => $name, 'scoring_method' => CompetitionScoringMethod::Wins]);
            $game->entries()->create(['event_team_id' => $team->id, 'competed' => true, 'win_total' => 3, 'loss_total' => 0]);

            return $game;
        });

        foreach ($games as $game) {
            $this->actingAs($auditor)->post(route('events.categories.competitions.finalize', [$event, $category, $game]))->assertRedirect();
        }

        $this->actingAs($auditor)->patch(route('events.teams.update', [$event, $team]), [
            'team_name' => 'Hawks', 'department_id' => $department->id, 'member_names' => "Bea\nDee",
        ])->assertRedirect();

        $this->assertSame(['Bea', 'Dee'], $team->refresh()->member_names);
        $this->assertSame('Hawks', $team->name);
        $this->assertDatabaseCount('finalized_results', 0);
        foreach ($games as $game) {
            $this->assertTrue($game->refresh()->results_open);
            $this->assertNull($game->finalized_at);
        }
        $this->getJson(route('leaderboards.data', $event))->assertJsonCount(0, 'rows');
    }

    public function test_deleting_a_registered_team_removes_its_game_entries_and_finalized_results(): void
    {
        $auditor = User::factory()->create();
        $event = Event::factory()->for($auditor, 'creator')->create();
        $department = $event->departments()->create(['name' => 'Arts']);
        $category = $event->categories()->create(['name' => 'Sports']);
        $team = $event->teams()->create(['name' => 'Falcons', 'department_id' => $department->id, 'member_names' => ['Bea']]);
        $game = $category->competitions()->create(['name' => 'Relay', 'scoring_method' => CompetitionScoringMethod::Wins]);
        $game->entries()->create(['event_team_id' => $team->id, 'competed' => true, 'win_total' => 3, 'loss_total' => 0]);
        $this->actingAs($auditor)->post(route('events.categories.competitions.finalize', [$event, $category, $game]))->assertRedirect();

        $this->actingAs($auditor)->delete(route('events.teams.destroy', [$event, $team]))->assertRedirect();

        $this->assertDatabaseMissing('event_teams', ['id' => $team->id]);
        $this->assertDatabaseCount('competition_entries', 0);
        $this->assertDatabaseCount('finalized_results', 0);
        $this->assertTrue($game->refresh()->results_open);
    }

    public function test_a_team_from_another_event_cannot_be_entered_in_a_game(): void
    {
        $auditor = User::factory()->create();
        $event = Event::factory()->for($auditor, 'creator')->create();
        $otherEvent = Event::factory()->for($auditor, 'creator')->create();
        $department = $otherEvent->departments()->create(['name' => 'Other']);
        $team = $otherEvent->teams()->create(['name' => 'Visitors', 'department_id' => $department->id, 'member_names' => ['Bea']]);
        $category = $event->categories()->create(['name' => 'Sports']);
        $game = $category->competitions()->create(['name' => 'Relay']);

        $this->actingAs($auditor)->post(route('events.categories.competitions.entries.store', [$event, $category, $game]), [
            'competitor' => 'team:'.$team->id,
        ])->assertSessionHasErrors('competitor');

        $this->assertDatabaseCount('competition_entries', 0);
    }

    public function test_changing_a_team_to_a_department_already_represented_in_its_game_is_rejected(): void
    {
        $auditor = User::factory()->create();
        $event = Event::factory()->for($auditor, 'creator')->create();
        $arts = $event->departments()->create(['name' => 'Arts']);
        $science = $event->departments()->create(['name' => 'Science']);
        $category = $event->categories()->create(['name' => 'Sports']);
        $game = $category->competitions()->create(['name' => 'Relay']);
        $first = $event->teams()->create(['name' => 'Falcons', 'department_id' => $arts->id, 'member_names' => ['Bea']]);
        $second = $event->teams()->create(['name' => 'Stars', 'department_id' => $science->id, 'member_names' => ['Cal']]);
        $game->entries()->create(['event_team_id' => $first->id, 'competed' => true]);
        $game->entries()->create(['event_team_id' => $second->id, 'competed' => true]);

        $this->actingAs($auditor)->patch(route('events.teams.update', [$event, $second]), [
            'team_name' => 'Stars', 'department_id' => $arts->id, 'member_names' => 'Cal',
        ])->assertSessionHasErrors('department_id');

        $this->assertSame($science->id, $second->refresh()->department_id);
    }

    public function test_team_edit_route_does_not_expose_a_team_from_another_event(): void
    {
        $auditor = User::factory()->create();
        $event = Event::factory()->for($auditor, 'creator')->create();
        $otherEvent = Event::factory()->for($auditor, 'creator')->create();
        $department = $otherEvent->departments()->create(['name' => 'Other']);
        $team = $otherEvent->teams()->create(['name' => 'Visitors', 'department_id' => $department->id, 'member_names' => ['Bea']]);

        $this->actingAs($auditor)->patch(route('events.teams.update', [$event, $team]), [
            'team_name' => 'Stolen', 'department_id' => $department->id, 'member_names' => 'Bea',
        ])->assertNotFound();

        $this->assertSame('Visitors', $team->refresh()->name);
    }

    public function test_registered_team_can_be_scored_by_judges_in_a_criteria_game(): void
    {
        $auditor = User::factory()->create();
        $event = Event::factory()->for($auditor, 'creator')->create();
        $department = $event->departments()->create(['name' => 'Arts']);
        $team = $event->teams()->create(['name' => 'Falcons', 'department_id' => $department->id, 'member_names' => ['Bea', 'Cal']]);
        $category = $event->categories()->create(['name' => 'Performance']);
        $game = $category->competitions()->create(['name' => 'Dance']);
        $criterion = $game->criteria()->create(['name' => 'Technique', 'max_score' => 100]);
        $this->actingAs($auditor)->post(route('events.categories.competitions.entries.store', [$event, $category, $game]), [
            'competitor' => 'team:'.$team->id,
        ])->assertRedirect();
        $entry = $game->entries()->sole();

        $this->actingAs($auditor)->patch(route('events.categories.competitions.entries.result.update', [$event, $category, $game, $entry]), [
            'scores' => [1 => [$criterion->id => 80]],
        ])->assertRedirect();
        $this->actingAs($auditor)->post(route('events.categories.competitions.finalize', [$event, $category, $game]))->assertRedirect();

        $this->assertDatabaseHas('finalized_results', ['competition_id' => $game->id, 'entrant_name' => 'Falcons', 'result_value' => 80]);
        $this->getJson(route('leaderboards.data', $event).'?scope=game&competition_id='.$game->id)
            ->assertJsonPath('rows.0.members.1', 'Cal');
    }

    public function test_game_rejects_a_second_registered_team_from_the_same_department(): void
    {
        $auditor = User::factory()->create();
        $event = Event::factory()->for($auditor, 'creator')->create();
        $department = $event->departments()->create(['name' => 'Arts']);
        $first = $event->teams()->create(['name' => 'Falcons', 'department_id' => $department->id, 'member_names' => ['Bea']]);
        $second = $event->teams()->create(['name' => 'Hawks', 'department_id' => $department->id, 'member_names' => ['Cal']]);
        $category = $event->categories()->create(['name' => 'Sports']);
        $game = $category->competitions()->create(['name' => 'Relay']);
        $route = route('events.categories.competitions.entries.store', [$event, $category, $game]);
        $this->actingAs($auditor)->post($route, ['competitor' => 'team:'.$first->id])->assertRedirect();

        $this->actingAs($auditor)->post($route, ['competitor' => 'team:'.$second->id])
            ->assertSessionHasErrors('competitor');

        $this->assertDatabaseCount('competition_entries', 1);
    }
}
