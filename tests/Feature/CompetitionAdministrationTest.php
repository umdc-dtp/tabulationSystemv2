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

    public function test_admin_can_reset_selected_contest_scores_without_removing_configuration(): void
    {
        $admin = User::factory()->create(['role' => AccountRole::Admin]);
        $event = Event::factory()->for($admin, 'creator')->create();
        $participant = $event->participants()->create(['name' => 'Alex Santos']);
        $category = $event->categories()->create(['name' => 'Senior']);
        $selected = $category->competitions()->create([
            'name' => 'Solo',
            'scores_finalized_at' => now(),
        ]);
        $untouched = $category->competitions()->create([
            'name' => 'Team Sports',
            'scoring_method' => CompetitionScoringMethod::Wins,
            'scores_finalized_at' => now(),
        ]);
        $criterion = $selected->criteria()->create([
            'name' => 'Technique',
            'max_score' => 100,
        ]);
        $rankScore = $selected->rankScores()->create([
            'rank' => 1,
            'points' => 100,
        ]);
        $selectedResult = $selected->results()->create([
            'participant_id' => $participant->id,
        ]);
        $criterionScore = $selectedResult->criterionScores()->create([
            'criterion_id' => $criterion->id,
            'score' => 95,
        ]);
        $untouchedResult = $untouched->results()->create([
            'participant_id' => $participant->id,
            'wins' => 4,
        ]);

        $this->actingAs($admin)
            ->patch(route('events.competition-scores.reset', $event), [
                'reset_scope' => 'selected',
                'competition_ids' => [$selected->id],
            ])
            ->assertRedirect(route('events.show', $event))
            ->assertSessionHas('status', 'Scores were reset for 1 contest.');

        $this->assertModelMissing($selectedResult);
        $this->assertModelMissing($criterionScore);
        $this->assertModelExists($untouchedResult);
        $this->assertModelExists($criterion);
        $this->assertModelExists($rankScore);
        $this->assertNull($selected->refresh()->scores_finalized_at);
        $this->assertNotNull($untouched->refresh()->scores_finalized_at);

        $log = ActivityLog::query()
            ->where('action', 'events.competition-scores.reset')
            ->sole();

        $this->assertContains([
            'operation' => 'reset',
            'type' => 'Competition',
            'id' => (string) $selected->id,
            'label' => 'Solo',
        ], $log->affectedRecords());
    }

    public function test_admin_can_reset_scores_for_all_contests_in_an_event(): void
    {
        $admin = User::factory()->create(['role' => AccountRole::Admin]);
        $event = Event::factory()->for($admin, 'creator')->create();
        $participant = $event->participants()->create(['name' => 'Alex Santos']);
        $category = $event->categories()->create(['name' => 'Sports']);

        foreach (['Basketball', 'Volleyball'] as $name) {
            $competition = $category->competitions()->create([
                'name' => $name,
                'scoring_method' => CompetitionScoringMethod::Wins,
                'scores_finalized_at' => now(),
            ]);
            $competition->results()->create([
                'participant_id' => $participant->id,
                'wins' => 3,
            ]);
        }

        $this->actingAs($admin)
            ->patch(route('events.competition-scores.reset', $event), [
                'reset_scope' => 'all',
            ])
            ->assertRedirect(route('events.show', $event))
            ->assertSessionHas('status', 'Scores were reset for 2 contests.');

        $this->assertDatabaseCount('competition_results', 0);
        $this->assertSame(0, $event->competitions()->whereNotNull('scores_finalized_at')->count());
    }

    public function test_selected_reset_rejects_a_contest_from_another_event(): void
    {
        $admin = User::factory()->create(['role' => AccountRole::Admin]);
        $event = Event::factory()->for($admin, 'creator')->create();
        $otherEvent = Event::factory()->for($admin, 'creator')->create();
        $category = $otherEvent->categories()->create(['name' => 'Other']);
        $otherCompetition = $category->competitions()->create(['name' => 'Other contest']);

        $this->actingAs($admin)
            ->from(route('events.show', $event))
            ->patch(route('events.competition-scores.reset', $event), [
                'reset_scope' => 'selected',
                'competition_ids' => [$otherCompetition->id],
            ])
            ->assertRedirect(route('events.show', $event))
            ->assertSessionHasErrors('competition_ids');

        $this->assertModelExists($otherCompetition);
    }

    public function test_admin_can_delete_a_contest_and_its_related_records(): void
    {
        $admin = User::factory()->create(['role' => AccountRole::Admin]);
        $event = Event::factory()->for($admin, 'creator')->create();
        $participant = $event->participants()->create(['name' => 'Alex Santos']);
        $category = $event->categories()->create(['name' => 'Senior']);
        $competition = $category->competitions()->create(['name' => 'Solo']);
        $criterion = $competition->criteria()->create(['name' => 'Technique', 'max_score' => 100]);
        $rankScore = $competition->rankScores()->create(['rank' => 1, 'points' => 100]);
        $result = $competition->results()->create(['participant_id' => $participant->id]);
        $criterionScore = $result->criterionScores()->create([
            'criterion_id' => $criterion->id,
            'score' => 90,
        ]);

        $this->actingAs($admin)
            ->delete(route('events.categories.competitions.destroy', [
                $event,
                $category,
                $competition,
            ]))
            ->assertRedirect(route('events.show', $event))
            ->assertSessionHas('status', 'Solo was deleted successfully.');

        $this->assertModelMissing($competition);
        $this->assertModelMissing($criterion);
        $this->assertModelMissing($rankScore);
        $this->assertModelMissing($result);
        $this->assertModelMissing($criterionScore);

        $log = ActivityLog::query()
            ->where('action', 'events.categories.competitions.destroy')
            ->sole();

        $this->assertContains([
            'operation' => 'deleted',
            'type' => 'Competition',
            'id' => (string) $competition->id,
            'label' => 'Solo',
        ], $log->affectedRecords());
    }

    public function test_auditor_cannot_reset_scores_or_delete_contests(): void
    {
        $auditor = User::factory()->create(['role' => AccountRole::Auditor]);
        $event = Event::factory()->create();
        $category = $event->categories()->create(['name' => 'Senior']);
        $competition = $category->competitions()->create(['name' => 'Solo']);

        $this->actingAs($auditor)
            ->patch(route('events.competition-scores.reset', $event), [
                'reset_scope' => 'all',
            ])
            ->assertForbidden();

        $this->actingAs($auditor)
            ->delete(route('events.categories.competitions.destroy', [
                $event,
                $category,
                $competition,
            ]))
            ->assertForbidden();

        $this->actingAs($auditor)
            ->get(route('events.show', $event))
            ->assertOk()
            ->assertDontSee('Reset selected')
            ->assertDontSee('Reset all scores')
            ->assertDontSee('Delete contest');

        $this->assertModelExists($competition);
    }
}
