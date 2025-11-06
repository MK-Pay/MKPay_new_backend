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
        // Seed roles and permissions first
        $this->call(RolesAndPermissionsSeeder::class);

        $initialUsers = [
            [
                'name' => 'Admin',
                'email' => 'admin@mail.com',
                'password' => 'power@123',
            ],
        ];

        foreach ($initialUsers as $userData) {
            $user = User::factory()->make($userData);

            $user = User::updateOrCreate([
                'email' => $user->email,
            ], [
                ...$user->toArray(),
                'password' => 'power@123',
            ]);

            // Assign super_admin role to admin user
            if ($user->email === 'admin@mail.com') {
                $user->assignRole('super_admin');
            }
        }
    }
}
