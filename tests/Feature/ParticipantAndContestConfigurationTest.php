<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CompetitionScoringMethod;
use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ParticipantAndContestConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_can_be_created_with_a_profile_picture(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'creator')->create();

        $this->actingAs($user)
            ->post(route('events.participants.store', $event), [
                'participant_name' => 'Alex Santos',
                'participant_reference' => 'P-100',
                'participant_image' => UploadedFile::fake()->image('alex.jpg'),
            ])
            ->assertRedirect(route('events.show', $event));

        $participant = Participant::query()->sole();

        $this->assertSame('Alex Santos', $participant->name);
        $this->assertNotNull($participant->profile_picture_path);
        Storage::disk('public')->assertExists($participant->profile_picture_path);
    }

    public function test_participant_can_be_edited_and_profile_picture_replaced(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'creator')->create();
        $oldPath = UploadedFile::fake()->image('old.jpg')->store(
            "participants/{$event->id}",
            'public',
        );
        $participant = $event->participants()->create([
            'name' => 'Old name',
            'reference_no' => 'OLD-1',
            'profile_picture_path' => $oldPath,
        ]);

        $this->actingAs($user)
            ->patch(route('events.participants.update', [$event, $participant]), [
                'participant_name' => 'Updated name',
                'participant_reference' => 'NEW-1',
                'participant_image' => UploadedFile::fake()->image('new.jpg'),
            ])
            ->assertRedirect(route('events.show', $event));

        $participant->refresh();

        $this->assertSame('Updated name', $participant->name);
        $this->assertSame('NEW-1', $participant->reference_no);
        $this->assertNotSame($oldPath, $participant->profile_picture_path);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($participant->profile_picture_path);
    }

    public function test_deleting_participant_also_deletes_profile_picture(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'creator')->create();
        $path = UploadedFile::fake()->image('participant.jpg')->store(
            "participants/{$event->id}",
            'public',
        );
        $participant = $event->participants()->create([
            'name' => 'Delete Me',
            'profile_picture_path' => $path,
        ]);

        $this->actingAs($user)
            ->delete(route('events.participants.destroy', [$event, $participant]))
            ->assertRedirect(route('events.show', $event));

        $this->assertModelMissing($participant);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_admin_can_configure_criteria_and_rank_scoring_for_a_contest(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'creator')->create();
        $category = $event->categories()->create(['name' => 'Senior']);
        $competition = $category->competitions()->create(['name' => 'Solo']);

        $this->actingAs($user)
            ->post(route('events.categories.competitions.criteria.store', [
                $event,
                $category,
                $competition,
            ]), [
                'criterion_name' => 'Technique',
                'max_score' => 40,
            ])
            ->assertRedirect(route('events.categories.competitions.show', [
                $event,
                $category,
                $competition,
            ]));

        $this->actingAs($user)
            ->post(route('events.categories.competitions.rank-scores.store', [
                $event,
                $category,
                $competition,
            ]), [
                'rank' => 1,
                'points' => 100,
            ])
            ->assertRedirect(route('events.categories.competitions.show', [
                $event,
                $category,
                $competition,
            ]));

        $this->assertDatabaseHas('criteria', [
            'competition_id' => $competition->id,
            'name' => 'Technique',
            'max_score' => 40,
        ]);
        $this->assertDatabaseHas('rank_scores', [
            'competition_id' => $competition->id,
            'rank' => 1,
            'points' => 100,
        ]);

        $this->actingAs($user)
            ->get(route('events.categories.competitions.show', [
                $event,
                $category,
                $competition,
            ]))
            ->assertOk()
            ->assertSee('Technique')
            ->assertSee('Maximum score: 40.00')
            ->assertSee('#1');
    }

    public function test_saving_an_existing_rank_updates_its_points(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'creator')->create();
        $category = $event->categories()->create(['name' => 'Junior']);
        $competition = $category->competitions()->create(['name' => 'Group']);
        $competition->rankScores()->create(['rank' => 1, 'points' => 50]);

        $this->actingAs($user)
            ->post(route('events.categories.competitions.rank-scores.store', [
                $event,
                $category,
                $competition,
            ]), [
                'rank' => 1,
                'points' => 75,
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('rank_scores', 1);
        $this->assertDatabaseHas('rank_scores', [
            'competition_id' => $competition->id,
            'rank' => 1,
            'points' => 75,
        ]);
    }

    public function test_admin_can_set_points_for_non_podium_participants(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'creator')->create();
        $category = $event->categories()->create(['name' => 'Open']);
        $competition = $category->competitions()->create(['name' => 'Final']);

        $this->actingAs($user)
            ->patch(route('events.categories.competitions.participation-points.update', [
                $event,
                $category,
                $competition,
            ]), [
                'non_podium_points' => 10,
            ])
            ->assertRedirect(route('events.categories.competitions.show', [
                $event,
                $category,
                $competition,
            ]));

        $this->assertSame('10.00', $competition->refresh()->non_podium_points);
    }

    public function test_admin_can_switch_a_contest_to_wins_based_scoring(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'creator')->create();
        $category = $event->categories()->create(['name' => 'Team Sports']);
        $competition = $category->competitions()->create(['name' => 'Basketball', 'judge_count' => 3]);
        $competition->criteria()->create(['name' => 'Technique', 'max_score' => 100]);

        $this->actingAs($user)
            ->patch(route('events.categories.competitions.scoring-method.update', [
                $event,
                $category,
                $competition,
            ]), [
                'scoring_method' => CompetitionScoringMethod::Wins->value,
            ])
            ->assertRedirect(route('events.categories.competitions.show', [
                $event,
                $category,
                $competition,
            ]));

        $competition->refresh();

        $this->assertSame(CompetitionScoringMethod::Wins, $competition->scoring_method);
        $this->assertDatabaseHas('criteria', [
            'competition_id' => $competition->id,
            'name' => 'Technique',
        ]);

        $this->actingAs($user)
            ->get(route('events.categories.competitions.show', [
                $event,
                $category,
                $competition,
            ]))
            ->assertOk()
            ->assertSee('Wins-based scoring active')
            ->assertDontSee('Leaderboard game setup')
            ->assertDontSee('Judge slots')
            ->assertDontSee('Define the items judges will score.');

        $this->actingAs($user)
            ->patch(route('events.categories.competitions.scoring-method.update', [$event, $category, $competition]), [
                'scoring_method' => CompetitionScoringMethod::Criteria->value,
            ])
            ->assertRedirect();

        $this->assertSame(3, $competition->refresh()->judge_count);
        $this->actingAs($user)
            ->get(route('events.categories.competitions.show', [$event, $category, $competition]))
            ->assertSee('Leaderboard game setup')
            ->assertSee('Judge slots');
    }

    public function test_criteria_cannot_be_added_to_a_wins_based_contest(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'creator')->create();
        $category = $event->categories()->create(['name' => 'Sports']);
        $competition = $category->competitions()->create([
            'name' => 'Volleyball',
            'scoring_method' => CompetitionScoringMethod::Wins,
        ]);

        $this->actingAs($user)
            ->post(route('events.categories.competitions.criteria.store', [
                $event,
                $category,
                $competition,
            ]), [
                'criterion_name' => 'Technique',
                'max_score' => 100,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('criteria', 0);
    }
}
