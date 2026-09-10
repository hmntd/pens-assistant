<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('users', 'name') && ! Schema::hasColumn('users', 'first_name')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('first_name')->nullable()->after('id');
                $table->string('last_name')->nullable()->after('first_name');
            });

            // Migrate existing name data if any exists
            $users = DB::table('users')->get();
            foreach ($users as $user) {
                $parts = explode(' ', trim($user->name ?? ''), 2);
                $firstName = $parts[0] ?? 'User';
                $lastName = $parts[1] ?? '';
                DB::table('users')->where('id', $user->id)->update([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                ]);
            }

            Schema::table('users', function (Blueprint $table) {
                $table->string('first_name')->nullable(false)->change();
                $table->string('last_name')->nullable(false)->change();
                $table->dropColumn('name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('users', 'name')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('name')->nullable();
            });

            $users = DB::table('users')->get();
            foreach ($users as $user) {
                DB::table('users')->where('id', $user->id)->update([
                    'name' => trim("{$user->first_name} {$user->last_name}"),
                ]);
            }

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['first_name', 'last_name']);
            });
        }
    }
};
