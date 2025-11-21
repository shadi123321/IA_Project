<?php

namespace Database\Seeders;
use App\Models\GovernmentEntity;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GovernmentEntitySeeder extends Seeder
{
    public function run(): void
    {
        $entities = [
            'Ministry of Health',
            'Ministry of Education',
            'Ministry of Transportation',
            'Ministry of Interior',
            'Ministry of Environment',
            'Electricity Authority',
            'Water Authority',
            'Municipality Department'
        ];

        foreach ($entities as $name) {
            GovernmentEntity::create([
                'name' => $name,
                'description' => $name . " related services",
                'location' => fake()->city(),
                'contact_email' => fake()->companyEmail(),
                'contact_phone' => fake()->phoneNumber(),
            ]);
        }
    }
}
