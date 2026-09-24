<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AccountRole;
use App\Enums\ScoringSystem;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class EventManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_events(): void
    {
        $this->get('/events')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_events_page(): void
    {
        $this->actingAs(User::factory()->create(['role' => AccountRole::Admin]))
            ->get('/events')
            ->assertOk()
            ->assertSee('Add event')
            ->assertSee('Scoring system');
    }

    public function test_authenticated_user_can_create_an_event(): void
    {
        $user = User::factory()->create(['role' => AccountRole::Admin]);

        $response = $this->actingAs($user)->post('/events', [
            'event_name' => 'Regional Championship',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-03',
            'scoring_system' => ScoringSystem::Points->value,
        ]);

        $event = Event::query()->sole();

        $response->assertRedirect(route('events.show', $event));

        $this->assertDatabaseHas('events', [
            'creator_id' => $user->id,
            'name' => 'Regional Championship',
            'scoring_system' => ScoringSystem::Points->value,
            'leaderboard_frozen' => false,
        ]);
    }

    public function test_other_scoring_system_requires_a_description(): void
    {
        $this->actingAs(User::factory()->create(['role' => AccountRole::Admin]))
            ->post('/events', [
                'event_name' => 'Special Event',
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-03',
                'scoring_system' => ScoringSystem::Other->value,
            ])
            ->assertSessionHasErrors('other_scoring_system');
    }

    public function test_end_date_cannot_be_before_start_date(): void
    {
        $this->actingAs(User::factory()->create(['role' => AccountRole::Admin]))
            ->post('/events', [
                'event_name' => 'Invalid Event',
                'start_date' => '2026-10-03',
                'end_date' => '2026-10-01',
                'scoring_system' => ScoringSystem::Ranking->value,
            ])
            ->assertSessionHasErrors('end_date');
    }

    public function test_event_configuration_accepts_participants_categories_and_competitions(): void
    {
        $user = User::factory()->create(['role' => AccountRole::Admin]);
        $event = Event::factory()->for($user, 'creator')->create();

        $this->actingAs($user)
            ->post(route('events.participants.store', $event), [
                'participant_name' => 'Jordan Reyes',
                'participant_reference' => 'P-001',
            ])
            ->assertRedirect(route('events.show', $event));

        $this->actingAs($user)
            ->post(route('events.categories.store', $event), [
                'category_name' => 'Senior Division',
            ])
            ->assertRedirect(route('events.show', $event));

        $category = Category::query()->sole();

        $this->actingAs($user)
            ->post(route('events.categories.competitions.store', [$event, $category]), [
                'competition_name' => 'Solo Performance',
            ])
            ->assertRedirect(route('events.show', $event));

        $this->assertDatabaseHas('participants', [
            'event_id' => $event->id,
            'name' => 'Jordan Reyes',
            'reference_no' => 'P-001',
        ]);
        $this->assertDatabaseHas('categories', [
            'event_id' => $event->id,
            'name' => 'Senior Division',
        ]);
        $this->assertDatabaseHas('competitions', [
            'category_id' => $category->id,
            'name' => 'Solo Performance',
        ]);

        $this->actingAs($user)
            ->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Jordan Reyes')
            ->assertSee('Senior Division')
            ->assertSee('Solo Performance')
            ->assertSee('Current leaderboard')
            ->assertSee('Enter scores');
    }

    public function test_category_from_another_event_cannot_receive_a_competition(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'creator')->create();
        $otherEvent = Event::factory()->for($user, 'creator')->create();
        $otherCategory = $otherEvent->categories()->create(['name' => 'Other category']);

        $this->actingAs($user)
            ->post(route('events.categories.competitions.store', [$event, $otherCategory]), [
                'competition_name' => 'Invalid competition',
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('competitions', [
            'name' => 'Invalid competition',
        ]);
    }

    public function test_leaderboard_can_be_frozen_and_unfrozen(): void
    {
        $user = User::factory()->create(['role' => AccountRole::Admin]);
        $event = Event::factory()->for($user, 'creator')->create();

        $this->actingAs($user)
            ->patch(route('events.leaderboard-freeze', $event))
            ->assertRedirect(route('events.show', $event));

        $this->assertTrue($event->refresh()->leaderboard_frozen);

        $this->actingAs($user)
            ->patch(route('events.leaderboard-freeze', $event))
            ->assertRedirect(route('events.show', $event));

        $this->assertFalse($event->refresh()->leaderboard_frozen);
    }

    public function test_admin_can_delete_an_event_and_its_stored_participant_images(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => AccountRole::Admin]);
        $event = Event::factory()->for($admin, 'creator')->create();
        $profilePath = "participants/{$event->id}/profile.jpg";
        Storage::disk('public')->put($profilePath, 'profile-image');

        $participant = $event->participants()->create([
            'name' => 'Jordan Reyes',
            'profile_picture_path' => $profilePath,
        ]);
        $category = $event->categories()->create(['name' => 'Senior Division']);
        $competition = $category->competitions()->create(['name' => 'Solo Performance']);

        $this->actingAs($admin)
            ->delete(route('events.destroy', $event))
            ->assertRedirect(route('events.index'))
            ->assertSessionHas('status', 'Event deleted successfully.');

        $this->assertModelMissing($event);
        $this->assertModelMissing($participant);
        $this->assertModelMissing($category);
        $this->assertModelMissing($competition);
        Storage::disk('public')->assertMissing($profilePath);
    }

    public function test_auditor_cannot_delete_an_event(): void
    {
        $auditor = User::factory()->create(['role' => AccountRole::Auditor]);
        $event = Event::factory()->create();

        $this->actingAs($auditor)
            ->delete(route('events.destroy', $event))
            ->assertForbidden();

        $this->assertModelExists($event);
    }
}
