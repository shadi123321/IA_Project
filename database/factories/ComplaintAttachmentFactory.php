<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Complaint;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ComplaintAttachment>
 */
class ComplaintAttachmentFactory extends Factory
{
  public function definition(): array
    {
        return [
            'complaint_id' => Complaint::inRandomOrder()->first()->complaint_id,
            'file_path' => 'uploads/' . fake()->image('public/uploads', 640, 480, null, false),
            'type' => fake()->randomElement(['image', 'document']),
        ];
    }
}
