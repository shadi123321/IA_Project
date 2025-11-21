<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Complaint;
use App\Models\ComplaintAttachment;
use App\Models\ComplaintStatusHistory;
use App\Models\GovernmentEntity;



class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(GovernmentEntitySeeder::class);
         $this->call(RolesAndPermissionsSeeder::class);

        // مواطنين
        User::factory(30)->create();

        // موظفين
        User::factory(10)->employee()->create();

        // أدمن واحد
        User::factory()->admin()->create([
            'email' => 'admin@test.com',
        ]);

        // شكاوى
        Complaint::factory(50)->create();

        // مرفقات
        ComplaintAttachment::factory(100)->create();

        // سجل حالات
        ComplaintStatusHistory::factory(100)->create();
    }

}
