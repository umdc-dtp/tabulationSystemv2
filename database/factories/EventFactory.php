<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ScoringSystem;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
final class EventFactory extends Factory
{
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 month', '+2 months');

        return [
            'creator_id' => User::factory(),
            'name' => fake()->words(3, true),
            'start_date' => $startDate,
            'end_date' => fake()->dateTimeBetween($startDate, '+3 months'),
            'scoring_system' => ScoringSystem::Points,
            'other_scoring_system' => null,
            'leaderboard_frozen' => false,
        ];
    }
}
