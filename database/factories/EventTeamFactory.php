<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventTeam;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventTeam>
 */
class EventTeamFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'department_id' => fn (array $attributes): int => Event::query()
                ->findOrFail($attributes['event_id'])
                ->departments()
                ->create(['name' => fake()->unique()->word()])->getKey(),
            'name' => fake()->company(),
            'member_names' => [fake()->name(), fake()->name()],
        ];
    }
}
