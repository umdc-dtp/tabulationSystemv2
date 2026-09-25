<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CompetitionScoringMethod;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LeaderboardFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_averaged_judge_scores_create_dense_ranks_and_department_totals(): void
    {
        $auditor = User::factory()->create();
        $event = Event::factory()->for($auditor, 'creator')->create();
        $arts = $event->departments()->create(['name' => 'Arts']);
        $science = $event->departments()->create(['name' => 'Science']);
        $category = $event->categories()->create(['name' => 'Performance']);
        $competition = $category->competitions()->create(['name' => 'Dance', 'judge_count' => 2]);
        $criterion = $competition->criteria()->create(['name' => 'Technique', 'max_score' => 100]);
        $competition->rankScores()->createMany([
            ['rank' => 1, 'points' => 50],
            ['rank' => 2, 'points' => 30],
        ]);

        foreach ([['Alex', $arts, 90, 70], ['Bea', $arts, 80, 80], ['Cal', $science, 60, 60]] as [$name, $department, $first, $second]) {
            $participant = $event->participants()->create(['name' => $name, 'department_id' => $department->id]);
            $entry = $competition->entries()->create(['participant_id' => $participant->id, 'competed' => true]);
            $entry->judgeScores()->createMany([
                ['criterion_id' => $criterion->id, 'judge_number' => 1, 'score' => $first],
                ['criterion_id' => $criterion->id, 'judge_number' => 2, 'score' => $second],
            ]);
        }

        $this->actingAs($auditor)
            ->post(route('events.categories.competitions.finalize', [$event, $category, $competition]))
            ->assertRedirect();

        $this->assertDatabaseHas('finalized_results', ['competition_id' => $competition->id, 'entrant_name' => 'Alex', 'rank' => 1, 'points' => 50]);
        $this->assertDatabaseHas('finalized_results', ['competition_id' => $competition->id, 'entrant_name' => 'Bea', 'rank' => 1, 'points' => 50]);
        $this->assertDatabaseHas('finalized_results', ['competition_id' => $competition->id, 'entrant_name' => 'Cal', 'rank' => 2, 'points' => 30]);

        $this->getJson(route('leaderboards.data', $event))
            ->assertOk()
            ->assertJsonPath('rows.0.name', 'Arts')
            ->assertJsonPath('rows.0.points', '100.00')
            ->assertJsonPath('rows.1.name', 'Science')
            ->assertJsonPath('rows.1.points', '30.00');

        $this->getJson(route('leaderboards.data', $event).'?scope=category&category_id='.$category->id)
            ->assertJsonPath('rows.0.points', '100.00');

        $this->getJson(route('leaderboards.data', $event).'?scope=game&competition_id='.$competition->id)
            ->assertJsonPath('rows.0.name', 'Alex')
            ->assertJsonPath('rows.1.rank', 1)
            ->assertJsonPath('rows.2.rank', 2);

        $this->actingAs($auditor)->patch(route('events.leaderboard-freeze', $event))->assertRedirect();

        $this->getJson(route('leaderboards.data', $event))
            ->assertOk()
            ->assertJsonPath('frozen', true)
            ->assertJsonCount(0, 'rows');

        $this->actingAs($auditor)->getJson(route('events.leaderboard.data', $event))
            ->assertJsonPath('rows.0.points', '100.00');
    }

    public function test_score_corrections_keep_published_results_until_auditor_publishes_the_preview(): void
    {
        $this->withoutVite();

        $auditor = User::factory()->create();
        $event = Event::factory()->for($auditor, 'creator')->create();
        $arts = $event->departments()->create(['name' => 'Arts']);
        $science = $event->departments()->create(['name' => 'Science']);
        $category = $event->categories()->create(['name' => 'Sports']);
        $competition = $category->competitions()->create([
            'name' => 'Volleyball',
            'scoring_method' => CompetitionScoringMethod::Wins,
        ]);
        $competition->rankScores()->createMany([['rank' => 1, 'points' => 50], ['rank' => 2, 'points' => 20]]);

        foreach ([[$arts, 'Falcons', 3], [$science, 'Stars', 1]] as [$department, $name, $wins]) {
            $team = $event->teams()->create(['department_id' => $department->id, 'name' => $name, 'member_names' => [$name.' First', $name.' Second']]);
            $competition->entries()->create(['event_team_id' => $team->id, 'competed' => true, 'win_total' => $wins, 'loss_total' => 0]);
        }

        $this->actingAs($auditor)->post(route('events.categories.competitions.finalize', [$event, $category, $competition]))->assertRedirect();

        $this->getJson(route('leaderboards.data', $event))
            ->assertJsonPath('rows.0.name', 'Arts')
            ->assertJsonPath('rows.0.points', '50.00');

        $this->actingAs($auditor)->post(route('events.categories.competitions.reopen', [$event, $category, $competition]))->assertRedirect();
        $starsEntry = $competition->entries()->whereHas('team', fn ($query) => $query->where('name', 'Stars'))->sole();
        $this->actingAs($auditor)->patch(route('events.categories.competitions.entries.result.update', [$event, $category, $competition, $starsEntry]), ['win_total' => 5])
            ->assertRedirect();
        $this->actingAs($auditor)->post(route('events.categories.competitions.finalize', [$event, $category, $competition]))
            ->assertSessionHasErrors('results');
        $this->getJson(route('leaderboards.data', $event))->assertJsonPath('rows.0.name', 'Arts');

        $this->actingAs($auditor)->patch(route('events.categories.competitions.entries.result.update', [$event, $category, $competition, $starsEntry]), ['win_total' => 5, 'loss_total' => 1])->assertRedirect();

        $this->getJson(route('leaderboards.data', $event))
            ->assertJsonPath('rows.0.name', 'Arts')
            ->assertJsonPath('rows.0.points', '50.00');
        $this->actingAs($auditor)->getJson(route('events.leaderboard.data', $event))
            ->assertJsonPath('rows.0.name', 'Arts');
        $this->actingAs($auditor)->get(route('events.categories.competitions.show', [$event, $category, $competition]))
            ->assertSee('Draft standings preview')
            ->assertSee('Published standings')
            ->assertSee('5–1')
            ->assertSee('Publish corrections');

        $this->assertDatabaseHas('finalized_results', ['entrant_name' => 'Stars', 'rank' => 2, 'result_value' => 1]);

        $this->actingAs($auditor)->post(route('events.categories.competitions.finalize', [$event, $category, $competition]))->assertRedirect();

        $this->getJson(route('leaderboards.data', $event))
            ->assertJsonPath('rows.0.name', 'Science')
            ->assertJsonPath('rows.0.points', '50.00');
    }

    public function test_judge_score_corrections_keep_the_published_ranking_until_publish(): void
    {
        $this->withoutVite();

        $auditor = User::factory()->create();
        $event = Event::factory()->for($auditor, 'creator')->create();
        $department = $event->departments()->create(['name' => 'Arts']);
        $category = $event->categories()->create(['name' => 'Performance']);
        $competition = $category->competitions()->create(['name' => 'Dance']);
        $criterion = $competition->criteria()->create(['name' => 'Technique', 'max_score' => 100]);
        $competition->rankScores()->createMany([['rank' => 1, 'points' => 50], ['rank' => 2, 'points' => 20]]);

        foreach ([['Alex', 90], ['Bea', 80]] as [$name, $score]) {
            $participant = $event->participants()->create(['name' => $name, 'department_id' => $department->id]);
            $entry = $competition->entries()->create(['participant_id' => $participant->id, 'competed' => true]);
            $entry->judgeScores()->create(['criterion_id' => $criterion->id, 'judge_number' => 1, 'score' => $score]);
        }

        $this->actingAs($auditor)->post(route('events.categories.competitions.finalize', [$event, $category, $competition]))->assertRedirect();
        $this->actingAs($auditor)->post(route('events.categories.competitions.reopen', [$event, $category, $competition]))->assertRedirect();
        $beaEntry = $competition->entries()->whereHas('participant', fn ($query) => $query->where('name', 'Bea'))->sole();

        $this->actingAs($auditor)->patch(route('events.categories.competitions.entries.result.update', [$event, $category, $competition, $beaEntry]), [
            'scores' => [1 => [$criterion->id => 95]],
        ])->assertRedirect();

        $this->getJson(route('leaderboards.data', $event).'?scope=game&competition_id='.$competition->id)
            ->assertJsonPath('rows.0.name', 'Alex')
            ->assertJsonPath('rows.0.points', '50.00');
        $this->actingAs($auditor)->get(route('events.categories.competitions.show', [$event, $category, $competition]))
            ->assertViewHas('draftPreview', fn (array $rows): bool => $rows[0]['entrant_name'] === 'Bea' && $rows[0]['rank'] === 1);

        $this->actingAs($auditor)->post(route('events.categories.competitions.finalize', [$event, $category, $competition]))->assertRedirect();
        $this->getJson(route('leaderboards.data', $event).'?scope=game&competition_id='.$competition->id)
            ->assertJsonPath('rows.0.name', 'Bea')
            ->assertJsonPath('rows.0.points', '50.00');
    }

    public function test_adding_a_competitor_during_corrections_withdraws_stale_published_results(): void
    {
        $auditor = User::factory()->create();
        $event = Event::factory()->for($auditor, 'creator')->create();
        $department = $event->departments()->create(['name' => 'Arts']);
        $category = $event->categories()->create(['name' => 'Sports']);
        $competition = $category->competitions()->create(['name' => 'Race', 'scoring_method' => CompetitionScoringMethod::Wins]);
        $first = $event->participants()->create(['name' => 'Alex', 'department_id' => $department->id]);
        $second = $event->participants()->create(['name' => 'Bea', 'department_id' => $department->id]);
        $competition->entries()->create(['participant_id' => $first->id, 'competed' => true, 'win_total' => 3, 'loss_total' => 1]);
        $this->actingAs($auditor)->post(route('events.categories.competitions.finalize', [$event, $category, $competition]))->assertRedirect();
        $this->actingAs($auditor)->post(route('events.categories.competitions.reopen', [$event, $category, $competition]))->assertRedirect();

        $this->actingAs($auditor)->post(route('events.categories.competitions.entries.store', [$event, $category, $competition]), [
            'competitor' => 'participant:'.$second->id,
        ])->assertRedirect();

        $this->assertDatabaseCount('finalized_results', 0);
        $this->assertNull($competition->refresh()->finalized_at);
        $this->getJson(route('leaderboards.data', $event))->assertJsonCount(0, 'rows');
    }

    public function test_changing_participation_during_corrections_withdraws_stale_published_results(): void
    {
        $auditor = User::factory()->create();
        $event = Event::factory()->for($auditor, 'creator')->create();
        $department = $event->departments()->create(['name' => 'Arts']);
        $category = $event->categories()->create(['name' => 'Sports']);
        $competition = $category->competitions()->create(['name' => 'Race', 'scoring_method' => CompetitionScoringMethod::Wins]);
        $participant = $event->participants()->create(['name' => 'Alex', 'department_id' => $department->id]);
        $entry = $competition->entries()->create(['participant_id' => $participant->id, 'competed' => true, 'win_total' => 3, 'loss_total' => 1]);
        $this->actingAs($auditor)->post(route('events.categories.competitions.finalize', [$event, $category, $competition]))->assertRedirect();
        $this->actingAs($auditor)->post(route('events.categories.competitions.reopen', [$event, $category, $competition]))->assertRedirect();

        $this->actingAs($auditor)->patch(route('events.categories.competitions.entries.participation.update', [$event, $category, $competition, $entry]), [
            'competed' => false,
        ])->assertRedirect();

        $this->assertFalse($entry->refresh()->competed);
        $this->assertDatabaseCount('finalized_results', 0);
        $this->assertNull($competition->refresh()->finalized_at);
        $this->getJson(route('leaderboards.data', $event))->assertJsonCount(0, 'rows');
    }

    public function test_wins_are_ranked_by_fewer_losses_then_shared_records_tie(): void
    {
        $auditor = User::factory()->create();
        $event = Event::factory()->for($auditor, 'creator')->create();
        $category = $event->categories()->create(['name' => 'Sports']);
        $competition = $category->competitions()->create([
            'name' => 'Basketball',
            'scoring_method' => CompetitionScoringMethod::Wins,
        ]);
        $competition->rankScores()->createMany([['rank' => 1, 'points' => 50], ['rank' => 2, 'points' => 30]]);

        foreach ([['Falcons', 3, 2], ['Stars', 3, 1], ['Eagles', 3, 2]] as [$name, $wins, $losses]) {
            $department = $event->departments()->create(['name' => $name]);
            $team = $event->teams()->create(['name' => $name, 'department_id' => $department->id, 'member_names' => [$name.' Member']]);
            $competition->entries()->create(['event_team_id' => $team->id, 'competed' => true, 'win_total' => $wins, 'loss_total' => $losses]);
        }

        $this->actingAs($auditor)
            ->post(route('events.categories.competitions.finalize', [$event, $category, $competition]))
            ->assertRedirect();

        $this->assertDatabaseHas('finalized_results', ['entrant_name' => 'Stars', 'rank' => 1, 'loss_total' => 1, 'points' => 50]);
        $this->assertDatabaseHas('finalized_results', ['entrant_name' => 'Falcons', 'rank' => 2, 'loss_total' => 2, 'points' => 30]);
        $this->assertDatabaseHas('finalized_results', ['entrant_name' => 'Eagles', 'rank' => 2, 'loss_total' => 2, 'points' => 30]);

        $this->getJson(route('leaderboards.data', $event).'?scope=game&competition_id='.$competition->id)
            ->assertJsonPath('rows.0.name', 'Stars')
            ->assertJsonPath('rows.0.record', '3–1')
            ->assertJsonPath('rows.1.rank', 2)
            ->assertJsonPath('rows.1.record', '3–2')
            ->assertJsonPath('rows.2.rank', 2);
    }

    public function test_wins_based_results_require_valid_losses_before_finalizing(): void
    {
        $this->withoutVite();

        $auditor = User::factory()->create();
        $event = Event::factory()->for($auditor, 'creator')->create();
        $department = $event->departments()->create(['name' => 'Arts']);
        $category = $event->categories()->create(['name' => 'Sports']);
        $competition = $category->competitions()->create(['name' => 'Basketball', 'scoring_method' => CompetitionScoringMethod::Wins]);
        $participant = $event->participants()->create(['name' => 'Alex', 'department_id' => $department->id]);
        $entry = $competition->entries()->create(['participant_id' => $participant->id, 'competed' => true, 'win_total' => 3]);
        $resultRoute = route('events.categories.competitions.entries.result.update', [$event, $category, $competition, $entry]);
        $finalizeRoute = route('events.categories.competitions.finalize', [$event, $category, $competition]);

        $this->actingAs($auditor)->get(route('events.categories.competitions.entries.show', [$event, $category, $competition, $entry]))
            ->assertSee('Losses');

        $this->actingAs($auditor)->post($finalizeRoute)
            ->assertSessionHasErrors(['results' => 'Every competitor needs final win and loss totals.']);
        $this->assertDatabaseCount('finalized_results', 0);

        $this->actingAs($auditor)->patch($resultRoute, ['win_total' => 3, 'loss_total' => -1])
            ->assertSessionHasErrors('loss_total');
        $this->assertNull($entry->refresh()->loss_total);

        $this->actingAs($auditor)->patch($resultRoute, ['win_total' => 3, 'loss_total' => 1])
            ->assertRedirect();
        $this->assertSame(1, $entry->refresh()->loss_total);

        $this->actingAs($auditor)->post($finalizeRoute)->assertRedirect();
        $this->assertDatabaseHas('finalized_results', ['entrant_name' => 'Alex', 'loss_total' => 1]);
    }

    public function test_incomplete_scores_cannot_be_finalized_and_internal_page_requires_login(): void
    {
        $auditor = User::factory()->create();
        $event = Event::factory()->for($auditor, 'creator')->create();
        $department = $event->departments()->create(['name' => 'Arts']);
        $category = $event->categories()->create(['name' => 'Music']);
        $competition = $category->competitions()->create(['name' => 'Solo', 'judge_count' => 2]);
        $competition->criteria()->create(['name' => 'Sound', 'max_score' => 100]);
        $participant = $event->participants()->create(['name' => 'Alex', 'department_id' => $department->id]);
        $competition->entries()->create(['participant_id' => $participant->id, 'competed' => true]);

        $this->get(route('events.leaderboard.show', $event))->assertRedirect(route('login'));

        $this->actingAs($auditor)
            ->post(route('events.categories.competitions.finalize', [$event, $category, $competition]))
            ->assertSessionHasErrors('results');

        $this->assertDatabaseCount('finalized_results', 0);
        $this->assertNull($competition->refresh()->finalized_at);

        $this->getJson(route('leaderboards.data', $event))->assertJsonCount(0, 'rows');
    }

    public function test_auditor_can_open_game_entry_and_both_leaderboard_pages(): void
    {
        $this->withoutVite();

        $auditor = User::factory()->create();
        $event = Event::factory()->for($auditor, 'creator')->create(['name' => 'University Games']);
        $department = $event->departments()->create(['name' => 'Arts']);
        $category = $event->categories()->create(['name' => 'Performance']);
        $competition = $category->competitions()->create(['name' => 'Dance', 'judge_count' => 2]);
        $competition->criteria()->create(['name' => 'Technique', 'max_score' => 100]);
        $participant = $event->participants()->create(['name' => 'Alex', 'department_id' => $department->id]);
        $entry = $competition->entries()->create(['participant_id' => $participant->id, 'competed' => true]);

        $this->actingAs($auditor)
            ->get(route('events.categories.competitions.show', [$event, $category, $competition]))
            ->assertSee('Competitors and results')
            ->assertSee('Alex');

        $this->actingAs($auditor)
            ->get(route('events.categories.competitions.entries.show', [$event, $category, $competition, $entry]))
            ->assertSee('Judge 1')
            ->assertSee('Judge 2');

        $this->actingAs($auditor)
            ->get(route('events.leaderboard.show', $event))
            ->assertSee('University Games')
            ->assertSee('Share the public leaderboard');

        $this->get(route('leaderboards.show', $event))
            ->assertSee('University Games')
            ->assertDontSee('Share the public leaderboard');
    }

    public function test_competitors_from_another_event_cannot_be_added(): void
    {
        $auditor = User::factory()->create();
        $event = Event::factory()->for($auditor, 'creator')->create();
        $otherEvent = Event::factory()->for($auditor, 'creator')->create();
        $otherDepartment = $otherEvent->departments()->create(['name' => 'Other']);
        $otherParticipant = $otherEvent->participants()->create(['name' => 'Outsider', 'department_id' => $otherDepartment->id]);
        $category = $event->categories()->create(['name' => 'Sports']);
        $competition = $category->competitions()->create(['name' => 'Running']);

        $this->actingAs($auditor)
            ->post(route('events.categories.competitions.entries.store', [$event, $category, $competition]), ['competitor' => 'participant:'.$otherParticipant->id])
            ->assertSessionHasErrors('competitor');

        $this->assertDatabaseCount('competition_entries', 0);
    }

    public function test_absent_competitors_receive_no_participation_points(): void
    {
        $auditor = User::factory()->create();
        $event = Event::factory()->for($auditor, 'creator')->create();
        $department = $event->departments()->create(['name' => 'Arts']);
        $category = $event->categories()->create(['name' => 'Sports']);
        $competition = $category->competitions()->create([
            'name' => 'Race',
            'scoring_method' => CompetitionScoringMethod::Wins,
            'non_podium_points' => 5,
        ]);
        $first = $event->participants()->create(['name' => 'Alex', 'department_id' => $department->id]);
        $second = $event->participants()->create(['name' => 'Bea', 'department_id' => $department->id]);
        $competed = $competition->entries()->create(['participant_id' => $first->id, 'competed' => true, 'win_total' => 1, 'loss_total' => 0]);
        $absent = $competition->entries()->create(['participant_id' => $second->id, 'competed' => true]);

        $this->actingAs($auditor)
            ->patch(route('events.categories.competitions.entries.participation.update', [$event, $category, $competition, $absent]), ['competed' => false])
            ->assertRedirect();

        $this->actingAs($auditor)
            ->post(route('events.categories.competitions.finalize', [$event, $category, $competition]))
            ->assertRedirect();

        $this->assertDatabaseCount('finalized_results', 1);
        $this->assertDatabaseHas('finalized_results', ['entrant_name' => 'Alex', 'points' => 5]);
        $this->assertFalse($absent->refresh()->competed);
    }

    public function test_auditor_can_save_separate_judge_scores_and_finalize_their_average(): void
    {
        $auditor = User::factory()->create();
        $event = Event::factory()->for($auditor, 'creator')->create();
        $department = $event->departments()->create(['name' => 'Arts']);
        $category = $event->categories()->create(['name' => 'Performance']);
        $competition = $category->competitions()->create(['name' => 'Singing', 'judge_count' => 2]);
        $criterion = $competition->criteria()->create(['name' => 'Voice', 'max_score' => 100]);
        $participant = $event->participants()->create(['name' => 'Alex', 'department_id' => $department->id]);
        $entry = $competition->entries()->create(['participant_id' => $participant->id, 'competed' => true]);
        $resultRoute = route('events.categories.competitions.entries.result.update', [$event, $category, $competition, $entry]);

        $this->actingAs($auditor)
            ->patch($resultRoute, ['scores' => [1 => [$criterion->id => 101], 2 => [$criterion->id => 90]]])
            ->assertSessionHasErrors('scores.1.'.$criterion->id);

        $this->assertDatabaseCount('judge_scores', 0);

        $this->actingAs($auditor)
            ->patch($resultRoute, ['scores' => [1 => [$criterion->id => 80], 2 => [$criterion->id => 90]]])
            ->assertRedirect();

        $this->assertDatabaseCount('judge_scores', 2);

        $this->actingAs($auditor)
            ->post(route('events.categories.competitions.finalize', [$event, $category, $competition]))
            ->assertRedirect();

        $this->assertDatabaseHas('finalized_results', ['entrant_name' => 'Alex', 'result_value' => 85]);
    }

    public function test_category_and_game_filters_only_show_results_from_the_selected_part_of_an_event(): void
    {
        $event = Event::factory()->create();
        $arts = $event->departments()->create(['name' => 'Arts']);
        $science = $event->departments()->create(['name' => 'Science']);
        $music = $event->categories()->create(['name' => 'Music']);
        $sports = $event->categories()->create(['name' => 'Sports']);
        $singing = $music->competitions()->create(['name' => 'Singing', 'results_open' => false]);
        $running = $sports->competitions()->create(['name' => 'Running', 'scoring_method' => CompetitionScoringMethod::Wins, 'results_open' => false]);
        $singing->finalizedResults()->create(['department_id' => $arts->id, 'entrant_name' => 'Alex', 'rank' => 1, 'result_value' => 90, 'points' => 50]);
        $running->finalizedResults()->create(['department_id' => $science->id, 'entrant_name' => 'Bea', 'rank' => 1, 'result_value' => 3, 'points' => 80]);

        $this->getJson(route('leaderboards.data', $event).'?scope=category&category_id='.$music->id)
            ->assertJsonPath('rows.0.name', 'Arts')
            ->assertJsonPath('rows.0.points', '50.00')
            ->assertJsonPath('rows.1.name', 'Science')
            ->assertJsonPath('rows.1.points', '0.00');

        $this->getJson(route('leaderboards.data', $event).'?scope=game&competition_id='.$running->id)
            ->assertJsonPath('rows.0.name', 'Bea')
            ->assertJsonPath('rows.0.department', 'Science')
            ->assertJsonPath('rows.0.record', '3–—')
            ->assertJsonPath('rows.0.points', '80.00');
    }

    public function test_auditor_can_rank_registered_individuals_and_teams_together(): void
    {
        $this->withoutVite();

        $auditor = User::factory()->create();
        $event = Event::factory()->for($auditor, 'creator')->create();
        $arts = $event->departments()->create(['name' => 'Arts']);
        $science = $event->departments()->create(['name' => 'Science']);
        $category = $event->categories()->create(['name' => 'Sports']);
        $competition = $category->competitions()->create(['name' => 'Relay', 'scoring_method' => CompetitionScoringMethod::Wins]);
        $competition->rankScores()->createMany([['rank' => 1, 'points' => 50], ['rank' => 2, 'points' => 20]]);
        $participant = $event->participants()->create(['name' => 'Alex', 'department_id' => $arts->id]);
        $team = $event->teams()->create(['name' => 'Falcons', 'department_id' => $science->id, 'member_names' => ['Bea', 'Cal']]);
        $storeRoute = route('events.categories.competitions.entries.store', [$event, $category, $competition]);

        $this->actingAs($auditor)->post($storeRoute, ['competitor' => 'participant:'.$participant->id])->assertRedirect();
        $this->actingAs($auditor)->post($storeRoute, ['competitor' => 'team:'.$team->id])->assertRedirect();

        $teamEntry = $competition->entries()->whereNotNull('event_team_id')->sole();
        $individualEntry = $competition->entries()->whereNotNull('participant_id')->sole();
        $this->assertSame(['Bea', 'Cal'], $teamEntry->team->member_names);

        $this->actingAs($auditor)->patch(route('events.categories.competitions.entries.result.update', [$event, $category, $competition, $teamEntry]), ['win_total' => 3, 'loss_total' => 0])->assertRedirect();
        $this->actingAs($auditor)->patch(route('events.categories.competitions.entries.result.update', [$event, $category, $competition, $individualEntry]), ['win_total' => 1, 'loss_total' => 0])->assertRedirect();
        $this->actingAs($auditor)->post(route('events.categories.competitions.finalize', [$event, $category, $competition]))->assertRedirect();

        $this->getJson(route('leaderboards.data', $event).'?scope=game&competition_id='.$competition->id)
            ->assertJsonPath('rows.0.name', 'Falcons')
            ->assertJsonPath('rows.0.members.0', 'Bea')
            ->assertJsonPath('rows.1.name', 'Alex');
        $this->actingAs($auditor)->get(route('events.categories.competitions.show', [$event, $category, $competition]))
            ->assertSee('Bea, Cal');

        $this->actingAs($auditor)->patch(route('events.leaderboard-freeze', $event))->assertRedirect();
        $this->getJson(route('leaderboards.data', $event).'?scope=game&competition_id='.$competition->id)
            ->assertJsonPath('frozen', true)
            ->assertJsonCount(0, 'rows');
        $this->actingAs($auditor)->patch(route('events.leaderboard-freeze', $event))->assertRedirect();

        $this->actingAs($auditor)->post(route('events.categories.competitions.reopen', [$event, $category, $competition]))->assertRedirect();
        $this->getJson(route('leaderboards.data', $event).'?scope=game&competition_id='.$competition->id)
            ->assertJsonPath('rows.0.name', 'Falcons');
        $this->getJson(route('leaderboards.data', $event))->assertJsonPath('rows.0.name', 'Science');
        $this->getJson(route('leaderboards.data', $event).'?scope=category&category_id='.$category->id)
            ->assertJsonPath('rows.0.name', 'Science');
        $this->actingAs($auditor)->get(route('events.categories.competitions.show', [$event, $category, $competition]))
            ->assertSee('Registered participant or team')
            ->assertDontSee('Edit team and members');
        $this->actingAs($auditor)->post(route('events.categories.competitions.finalize', [$event, $category, $competition]))->assertRedirect();

        $this->getJson(route('leaderboards.data', $event).'?scope=game&competition_id='.$competition->id)
            ->assertJsonPath('rows.0.name', 'Falcons')
            ->assertJsonPath('rows.0.members.1', 'Cal');
        $this->assertDatabaseHas('finalized_results', ['competition_id' => $competition->id, 'entrant_name' => 'Falcons', 'rank' => 1, 'points' => 50]);
    }

    public function test_deleting_a_participant_invalidates_the_whole_game_and_removes_stale_results(): void
    {
        $auditor = User::factory()->create();
        $event = Event::factory()->for($auditor, 'creator')->create();
        $department = $event->departments()->create(['name' => 'Arts']);
        $category = $event->categories()->create(['name' => 'Sports']);
        $competition = $category->competitions()->create(['name' => 'Race', 'scoring_method' => CompetitionScoringMethod::Wins]);
        $competition->rankScores()->create(['rank' => 1, 'points' => 50]);
        $first = $event->participants()->create(['name' => 'Alex', 'department_id' => $department->id]);
        $second = $event->participants()->create(['name' => 'Bea', 'department_id' => $department->id]);
        $competition->entries()->create(['participant_id' => $first->id, 'competed' => true, 'win_total' => 3, 'loss_total' => 0]);
        $competition->entries()->create(['participant_id' => $second->id, 'competed' => true, 'win_total' => 1, 'loss_total' => 0]);
        $this->actingAs($auditor)->post(route('events.categories.competitions.finalize', [$event, $category, $competition]))->assertRedirect();

        $this->actingAs($auditor)->delete(route('events.participants.destroy', [$event, $first]))->assertRedirect();

        $this->assertDatabaseMissing('competition_entries', ['participant_id' => $first->id]);
        $this->assertDatabaseCount('finalized_results', 0);
        $this->assertNull($competition->refresh()->finalized_at);
        $this->assertTrue($competition->results_open);
        $this->getJson(route('leaderboards.data', $event))->assertJsonCount(0, 'rows');
    }

    public function test_renaming_a_registered_individual_withdraws_their_finalized_game_until_refinalization(): void
    {
        $auditor = User::factory()->create();
        $event = Event::factory()->for($auditor, 'creator')->create();
        $department = $event->departments()->create(['name' => 'Arts']);
        $category = $event->categories()->create(['name' => 'Sports']);
        $game = $category->competitions()->create(['name' => 'Race', 'scoring_method' => CompetitionScoringMethod::Wins]);
        $participant = $event->participants()->create(['name' => 'Alex', 'department_id' => $department->id]);
        $game->entries()->create(['participant_id' => $participant->id, 'competed' => true, 'win_total' => 3, 'loss_total' => 0]);
        $this->actingAs($auditor)->post(route('events.categories.competitions.finalize', [$event, $category, $game]))->assertRedirect();

        $this->actingAs($auditor)->patch(route('events.participants.update', [$event, $participant]), [
            'participant_name' => 'Alexandra', 'department_id' => $department->id,
        ])->assertRedirect();

        $this->assertSame('Alexandra', $participant->refresh()->name);
        $this->assertTrue($game->refresh()->results_open);
        $this->assertDatabaseCount('finalized_results', 0);
        $this->getJson(route('leaderboards.data', $event))->assertJsonCount(0, 'rows');
    }

    public function test_removing_a_competitor_from_reopened_results_discards_the_previous_snapshot(): void
    {
        $auditor = User::factory()->create();
        $event = Event::factory()->for($auditor, 'creator')->create();
        $department = $event->departments()->create(['name' => 'Arts']);
        $category = $event->categories()->create(['name' => 'Sports']);
        $competition = $category->competitions()->create(['name' => 'Race', 'scoring_method' => CompetitionScoringMethod::Wins]);
        $participant = $event->participants()->create(['name' => 'Alex', 'department_id' => $department->id]);
        $entry = $competition->entries()->create(['participant_id' => $participant->id, 'competed' => true, 'win_total' => 3, 'loss_total' => 0]);
        $this->actingAs($auditor)->post(route('events.categories.competitions.finalize', [$event, $category, $competition]))->assertRedirect();
        $this->actingAs($auditor)->post(route('events.categories.competitions.reopen', [$event, $category, $competition]))->assertRedirect();

        $this->actingAs($auditor)->delete(route('events.categories.competitions.entries.destroy', [$event, $category, $competition, $entry]))->assertRedirect();

        $this->assertDatabaseCount('competition_entries', 0);
        $this->assertDatabaseCount('finalized_results', 0);
        $this->assertNull($competition->refresh()->finalized_at);
        $this->actingAs($auditor)->get(route('events.categories.competitions.show', [$event, $category, $competition]))
            ->assertSee('No competitors entered yet.');
        $this->getJson(route('leaderboards.data', $event).'?scope=game&competition_id='.$competition->id)
            ->assertJsonCount(0, 'rows');
    }

    public function test_team_roster_requires_names_and_the_selected_department_must_belong_to_the_event(): void
    {
        $auditor = User::factory()->create();
        $event = Event::factory()->for($auditor, 'creator')->create();
        $otherEvent = Event::factory()->for($auditor, 'creator')->create();
        $otherDepartment = $otherEvent->departments()->create(['name' => 'Other']);
        $route = route('events.teams.store', $event);

        $this->actingAs($auditor)->post($route, [
            'team_name' => 'Falcons', 'department_id' => $otherDepartment->id,
            'member_names' => 'Bea',
        ])->assertSessionHasErrors('department_id');

        $department = $event->departments()->create(['name' => 'Arts']);
        $this->actingAs($auditor)->post($route, [
            'team_name' => 'Falcons', 'department_id' => $department->id,
            'member_names' => " \n ",
        ])->assertSessionHasErrors('member_names');

        $this->assertDatabaseCount('event_teams', 0);
    }

    public function test_deleting_an_individual_does_not_change_a_separately_registered_team_roster(): void
    {
        $auditor = User::factory()->create();
        $event = Event::factory()->for($auditor, 'creator')->create();
        $department = $event->departments()->create(['name' => 'Arts']);
        $category = $event->categories()->create(['name' => 'Sports']);
        $competition = $category->competitions()->create(['name' => 'Relay', 'scoring_method' => CompetitionScoringMethod::Wins]);
        $team = $event->teams()->create(['name' => 'Falcons', 'department_id' => $department->id, 'member_names' => ['Alex', 'Bea']]);
        $first = $event->participants()->create(['name' => 'Alex', 'department_id' => $department->id]);
        $competition->entries()->create(['event_team_id' => $team->id, 'competed' => true, 'win_total' => 3, 'loss_total' => 0]);
        $this->actingAs($auditor)->post(route('events.categories.competitions.finalize', [$event, $category, $competition]))->assertRedirect();

        $this->actingAs($auditor)->delete(route('events.participants.destroy', [$event, $first]))->assertRedirect();

        $this->assertSame(['Alex', 'Bea'], $team->refresh()->member_names);
        $this->assertDatabaseCount('competition_entries', 1);
        $this->assertDatabaseCount('finalized_results', 1);
        $this->getJson(route('leaderboards.data', $event).'?scope=game&competition_id='.$competition->id)
            ->assertJsonPath('rows.0.members.0', 'Alex');
    }
}
