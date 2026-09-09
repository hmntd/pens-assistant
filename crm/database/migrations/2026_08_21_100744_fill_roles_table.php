<?php

use Illuminate\Database\Migrations\Migration; 
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $roles = ['admin', 'user'];

        foreach ($roles as $role) {
            Role::create(['name' => $role]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
