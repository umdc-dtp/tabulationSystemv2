<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AccountRole;
use App\Enums\CompetitionScoringMethod;
use App\Enums\TieRankingMethod;
use App\Models\Event;
use App\Models\User;
use App\Services\LeaderboardCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CompetitionScoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_criteria_scores_are_saved_and_ranked_with_leaderboard_points(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'creator')->create();
        $alex = $event->participants()->create(['name' => 'Alex Santos']);
        $bea = $event->participants()->create(['name' => 'Bea Cruz']);
        $category = $event->categories()->create(['name' => 'Senior']);
        $competition = $category->competitions()->create(['name' => 'Solo']);
        $technique = $competition->criteria()->create([
            'name' => 'Technique',
            'max_score' => 50,
        ]);
        $presentation = $competition->criteria()->create([
            'name' => 'Presentation',
            'max_score' => 50,
        ]);
        $competition->rankScores()->createMany([
            ['rank' => 1, 'points' => 100],
            ['rank' => 2, 'points' => 75],
        ]);

        $this->actingAs($user)
            ->patch(route('events.categories.competitions.scores.update', [
                $event,
                $category,
                $competition,
            ]), [
                'scores' => [
                    $alex->id => [
                        $technique->id => 45,
                        $presentation->id => 48,
                    ],
                    $bea->id => [
                        $technique->id => 40,
                        $presentation->id => 42,
                    ],
                ],
            ])
            ->assertRedirect(route('events.categories.competitions.scores.edit', [
                $event,
                $category,
                $competition,
            ]));

        $this->assertDatabaseCount('competition_results', 2);
        $this->assertDatabaseCount('criterion_scores', 4);

        $standings = app(LeaderboardCalculator::class)
            ->competitionStandings($competition->fresh());

        $this->assertTrue($standings->first()['participant']->is($alex));
        $this->assertSame(93.0, $standings->first()['raw_score']);
        $this->assertSame(1, $standings->first()['rank']);
        $this->assertSame(100.0, $standings->first()['leaderboard_points']);
    }

    public function test_event_tie_rule_changes_following_rank_and_podium_points(): void
    {
        $admin = User::factory()->create(['role' => AccountRole::Admin]);
        $event = Event::factory()->for($admin, 'creator')->create();
        $alex = $event->participants()->create(['name' => 'Alex Santos']);
        $bea = $event->participants()->create(['name' => 'Bea Cruz']);
        $carlo = $event->participants()->create(['name' => 'Carlo Reyes']);
        $category = $event->categories()->create(['name' => 'Senior']);
        $competition = $category->competitions()->create(['name' => 'Solo']);
        $criterion = $competition->criteria()->create(['name' => 'Technique', 'max_score' => 100]);
        $competition->rankScores()->createMany([
            ['rank' => 1, 'points' => 100],
            ['rank' => 2, 'points' => 75],
            ['rank' => 3, 'points' => 50],
        ]);

        $this->actingAs($admin)
            ->patch(route('events.categories.competitions.scores.update', [
                $event,
                $category,
                $competition,
            ]), [
                'scores' => [
                    $alex->id => [$criterion->id => 90],
                    $bea->id => [$criterion->id => 90],
                    $carlo->id => [$criterion->id => 80],
                ],
            ])
            ->assertRedirect();

        $standings = app(LeaderboardCalculator::class)->competitionStandings($competition->fresh());
        $this->assertSame([1, 1, 3], $standings->pluck('rank')->all());
        $this->assertSame([100.0, 100.0, 50.0], $standings->pluck('leaderboard_points')->all());

        $this->actingAs($admin)
            ->patch(route('events.tie-ranking.update', $event), [
                'tie_ranking_method' => TieRankingMethod::ConsecutivePositions->value,
            ])
            ->assertRedirect(route('events.show', $event));

        $standings = app(LeaderboardCalculator::class)->competitionStandings($competition->fresh());
        $this->assertSame([1, 1, 2], $standings->pluck('rank')->all());
        $this->assertSame([100.0, 100.0, 75.0], $standings->pluck('leaderboard_points')->all());

        $overall = app(LeaderboardCalculator::class)->overallStandings($event->fresh());
        $this->assertSame(75.0, $overall->firstWhere('participant.id', $carlo->id)['leaderboard_points']);

        $scoreSheet = $this->actingAs($admin)->get(route('events.categories.competitions.scores.edit', [
            $event,
            $category,
            $competition,
        ]));
        $scoreSheet->assertOk()
            ->assertSee('Continue with the next position')
            ->assertSee('aria-label="Sort by rank ascending"', false)
            ->assertSee('data-rank="2"', false);
        $this->assertMatchesRegularExpression(
            '/<table[^>]*>\s*<thead[^>]*>\s*<tr>\s*<th[^>]*>\s*<button[^>]*>.*?<span>Rank<\/span>.*?<\/button>\s*<\/th>\s*<th[^>]*>Participant<\/th>/s',
            $scoreSheet->getContent(),
        );
    }

    public function test_unscored_contest_score_sheet_starts_with_participant_column(): void
    {
        $admin = User::factory()->create(['role' => AccountRole::Admin]);
        $event = Event::factory()->for($admin, 'creator')->create();
        $event->participants()->create(['name' => 'Alex Santos']);
        $category = $event->categories()->create(['name' => 'Sports']);
        $competition = $category->competitions()->create([
            'name' => 'Basketball',
            'scoring_method' => CompetitionScoringMethod::Wins,
        ]);

        $scoreSheet = $this->actingAs($admin)->get(route('events.categories.competitions.scores.edit', [
            $event,
            $category,
            $competition,
        ]));

        $scoreSheet->assertOk()->assertDontSee('id="rank-sort-button"', false);
        $this->assertMatchesRegularExpression(
            '/<table[^>]*>\s*<thead[^>]*>\s*<tr>\s*<th[^>]*>Participant<\/th>/s',
            $scoreSheet->getContent(),
        );
    }

    public function test_consecutive_tie_positions_apply_to_wins_based_contests(): void
    {
        $admin = User::factory()->create(['role' => AccountRole::Admin]);
        $event = Event::factory()->for($admin, 'creator')->create([
            'tie_ranking_method' => TieRankingMethod::ConsecutivePositions,
        ]);
        $alex = $event->participants()->create(['name' => 'Alex Santos']);
        $bea = $event->participants()->create(['name' => 'Bea Cruz']);
        $carlo = $event->participants()->create(['name' => 'Carlo Reyes']);
        $category = $event->categories()->create(['name' => 'Sports']);
        $competition = $category->competitions()->create([
            'name' => 'Basketball',
            'scoring_method' => CompetitionScoringMethod::Wins,
        ]);
        $competition->rankScores()->createMany([
            ['rank' => 1, 'points' => 100],
            ['rank' => 2, 'points' => 50],
        ]);

        $this->actingAs($admin)
            ->patch(route('events.categories.competitions.scores.update', [
                $event,
                $category,
                $competition,
            ]), [
                'wins' => [
                    $alex->id => 4,
                    $bea->id => 4,
                    $carlo->id => 2,
                ],
            ])
            ->assertRedirect();

        $standings = app(LeaderboardCalculator::class)->competitionStandings($competition->fresh());

        $this->assertSame([1, 1, 2], $standings->pluck('rank')->all());
        $this->assertSame([100.0, 100.0, 50.0], $standings->pluck('leaderboard_points')->all());
    }

    public function test_overall_winner_is_calculated_from_contest_wins(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'creator')->create();
        $alex = $event->participants()->create(['name' => 'Alex Santos']);
        $bea = $event->participants()->create(['name' => 'Bea Cruz']);
        $category = $event->categories()->create(['name' => 'Sports']);
        $basketball = $category->competitions()->create([
            'name' => 'Basketball',
            'scoring_method' => CompetitionScoringMethod::Wins,
        ]);
        $volleyball = $category->competitions()->create([
            'name' => 'Volleyball',
            'scoring_method' => CompetitionScoringMethod::Wins,
        ]);

        foreach ([$basketball, $volleyball] as $competition) {
            $this->actingAs($user)
                ->patch(route('events.categories.competitions.scores.update', [
                    $event,
                    $category,
                    $competition,
                ]), [
                    'wins' => [
                        $alex->id => 4,
                        $bea->id => 2,
                    ],
                ])
                ->assertRedirect();
        }

        $overall = app(LeaderboardCalculator::class)
            ->overallStandings($event->fresh());

        $this->assertTrue($overall->first()['participant']->is($alex));
        $this->assertSame(2, $overall->first()['contest_wins']);
        $this->assertSame(8, $overall->first()['recorded_wins']);

        $this->actingAs($user)
            ->get(route('events.leaderboard', $event))
            ->assertOk()
            ->assertSee('Current overall leader')
            ->assertSee('Alex Santos')
            ->assertSee('2 contest wins');
    }

    public function test_participant_without_an_entry_is_excluded_from_rankings_and_non_podium_points(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'creator')->create();
        $winner = $event->participants()->create(['name' => 'Alex Santos']);
        $nonPodiumParticipant = $event->participants()->create(['name' => 'Bea Cruz']);
        $participantWithoutEntry = $event->participants()->create(['name' => 'Carlo Reyes']);
        $category = $event->categories()->create(['name' => 'Senior']);
        $competition = $category->competitions()->create([
            'name' => 'Solo',
            'non_podium_points' => 10,
        ]);
        $criterion = $competition->criteria()->create([
            'name' => 'Technique',
            'max_score' => 100,
        ]);
        $competition->rankScores()->create([
            'rank' => 1,
            'points' => 100,
        ]);

        $this->actingAs($user)
            ->patch(route('events.categories.competitions.scores.update', [
                $event,
                $category,
                $competition,
            ]), [
                'entries' => [
                    $winner->id => 1,
                    $nonPodiumParticipant->id => 1,
                    $participantWithoutEntry->id => 0,
                ],
                'scores' => [
                    $winner->id => [$criterion->id => 90],
                    $nonPodiumParticipant->id => [$criterion->id => 70],
                    $participantWithoutEntry->id => [$criterion->id => 80],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('competition_results', [
            'competition_id' => $competition->id,
            'participant_id' => $participantWithoutEntry->id,
            'has_entry' => false,
        ]);

        $standings = app(LeaderboardCalculator::class)
            ->competitionStandings($competition->fresh());

        $this->assertCount(2, $standings);
        $this->assertTrue($standings->first()['participant']->is($winner));
        $this->assertTrue($standings->last()['participant']->is($nonPodiumParticipant));
        $this->assertSame(10.0, $standings->last()['leaderboard_points']);
        $this->assertFalse($standings->contains(
            fn (array $row): bool => $row['participant']->is($participantWithoutEntry),
        ));

        $overall = app(LeaderboardCalculator::class)
            ->overallStandings($event->fresh());

        $this->assertFalse($overall->contains(
            fn (array $row): bool => $row['participant']->is($participantWithoutEntry),
        ));

        $this->actingAs($user)
            ->get(route('events.categories.competitions.scores.edit', [
                $event,
                $category,
                $competition,
            ]))
            ->assertOk()
            ->assertSee('name="entries['.$participantWithoutEntry->id.']"', false)
            ->assertSee('No entry')
            ->assertSee('Excluded');
    }

    public function test_no_entry_status_is_saved_without_a_score_and_cannot_be_finalized(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'creator')->create();
        $participant = $event->participants()->create(['name' => 'Alex Santos']);
        $category = $event->categories()->create(['name' => 'Sports']);
        $competition = $category->competitions()->create([
            'name' => 'Basketball',
            'scoring_method' => CompetitionScoringMethod::Wins,
        ]);

        $this->actingAs($user)
            ->patch(route('events.categories.competitions.scores.update', [
                $event,
                $category,
                $competition,
            ]), [
                'entries' => [$participant->id => 0],
                'wins' => [$participant->id => null],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('competition_results', [
            'competition_id' => $competition->id,
            'participant_id' => $participant->id,
            'has_entry' => false,
            'wins' => null,
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

    public function test_criterion_score_cannot_exceed_its_configured_maximum(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'creator')->create();
        $participant = $event->participants()->create(['name' => 'Alex Santos']);
        $category = $event->categories()->create(['name' => 'Senior']);
        $competition = $category->competitions()->create(['name' => 'Solo']);
        $criterion = $competition->criteria()->create([
            'name' => 'Technique',
            'max_score' => 50,
        ]);

        $this->actingAs($user)
            ->patch(route('events.categories.competitions.scores.update', [
                $event,
                $category,
                $competition,
            ]), [
                'scores' => [
                    $participant->id => [$criterion->id => 51],
                ],
            ])
            ->assertSessionHasErrors("scores.{$participant->id}.{$criterion->id}");

        $this->assertDatabaseCount('competition_results', 0);
    }

    public function test_deduction_requires_a_saved_score_and_reduces_the_adjusted_total(): void
    {
        $auditor = User::factory()->create(['role' => AccountRole::Auditor]);
        $event = Event::factory()->for($auditor, 'creator')->create();
        $participant = $event->participants()->create(['name' => 'Alex Santos']);
        $category = $event->categories()->create(['name' => 'Senior']);
        $competition = $category->competitions()->create(['name' => 'Solo']);
        $criterion = $competition->criteria()->create([
            'name' => 'Technique',
            'max_score' => 100,
        ]);

        $deductionRoute = route('events.categories.competitions.deductions.update', [
            $event,
            $category,
            $competition,
        ]);

        $this->actingAs($auditor)
            ->get(route('events.categories.competitions.scores.edit', [
                $event,
                $category,
                $competition,
            ]))
            ->assertOk()
            ->assertSee('Save score first')
            ->assertDontSee('name="deductions['.$participant->id.']"', false);

        $this->actingAs($auditor)
            ->patch($deductionRoute, [
                'deductions' => [$participant->id => 5],
            ])
            ->assertSessionHasErrors('deductions');

        $this->actingAs($auditor)
            ->patch(route('events.categories.competitions.scores.update', [
                $event,
                $category,
                $competition,
            ]), [
                'scores' => [
                    $participant->id => [$criterion->id => 80],
                ],
            ])
            ->assertRedirect();

        $this->actingAs($auditor)
            ->get(route('events.categories.competitions.scores.edit', [
                $event,
                $category,
                $competition,
            ]))
            ->assertOk()
            ->assertSee('name="deductions['.$participant->id.']"', false)
            ->assertSee('Save deductions');

        $this->actingAs($auditor)
            ->patch($deductionRoute, [
                'deductions' => [$participant->id => 12.5],
            ])
            ->assertRedirect();

        $result = $competition->results()->sole();
        $this->assertSame('12.50', $result->deduction);

        $standing = app(LeaderboardCalculator::class)
            ->competitionStandings($competition->fresh())
            ->sole();

        $this->assertSame(80.0, $standing['gross_score']);
        $this->assertSame(12.5, $standing['deduction']);
        $this->assertSame(67.5, $standing['raw_score']);
    }

    public function test_admin_can_override_scores_and_deductions_after_finalization(): void
    {
        $admin = User::factory()->create(['role' => AccountRole::Admin]);
        $event = Event::factory()->for($admin, 'creator')->create();
        $participant = $event->participants()->create(['name' => 'Alex Santos']);
        $category = $event->categories()->create(['name' => 'Sports']);
        $competition = $category->competitions()->create([
            'name' => 'Basketball',
            'scoring_method' => CompetitionScoringMethod::Wins,
            'scores_finalized_at' => now(),
        ]);
        $competition->results()->create([
            'participant_id' => $participant->id,
            'wins' => 4,
        ]);

        $this->actingAs($admin)
            ->patch(route('events.categories.competitions.scores.update', [
                $event,
                $category,
                $competition,
            ]), [
                'wins' => [$participant->id => 7],
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->patch(route('events.categories.competitions.deductions.update', [
                $event,
                $category,
                $competition,
            ]), [
                'deductions' => [$participant->id => 2],
            ])
            ->assertRedirect();

        $result = $competition->results()->sole();
        $this->assertSame(7, $result->wins);
        $this->assertSame('2.00', $result->deduction);
        $this->assertNotNull($competition->refresh()->scores_finalized_at);

        $this->actingAs($admin)
            ->get(route('events.categories.competitions.scores.edit', [
                $event,
                $category,
                $competition,
            ]))
            ->assertOk()
            ->assertSee('Admin override')
            ->assertSee('Save deductions')
            ->assertSee('Save all scores');
    }
}
