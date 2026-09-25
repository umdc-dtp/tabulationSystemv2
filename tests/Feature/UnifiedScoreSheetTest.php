<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CompetitionScoringMethod;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UnifiedScoreSheetTest extends TestCase
{
    use RefreshDatabase;

    public function test_score_sheet_lists_only_registered_game_competitors_including_teams(): void
    {
        $this->withoutVite();
        $auditor = User::factory()->create();
        $event = Event::factory()->for($auditor, 'creator')->create();
        $department = $event->departments()->create(['name' => 'Arts']);
        $category = $event->categories()->create(['name' => 'Sports']);
        $competition = $category->competitions()->create(['name' => 'Relay', 'scoring_method' => CompetitionScoringMethod::Wins]);
        $registered = $event->participants()->create(['name' => 'Alex', 'department_id' => $department->id]);
        $unselected = $event->participants()->create(['name' => 'Unused Participant', 'department_id' => $department->id]);
        $team = $event->teams()->create(['name' => 'Falcons', 'department_id' => $department->id, 'member_names' => ['Bea', 'Cal']]);

        $this->actingAs($auditor)->post(route('events.categories.competitions.entries.store', [$event, $category, $competition]), ['competitor' => 'participant:'.$registered->id])->assertRedirect();
        $this->actingAs($auditor)->post(route('events.categories.competitions.entries.store', [$event, $category, $competition]), ['competitor' => 'team:'.$team->id])->assertRedirect();

        $this->actingAs($auditor)->get(route('events.categories.competitions.show', [$event, $category, $competition]))
            ->assertSee('Competitors')
            ->assertDontSee('Enter result');
        $this->actingAs($auditor)->get(route('events.categories.competitions.scores.edit', [$event, $category, $competition]))
            ->assertSee('Alex')
            ->assertSee('Falcons')
            ->assertSee('Bea, Cal')
            ->assertDontSee($unselected->name);
        $this->assertDatabaseCount('competition_entries', 2);
    }

    public function test_criteria_deduction_reduces_the_average_and_publishes_one_snapshot(): void
    {
        $this->withoutVite();
        $auditor = User::factory()->create();
        $event = Event::factory()->for($auditor, 'creator')->create();
        $department = $event->departments()->create(['name' => 'Arts']);
        $category = $event->categories()->create(['name' => 'Performance']);
        $competition = $category->competitions()->create(['name' => 'Dance', 'judge_count' => 2]);
        $criterion = $competition->criteria()->create(['name' => 'Technique', 'max_score' => 100]);
        $participant = $event->participants()->create(['name' => 'Alex', 'department_id' => $department->id]);
        $entry = $competition->entries()->create(['participant_id' => $participant->id, 'competed' => true]);

        $this->actingAs($auditor)->patch(route('events.categories.competitions.entries.result.update', [$event, $category, $competition, $entry]), [
            'scores' => [1 => [$criterion->id => 80], 2 => [$criterion->id => 100]],
            'deduction' => 10,
        ])->assertRedirect(route('events.categories.competitions.scores.edit', [$event, $category, $competition]));
        $this->actingAs($auditor)->post(route('events.categories.competitions.finalize', [$event, $category, $competition]))->assertRedirect();

        $this->assertDatabaseHas('competition_entries', ['id' => $entry->id, 'deduction' => 10]);
        $this->assertDatabaseHas('finalized_results', ['competition_id' => $competition->id, 'entrant_name' => 'Alex', 'result_value' => 80, 'deduction' => 10]);
        $this->getJson(route('leaderboards.data', $event).'?scope=game&competition_id='.$competition->id)
            ->assertJsonPath('rows.0.name', 'Alex')
            ->assertJsonPath('rows.0.result', '80.00');
    }

    public function test_editing_a_published_team_score_keeps_the_last_result_until_corrections_are_published(): void
    {
        $auditor = User::factory()->create();
        $event = Event::factory()->for($auditor, 'creator')->create();
        $department = $event->departments()->create(['name' => 'Arts']);
        $category = $event->categories()->create(['name' => 'Sports']);
        $competition = $category->competitions()->create(['name' => 'Volleyball', 'scoring_method' => CompetitionScoringMethod::Wins]);
        $team = $event->teams()->create(['name' => 'Falcons', 'department_id' => $department->id, 'member_names' => ['Bea', 'Cal']]);
        $entry = $competition->entries()->create(['event_team_id' => $team->id, 'competed' => true, 'win_total' => 3, 'loss_total' => 1]);

        $this->actingAs($auditor)->post(route('events.categories.competitions.finalize', [$event, $category, $competition]))->assertRedirect();
        $this->actingAs($auditor)->patch(route('events.categories.competitions.entries.result.update', [$event, $category, $competition, $entry]), [
            'win_total' => 5, 'loss_total' => 2,
        ])->assertRedirect();

        $this->assertTrue($competition->refresh()->results_open);
        $this->assertDatabaseHas('finalized_results', ['competition_id' => $competition->id, 'entrant_name' => 'Falcons', 'result_value' => 3, 'loss_total' => 1]);
        $this->getJson(route('leaderboards.data', $event).'?scope=game&competition_id='.$competition->id)
            ->assertJsonPath('rows.0.record', '3–1');

        $this->actingAs($auditor)->post(route('events.categories.competitions.finalize', [$event, $category, $competition]))->assertRedirect();

        $this->assertDatabaseHas('finalized_results', ['competition_id' => $competition->id, 'entrant_name' => 'Falcons', 'result_value' => 5, 'loss_total' => 2]);
    }
}
