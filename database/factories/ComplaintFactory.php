<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\GovernmentEntity;
use App\Models\User;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Complaint>
 */
class ComplaintFactory extends Factory
{
   public function definition(): array
    {
        return [
            'reference_number' => strtoupper(fake()->unique()->bothify('CMP-####')),
            'user_id' => User::where('status', 0)->inRandomOrder()->first()->id,
            'government_entity_id' => GovernmentEntity::inRandomOrder()->first()->entity_id,
            'type' => fake()->randomElement(['Road Damage', 'Water Issue', 'Electricity Problem', 'Health Service']),
            'location' => fake()->address(),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(['new', 'processing', 'resolved', 'rejected']),
        ];
    }
}
