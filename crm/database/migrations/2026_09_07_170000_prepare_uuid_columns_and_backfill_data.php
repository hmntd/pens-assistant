<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('users') && in_array(Schema::getColumnType('users', 'id'), ['guid', 'string', 'uuid'])) {
            return;
        }

        // 1. Add temporary uuid columns to primary tables
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        Schema::table('calculated_pensions', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
            $table->uuid('new_user_id')->nullable()->after('user_id');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
            $table->uuid('new_user_id')->nullable()->after('user_id');
        });

        Schema::table('recognized_documents', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
            $table->uuid('new_document_id')->nullable()->after('document_id');
        });

        // 2. Add temporary new foreign key columns to dependent tables
        if (Schema::hasTable('tax_histories')) {
            Schema::table('tax_histories', function (Blueprint $table) {
                $table->uuid('new_user_id')->nullable()->after('user_id');
                $table->uuid('new_document_id')->nullable()->after('document_id');
            });
        }

        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->uuid('new_user_id')->nullable()->after('user_id');
            });
        }

        if (Schema::hasTable('passkeys')) {
            Schema::table('passkeys', function (Blueprint $table) {
                $table->uuid('new_user_id')->nullable()->after('user_id');
            });
        }

        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->uuid('new_user_id')->nullable()->after('user_id');
            });
        }

        if (Schema::hasTable('user_notification_channels')) {
            Schema::table('user_notification_channels', function (Blueprint $table) {
                $table->uuid('new_user_id')->nullable()->after('user_id');
            });
        }

        if (Schema::hasTable('system_error_logs')) {
            Schema::table('system_error_logs', function (Blueprint $table) {
                $table->uuid('new_user_id')->nullable()->after('user_id');
                $table->uuid('new_resolved_by_id')->nullable()->after('resolved_by_id');
            });
        }

        if (Schema::hasTable('sessions')) {
            Schema::table('sessions', function (Blueprint $table) {
                $table->uuid('new_user_id')->nullable()->after('user_id');
            });
        }

        if (Schema::hasTable('model_has_roles')) {
            Schema::table('model_has_roles', function (Blueprint $table) {
                $table->uuid('new_model_id')->nullable()->after('model_id');
            });
        }

        if (Schema::hasTable('model_has_permissions')) {
            Schema::table('model_has_permissions', function (Blueprint $table) {
                $table->uuid('new_model_id')->nullable()->after('model_id');
            });
        }

        // 3. Backfill UUIDs for primary tables
        $tables = ['users', 'calculated_pensions', 'documents', 'recognized_documents'];
        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                DB::table($tableName)->whereNull('uuid')->orderBy('id')->chunkById(200, function ($rows) use ($tableName) {
                    foreach ($rows as $row) {
                        DB::table($tableName)
                            ->where('id', $row->id)
                            ->update(['uuid' => (string) Str::orderedUuid()]);
                    }
                });
            }
        }

        // 4. Backfill foreign keys using ANSI SQL subqueries (compatible with Postgres, MySQL, SQLite)
        if (Schema::hasTable('calculated_pensions')) {
            DB::table('calculated_pensions')
                ->whereNotNull('user_id')
                ->update(['new_user_id' => DB::raw('(SELECT uuid FROM users WHERE users.id = calculated_pensions.user_id)')]);
        }

        if (Schema::hasTable('documents')) {
            DB::table('documents')
                ->whereNotNull('user_id')
                ->update(['new_user_id' => DB::raw('(SELECT uuid FROM users WHERE users.id = documents.user_id)')]);
        }

        if (Schema::hasTable('recognized_documents')) {
            DB::table('recognized_documents')
                ->whereNotNull('document_id')
                ->update(['new_document_id' => DB::raw('(SELECT uuid FROM documents WHERE documents.id = recognized_documents.document_id)')]);
        }

        if (Schema::hasTable('tax_histories')) {
            DB::table('tax_histories')
                ->whereNotNull('user_id')
                ->update(['new_user_id' => DB::raw('(SELECT uuid FROM users WHERE users.id = tax_histories.user_id)')]);

            DB::table('tax_histories')
                ->whereNotNull('document_id')
                ->update(['new_document_id' => DB::raw('(SELECT uuid FROM documents WHERE documents.id = tax_histories.document_id)')]);
        }

        if (Schema::hasTable('audit_logs')) {
            DB::table('audit_logs')
                ->whereNotNull('user_id')
                ->update(['new_user_id' => DB::raw('(SELECT uuid FROM users WHERE users.id = audit_logs.user_id)')]);
        }

        if (Schema::hasTable('passkeys')) {
            DB::table('passkeys')
                ->whereNotNull('user_id')
                ->update(['new_user_id' => DB::raw('(SELECT uuid FROM users WHERE users.id = passkeys.user_id)')]);
        }

        if (Schema::hasTable('notifications')) {
            DB::table('notifications')
                ->whereNotNull('user_id')
                ->update(['new_user_id' => DB::raw('(SELECT uuid FROM users WHERE users.id = notifications.user_id)')]);
        }

        if (Schema::hasTable('user_notification_channels')) {
            DB::table('user_notification_channels')
                ->whereNotNull('user_id')
                ->update(['new_user_id' => DB::raw('(SELECT uuid FROM users WHERE users.id = user_notification_channels.user_id)')]);
        }

        if (Schema::hasTable('system_error_logs')) {
            DB::table('system_error_logs')
                ->whereNotNull('user_id')
                ->update(['new_user_id' => DB::raw('(SELECT uuid FROM users WHERE users.id = system_error_logs.user_id)')]);

            DB::table('system_error_logs')
                ->whereNotNull('resolved_by_id')
                ->update(['new_resolved_by_id' => DB::raw('(SELECT uuid FROM users WHERE users.id = system_error_logs.resolved_by_id)')]);
        }

        if (Schema::hasTable('sessions')) {
            DB::table('sessions')
                ->whereNotNull('user_id')
                ->update(['new_user_id' => DB::raw('(SELECT uuid FROM users WHERE users.id = sessions.user_id)')]);
        }

        if (Schema::hasTable('model_has_roles')) {
            DB::table('model_has_roles')
                ->where('model_type', 'App\\Models\\User')
                ->update(['new_model_id' => DB::raw('(SELECT uuid FROM users WHERE users.id = model_has_roles.model_id)')]);
        }

        if (Schema::hasTable('model_has_permissions')) {
            DB::table('model_has_permissions')
                ->where('model_type', 'App\\Models\\User')
                ->update(['new_model_id' => DB::raw('(SELECT uuid FROM users WHERE users.id = model_has_permissions.model_id)')]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'model_has_permissions' => ['new_model_id'],
            'model_has_roles' => ['new_model_id'],
            'sessions' => ['new_user_id'],
            'system_error_logs' => ['new_user_id', 'new_resolved_by_id'],
            'user_notification_channels' => ['new_user_id'],
            'notifications' => ['new_user_id'],
            'passkeys' => ['new_user_id'],
            'audit_logs' => ['new_user_id'],
            'tax_histories' => ['new_user_id', 'new_document_id'],
            'recognized_documents' => ['uuid', 'new_document_id'],
            'documents' => ['uuid', 'new_user_id'],
            'calculated_pensions' => ['uuid', 'new_user_id'],
            'users' => ['uuid'],
        ];

        foreach ($tables as $table => $columns) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) use ($columns) {
                    $table->dropColumn($columns);
                });
            }
        }
    }
};
