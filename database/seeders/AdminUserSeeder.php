<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'cshengli323@gmail.com'],
            [
                'name' => env('ADMIN_NAME', 'Admin'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'CHANGE_ME_strong!')),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
