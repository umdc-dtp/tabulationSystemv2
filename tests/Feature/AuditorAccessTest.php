<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AccountRole;
use App\Enums\CompetitionScoringMethod;
use App\Enums\ScoringSystem;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AuditorAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_auditor_can_freeze_leaderboards_but_cannot_access_administrator_only_modules_or_event_creation(): void
    {
        $auditor = User::factory()->create(['role' => AccountRole::Auditor]);
        $event = Event::factory()->create();

        $this->actingAs($auditor)
            ->post(route('events.store'), [
                'event_name' => 'Unauthorized Event',
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-02',
                'scoring_system' => ScoringSystem::Points->value,
            ])
            ->assertForbidden();

        $this->actingAs($auditor)->delete(route('events.destroy', $event))->assertForbidden();
        $this->actingAs($auditor)
            ->patch(route('events.leaderboard-freeze', $event))
            ->assertRedirect();
        $this->actingAs($auditor)->get(route('users.index'))->assertForbidden();
        $this->actingAs($auditor)->get(route('logs.index'))->assertForbidden();

        $this->actingAs($auditor)
            ->get(route('events.index'))
            ->assertOk()
            ->assertDontSee('Add event')
            ->assertDontSee('>Users<', false)
            ->assertDontSee('>Logs<', false)
            ->assertDontSee('>Settings<', false);

        $this->actingAs($auditor)
            ->get(route('events.show', $event))
            ->assertOk()
            ->assertSee(route('events.leaderboard-freeze', $event), false);

        $this->assertDatabaseMissing('events', ['name' => 'Unauthorized Event']);
        $this->assertTrue($event->refresh()->leaderboard_frozen);
    }

    public function test_auditor_can_manage_event_structure_and_enter_scores(): void
    {
        $auditor = User::factory()->create(['role' => AccountRole::Auditor]);
        $event = Event::factory()->create();

        $this->actingAs($auditor)
            ->post(route('events.participants.store', $event), [
                'participant_name' => 'Alex Santos',
                'participant_reference' => 'P-101',
            ])
            ->assertRedirect(route('events.show', $event));

        $participant = $event->participants()->sole();

        $this->actingAs($auditor)
            ->post(route('events.categories.store', $event), [
                'category_name' => 'Team Sports',
            ])
            ->assertRedirect(route('events.show', $event));

        $category = Category::query()->sole();

        $this->actingAs($auditor)
            ->post(route('events.categories.competitions.store', [$event, $category]), [
                'competition_name' => 'Basketball',
            ])
            ->assertRedirect(route('events.show', $event));

        $competition = $category->competitions()->sole();

        $this->actingAs($auditor)
            ->patch(route('events.categories.competitions.scoring-method.update', [
                $event,
                $category,
                $competition,
            ]), [
                'scoring_method' => CompetitionScoringMethod::Wins->value,
            ])
            ->assertRedirect();

        $department = $event->departments()->create(['name' => 'Arts']);
        $participant->update(['department_id' => $department->id]);
        $this->actingAs($auditor)->post(route('events.categories.competitions.entries.store', [
            $event,
            $category,
            $competition,
        ]), ['competitor' => 'participant:'.$participant->id])->assertRedirect();
        $entry = $competition->entries()->sole();

        $this->actingAs($auditor)
            ->patch(route('events.categories.competitions.entries.result.update', [
                $event,
                $category,
                $competition,
                $entry,
            ]), [
                'win_total' => 5,
                'loss_total' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('competition_entries', [
            'competition_id' => $competition->id,
            'participant_id' => $participant->id,
            'win_total' => 5,
            'loss_total' => 1,
        ]);

        $this->actingAs($auditor)
            ->get(route('events.categories.competitions.show', [
                $event,
                $category,
                $competition,
            ]))
            ->assertOk();
    }

    public function test_auditor_dashboard_shows_active_event_stats_without_freeze_controls(): void
    {
        $auditor = User::factory()->create(['role' => AccountRole::Auditor]);
        $activeEvent = Event::factory()->create([
            'name' => 'Active Regional Games',
            'start_date' => today()->subDay(),
            'end_date' => today()->addDay(),
        ]);
        $activeEvent->participants()->create(['name' => 'Alex Santos']);
        $category = $activeEvent->categories()->create(['name' => 'Senior']);
        $category->competitions()->create(['name' => 'Solo']);

        Event::factory()->create([
            'name' => 'Future Games',
            'start_date' => today()->addMonth(),
            'end_date' => today()->addMonth()->addDay(),
        ]);

        $this->actingAs($auditor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Auditor workspace')
            ->assertSee('Active Regional Games')
            ->assertDontSee('Future Games')
            ->assertSee('0 of 1 contest finalized')
            ->assertDontSee('>Freeze<', false)
            ->assertDontSee('>Unfreeze<', false)
            ->assertDontSee('View activity logs');
    }
}
