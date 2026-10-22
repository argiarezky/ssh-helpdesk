<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Ticket> */
class TicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ticket_number' => 'TKT-'.fake()->unique()->numerify('###'),
            'reporter_name' => fake()->name(),
            'location' => fake()->city(),
            'coordinator_name' => fake()->name(),
            'description' => fake()->sentence(),
            'engineer_id' => User::factory(),
            'status' => fake()->randomElement(['open', 'pending', 'in_progress', 'resolved', 'closed']),
            'priority' => fake()->randomElement(['low', 'normal', 'high', 'urgent']),
            'due_at' => null,
            'due_reminded_at' => null,
            'handling_notes' => fake()->optional()->sentence(),
        ];
    }
}
