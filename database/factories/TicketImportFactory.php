<?php

namespace Database\Factories;

use App\Models\TicketImport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketImport>
 */
class TicketImportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $totalRows = $this->faker->numberBetween(1, 100);
        $createdCount = $this->faker->numberBetween(0, $totalRows);

        return [
            'imported_by_id' => User::factory()->engineer(),
            'imported_by_name' => $this->faker->name(),
            'original_filename' => $this->faker->word().'.csv',
            'total_rows' => $totalRows,
            'created_count' => $createdCount,
            'skipped_count' => $totalRows - $createdCount,
        ];
    }
}
