<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;


class AdminSeeder extends Seeder
{
    public function run(): void
    {
             //  $admin = User::where('email', 'admin@test.com')->first();

        // لا تنشئ مستخدم جديد إذا كان موجودًا
        if (!User::where('email', 'hamzehshadi0@gmail.com')->exists()) {
            $admin = User::create([
                'name'              => 'Super Admin',
                'email'             => 'hamzehshadi0@gmail.com',
                'password'          => Hash::make('12345'),
             ]);
            $admin->assignRole('admin'); // Spatie
        }
    }
}
