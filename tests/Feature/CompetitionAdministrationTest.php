<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AccountRole;
use App\Enums\CompetitionScoringMethod;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CompetitionAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reset_selected_scores_without_removing_competitors_or_configuration(): void
    {
        $admin = User::factory()->create(['role' => AccountRole::Admin]);
        $event = Event::factory()->for($admin, 'creator')->create();
        $department = $event->departments()->create(['name' => 'Arts']);
        $participant = $event->participants()->create(['name' => 'Alex Santos', 'department_id' => $department->id]);
        $category = $event->categories()->create(['name' => 'Senior']);
        $selected = $category->competitions()->create(['name' => 'Solo', 'finalized_at' => now(), 'results_open' => false]);
        $untouched = $category->competitions()->create(['name' => 'Team Sports', 'scoring_method' => CompetitionScoringMethod::Wins, 'finalized_at' => now(), 'results_open' => false]);
        $criterion = $selected->criteria()->create(['name' => 'Technique', 'max_score' => 100]);
        $rankScore = $selected->rankScores()->create(['rank' => 1, 'points' => 100]);
        $selectedEntry = $selected->entries()->create(['participant_id' => $participant->id, 'competed' => true, 'deduction' => 5]);
        $judgeScore = $selectedEntry->judgeScores()->create(['criterion_id' => $criterion->id, 'judge_number' => 1, 'score' => 95]);
        $selected->finalizedResults()->create(['department_id' => $department->id, 'entrant_name' => 'Alex Santos', 'rank' => 1, 'result_value' => 90, 'points' => 100]);
        $untouchedEntry = $untouched->entries()->create(['participant_id' => $participant->id, 'competed' => true, 'win_total' => 4, 'loss_total' => 0]);
        $untouched->finalizedResults()->create(['department_id' => $department->id, 'entrant_name' => 'Alex Santos', 'rank' => 1, 'result_value' => 4, 'points' => 100]);

        $this->actingAs($admin)->patch(route('events.competition-scores.reset', $event), [
            'reset_scope' => 'selected', 'competition_ids' => [$selected->id],
        ])->assertRedirect(route('events.show', $event))
            ->assertSessionHas('status', 'Scores were reset for 1 contest.');

        $this->assertModelExists($selectedEntry);
        $this->assertModelMissing($judgeScore);
        $this->assertSame('0.00', $selectedEntry->refresh()->deduction);
        $this->assertModelExists($untouchedEntry);
        $this->assertSame(4, $untouchedEntry->refresh()->win_total);
        $this->assertModelExists($criterion);
        $this->assertModelExists($rankScore);
        $this->assertNull($selected->refresh()->finalized_at);
        $this->assertNotNull($untouched->refresh()->finalized_at);
        $this->assertDatabaseMissing('finalized_results', ['competition_id' => $selected->id]);
        $this->assertDatabaseHas('finalized_results', ['competition_id' => $untouched->id]);

        $log = ActivityLog::query()->where('action', 'events.competition-scores.reset')->sole();
        $this->assertContains([
            'operation' => 'reset', 'type' => 'Competition', 'id' => (string) $selected->id, 'label' => 'Solo',
        ], $log->affectedRecords());
    }

    public function test_admin_can_reset_all_game_scores_without_removing_rosters(): void
    {
        $admin = User::factory()->create(['role' => AccountRole::Admin]);
        $event = Event::factory()->for($admin, 'creator')->create();
        $department = $event->departments()->create(['name' => 'Arts']);
        $participant = $event->participants()->create(['name' => 'Alex', 'department_id' => $department->id]);
        $category = $event->categories()->create(['name' => 'Sports']);

        foreach (['Basketball', 'Volleyball'] as $name) {
            $competition = $category->competitions()->create([
                'name' => $name, 'scoring_method' => CompetitionScoringMethod::Wins, 'finalized_at' => now(), 'results_open' => false,
            ]);
            $competition->entries()->create(['participant_id' => $participant->id, 'competed' => true, 'win_total' => 3, 'loss_total' => 0]);
            $competition->finalizedResults()->create(['department_id' => $department->id, 'entrant_name' => 'Alex', 'rank' => 1, 'result_value' => 3, 'points' => 50]);
        }

        $this->actingAs($admin)->patch(route('events.competition-scores.reset', $event), ['reset_scope' => 'all'])
            ->assertRedirect(route('events.show', $event));

        $this->assertDatabaseCount('competition_entries', 2);
        $this->assertDatabaseCount('finalized_results', 0);
        $this->assertSame(0, $event->competitions()->whereNotNull('finalized_at')->count());
        $this->assertSame(0, $event->competitions()->whereHas('entries', fn ($query) => $query->whereNotNull('win_total'))->count());
    }

    public function test_selected_reset_rejects_a_contest_from_another_event(): void
    {
        $admin = User::factory()->create(['role' => AccountRole::Admin]);
        $event = Event::factory()->for($admin, 'creator')->create();
        $otherEvent = Event::factory()->for($admin, 'creator')->create();
        $otherCompetition = $otherEvent->categories()->create(['name' => 'Other'])->competitions()->create(['name' => 'Other contest']);

        $this->actingAs($admin)->from(route('events.show', $event))
            ->patch(route('events.competition-scores.reset', $event), [
                'reset_scope' => 'selected', 'competition_ids' => [$otherCompetition->id],
            ])->assertRedirect(route('events.show', $event))
            ->assertSessionHasErrors('competition_ids');

        $this->assertModelExists($otherCompetition);
    }

    public function test_admin_can_delete_a_contest_and_its_related_records(): void
    {
        $admin = User::factory()->create(['role' => AccountRole::Admin]);
        $event = Event::factory()->for($admin, 'creator')->create();
        $department = $event->departments()->create(['name' => 'Arts']);
        $participant = $event->participants()->create(['name' => 'Alex', 'department_id' => $department->id]);
        $category = $event->categories()->create(['name' => 'Senior']);
        $competition = $category->competitions()->create(['name' => 'Solo']);
        $criterion = $competition->criteria()->create(['name' => 'Technique', 'max_score' => 100]);
        $entry = $competition->entries()->create(['participant_id' => $participant->id, 'competed' => true]);
        $judgeScore = $entry->judgeScores()->create(['criterion_id' => $criterion->id, 'judge_number' => 1, 'score' => 90]);
        $competition->finalizedResults()->create(['department_id' => $department->id, 'entrant_name' => 'Alex', 'rank' => 1, 'result_value' => 90, 'points' => 50]);

        $this->actingAs($admin)->delete(route('events.categories.competitions.destroy', [$event, $category, $competition]))
            ->assertRedirect(route('events.show', $event));

        $this->assertModelMissing($competition);
        $this->assertModelMissing($criterion);
        $this->assertModelMissing($entry);
        $this->assertModelMissing($judgeScore);
        $this->assertDatabaseCount('finalized_results', 0);
    }

    public function test_auditor_cannot_reset_scores_or_delete_contests(): void
    {
        $auditor = User::factory()->create(['role' => AccountRole::Auditor]);
        $event = Event::factory()->create();
        $category = $event->categories()->create(['name' => 'Senior']);
        $competition = $category->competitions()->create(['name' => 'Solo']);

        $this->actingAs($auditor)->patch(route('events.competition-scores.reset', $event), ['reset_scope' => 'all'])->assertForbidden();
        $this->actingAs($auditor)->delete(route('events.categories.competitions.destroy', [$event, $category, $competition]))->assertForbidden();
        $this->assertModelExists($competition);
    }
}
