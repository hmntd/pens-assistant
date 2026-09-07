<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
