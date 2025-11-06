<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $initialUsers = [
            [
                'name' => 'Admin',
                'email' => 'admin@mail.com',
                'password' => 'power@123',
            ],
        ];

        foreach ($initialUsers as $user) {
            $user = User::factory()->make($user);

            User::updateOrCreate([
                'email' => $user->email,
            ], [
                ...$user->toArray(),
                'password' => 'power@123',
            ]);
        }
    }
}
