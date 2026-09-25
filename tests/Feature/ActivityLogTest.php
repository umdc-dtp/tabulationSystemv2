<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AccountRole;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_views_are_not_logged_but_data_changes_are_recorded(): void
    {
        $user = User::factory()->create(['role' => AccountRole::Admin]);
        $event = Event::factory()->for($user, 'creator')->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();

        $this->assertDatabaseCount('activity_logs', 0);

        $this->actingAs($user)
            ->patch(route('events.leaderboard-freeze', $event))
            ->assertRedirect(route('events.show', $event));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'actor_name' => $user->name,
            'actor_username' => $user->username,
            'interaction_type' => 'update',
            'action' => 'events.leaderboard-freeze',
            'method' => 'PATCH',
            'status_code' => 302,
        ]);

        $log = ActivityLog::query()
            ->where('action', 'events.leaderboard-freeze')
            ->sole();

        $this->assertSame([
            [
                'operation' => 'updated',
                'type' => 'Event',
                'id' => (string) $event->id,
                'label' => $event->name,
            ],
        ], $log->affectedRecords());
    }

    public function test_a_newly_created_record_is_stored_in_the_log(): void
    {
        $user = User::factory()->create(['role' => AccountRole::Admin]);

        $this->actingAs($user)
            ->post(route('events.store'), [
                'event_name' => 'Regional Games',
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-03',
                'scoring_system' => 'points',
            ])
            ->assertRedirect();

        $event = Event::query()->where('name', 'Regional Games')->sole();
        $log = ActivityLog::query()->where('action', 'events.store')->sole();

        $this->assertSame([
            [
                'operation' => 'created',
                'type' => 'Event',
                'id' => (string) $event->id,
                'label' => 'Regional Games',
            ],
        ], $log->affectedRecords());
    }

    public function test_sign_in_is_logged_without_storing_the_password(): void
    {
        $user = User::factory()->create([
            'username' => 'audit.user',
            'password' => 'secret-password',
        ]);

        $this->post(route('login.store'), [
            'username' => 'audit.user',
            'password' => 'secret-password',
        ])->assertRedirect(route('dashboard'));

        $log = ActivityLog::query()->where('action', 'login.store')->sole();

        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('Signed in successfully', $log->description);
        $this->assertStringNotContainsString('secret-password', $log->toJson());
    }

    public function test_logs_can_be_searched_and_sorted(): void
    {
        $user = User::factory()->create(['role' => AccountRole::Admin]);

        ActivityLog::query()->create([
            'user_id' => $user->id,
            'actor_name' => 'Alpha Auditor',
            'actor_username' => 'alpha',
            'interaction_type' => 'update',
            'action' => 'users.update',
            'description' => 'Updated a user account',
            'method' => 'PATCH',
            'route_name' => 'users.update',
            'path' => '/users/2',
            'status_code' => 302,
            'ip_address' => '127.0.0.1',
            'duration_ms' => 8,
        ]);
        ActivityLog::query()->create([
            'actor_name' => 'Guest',
            'interaction_type' => 'view',
            'action' => 'login',
            'description' => 'Viewed the sign-in page',
            'method' => 'GET',
            'route_name' => 'login',
            'path' => '/login',
            'status_code' => 200,
            'ip_address' => '127.0.0.1',
            'duration_ms' => 3,
        ]);

        $this->actingAs($user)
            ->get(route('logs.index', [
                'q' => 'Alpha',
                'sort' => 'actor_name',
                'direction' => 'asc',
            ]))
            ->assertOk()
            ->assertSee('Alpha Auditor')
            ->assertSee('Updated a user account')
            ->assertDontSee('Viewed the sign-in page');

        $this->actingAs($user)
            ->get(route('logs.index'))
            ->assertOk()
            ->assertDontSee('Viewed the sign-in page');
    }

    public function test_filtered_logs_can_be_downloaded_as_csv(): void
    {
        $user = User::factory()->create(['role' => AccountRole::Admin]);

        ActivityLog::query()->create([
            'user_id' => $user->id,
            'actor_name' => 'CSV Auditor',
            'actor_username' => 'csv.auditor',
            'interaction_type' => 'update',
            'action' => 'users.update',
            'description' => 'Updated a user account',
            'method' => 'PATCH',
            'route_name' => 'users.update',
            'path' => '/users/2',
            'status_code' => 200,
            'ip_address' => '127.0.0.1',
            'duration_ms' => 4,
            'context' => [
                'records' => [[
                    'operation' => 'updated',
                    'type' => 'User',
                    'id' => '2',
                    'label' => 'CSV Target',
                ]],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('logs.download', [
            'q' => 'CSV Auditor',
        ]));

        $response->assertOk()->assertDownload();
        $content = $response->streamedContent();

        $this->assertStringContainsString('"Date and time",Name,Username', $content);
        $this->assertStringContainsString('CSV Auditor', $content);
        $this->assertStringContainsString('Updated User #2 - CSV Target', $content);
    }
}
