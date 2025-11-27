<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Complaint;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ComplaintStatusHistory>
 */
class ComplaintStatusHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
   public function definition(): array
{
    // Get a random employee (status = 1 or 2)
    $employee = User::whereIn('status', [1, 2])->inRandomOrder()->first();

    return [
        'complaint_id' => Complaint::inRandomOrder()->first()->complaint_id,

        // إذا لم يوجد موظف → null
        'handled_by' => $employee?->id,

        'status' => fake()->randomElement(['new', 'processing', 'resolved', 'rejected']),
        'note' => fake()->sentence(),
        'changed_at' => now(),
    ];
}

}
