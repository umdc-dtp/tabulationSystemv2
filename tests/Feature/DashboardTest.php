<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AccountRole;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_progress_uses_published_competitor_results(): void
    {
        $this->withoutVite();
        $admin = User::factory()->create(['role' => AccountRole::Admin]);
        $partiallyFinalized = Event::factory()->for($admin, 'creator')->create([
            'name' => 'Regional Games',
            'start_date' => today()->subDay(),
            'end_date' => today()->addDay(),
        ]);
        $category = $partiallyFinalized->categories()->create(['name' => 'Senior']);
        $category->competitions()->create(['name' => 'Contest One', 'finalized_at' => now(), 'results_open' => false]);
        $category->competitions()->create(['name' => 'Contest Two']);

        $completed = Event::factory()->for($admin, 'creator')->create(['name' => 'Completed Games']);
        $completedCategory = $completed->categories()->create(['name' => 'Open']);
        $completedCategory->competitions()->create(['name' => 'Final Contest', 'finalized_at' => now(), 'results_open' => false]);

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Regional Games')
            ->assertSee('1 of 2 contests finalized')
            ->assertSee('Completed Games')
            ->assertSee('1 of 1 contest finalized')
            ->assertSee('50 percent of events finalized', false);
    }
}
