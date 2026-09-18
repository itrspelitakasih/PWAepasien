<?php

namespace Database\Factories;

use App\Models\PatientAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatientAccount>
 */
class PatientAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'no_rkm_medis' => fake()->unique()->numerify('######'),
            'name' => fake()->name(),
            'wa_number' => null,
            'wa_verified_at' => null,
            'notify_appointment' => true,
            'notify_queue' => true,
            'notify_lab_result' => false,
        ];
    }

    public function waVerified(): static
    {
        return $this->state(fn (array $attributes) => [
            'wa_number' => '62812'.fake()->numerify('#######'),
            'wa_verified_at' => now(),
        ]);
    }
}
