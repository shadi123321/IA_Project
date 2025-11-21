<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\GovernmentEntity;   // ← هذا الصحيح 100%

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
  public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'status' => 0, // citizen
            'government_entity_id' => null,
        ];
    }

    // موظف
    public function employee()
    {
        return $this->state(function () {
            return [
                'status' => 1,
                'government_entity_id' => GovernmentEntity::inRandomOrder()->first()->entity_id,
            ];
        });
    }

    // أدمن
    public function admin()
    {
        return $this->state(function () {
            return ['status' => 2];
        });
    }
}
