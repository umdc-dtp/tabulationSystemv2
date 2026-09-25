<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AccountRole;
use App\Enums\CompetitionScoringMethod;
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
