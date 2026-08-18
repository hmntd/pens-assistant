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
        $this->call(RoleSeeder::class);

        $adminEmail = (string) config('app.admin.email', '');
        $adminPassword = (string) config('app.admin.password', '');

        if (empty($adminEmail) || empty($adminPassword)) {
            return;
        }

        $admin = User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => $adminEmail,
            'password' => $adminPassword,
        ]);
        $admin->assignRole('admin');
    }
}
