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
        if (! Schema::hasColumn('users', 'uuid')) {
            return;
        }
        // 1. Drop Foreign Keys & Columns from dependent tables
        if (Schema::hasTable('recognized_documents')) {
            Schema::table('recognized_documents', function (Blueprint $table) {
                $table->dropForeign(['document_id']);
                $table->dropColumn(['id', 'document_id']);
            });
        }

        if (Schema::hasTable('tax_histories')) {
            Schema::table('tax_histories', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropForeign(['document_id']);
                $table->dropColumn(['user_id', 'document_id']);
            });
        }

        if (Schema::hasTable('calculated_pensions')) {
            Schema::table('calculated_pensions', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn(['id', 'user_id']);
            });
        }

        if (Schema::hasTable('documents')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn(['id', 'user_id']);
            });
        }

        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            });
        }

        if (Schema::hasTable('passkeys')) {
            Schema::table('passkeys', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            });
        }

        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            });
        }

        if (Schema::hasTable('user_notification_channels')) {
            Schema::table('user_notification_channels', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            });
        }

        if (Schema::hasTable('system_error_logs')) {
            Schema::table('system_error_logs', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropForeign(['resolved_by_id']);
                $table->dropColumn(['user_id', 'resolved_by_id']);
            });
        }

        if (Schema::hasTable('sessions')) {
            Schema::table('sessions', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
        }

        if (Schema::hasTable('model_has_roles')) {
            Schema::table('model_has_roles', function (Blueprint $table) {
                $table->dropPrimary(['role_id', 'model_id', 'model_type']);
                $table->dropColumn('model_id');
            });
        }

        if (Schema::hasTable('model_has_permissions')) {
            Schema::table('model_has_permissions', function (Blueprint $table) {
                $table->dropPrimary(['permission_id', 'model_id', 'model_type']);
                $table->dropColumn('model_id');
            });
        }

        // Drop users integer primary key
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('id');
            });
        }

        // 2. Rename temporary columns to standard names
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('uuid', 'id');
        });

        Schema::table('calculated_pensions', function (Blueprint $table) {
            $table->renameColumn('uuid', 'id');
            $table->renameColumn('new_user_id', 'user_id');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->renameColumn('uuid', 'id');
            $table->renameColumn('new_user_id', 'user_id');
        });

        Schema::table('recognized_documents', function (Blueprint $table) {
            $table->renameColumn('uuid', 'id');
            $table->renameColumn('new_document_id', 'document_id');
        });

        if (Schema::hasTable('tax_histories')) {
            Schema::table('tax_histories', function (Blueprint $table) {
                $table->renameColumn('new_user_id', 'user_id');
                $table->renameColumn('new_document_id', 'document_id');
            });
        }

        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->renameColumn('new_user_id', 'user_id');
            });
        }

        if (Schema::hasTable('passkeys')) {
            Schema::table('passkeys', function (Blueprint $table) {
                $table->renameColumn('new_user_id', 'user_id');
            });
        }

        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->renameColumn('new_user_id', 'user_id');
            });
        }

        if (Schema::hasTable('user_notification_channels')) {
            Schema::table('user_notification_channels', function (Blueprint $table) {
                $table->renameColumn('new_user_id', 'user_id');
            });
        }

        if (Schema::hasTable('system_error_logs')) {
            Schema::table('system_error_logs', function (Blueprint $table) {
                $table->renameColumn('new_user_id', 'user_id');
                $table->renameColumn('new_resolved_by_id', 'resolved_by_id');
            });
        }

        if (Schema::hasTable('sessions')) {
            Schema::table('sessions', function (Blueprint $table) {
                $table->renameColumn('new_user_id', 'user_id');
            });
        }

        if (Schema::hasTable('model_has_roles')) {
            Schema::table('model_has_roles', function (Blueprint $table) {
                $table->renameColumn('new_model_id', 'model_id');
            });
        }

        if (Schema::hasTable('model_has_permissions')) {
            Schema::table('model_has_permissions', function (Blueprint $table) {
                $table->renameColumn('new_model_id', 'model_id');
            });
        }

        // 3. Set Primary Keys & Constraints
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('id')->nullable(false)->change();
            $table->primary('id');
        });

        Schema::table('calculated_pensions', function (Blueprint $table) {
            $table->uuid('id')->nullable(false)->change();
            $table->primary('id');
            $table->uuid('user_id')->nullable(false)->change();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->uuid('id')->nullable(false)->change();
            $table->primary('id');
            $table->uuid('user_id')->nullable(false)->change();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::table('recognized_documents', function (Blueprint $table) {
            $table->uuid('id')->nullable(false)->change();
            $table->primary('id');
            $table->uuid('document_id')->nullable(false)->change();

            $table->foreign('document_id')
                ->references('id')
                ->on('documents')
                ->onDelete('cascade');
        });

        if (Schema::hasTable('tax_histories')) {
            Schema::table('tax_histories', function (Blueprint $table) {
                $table->uuid('user_id')->nullable(false)->change();
                $table->uuid('document_id')->nullable()->change();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');

                $table->foreign('document_id')
                    ->references('id')
                    ->on('documents')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->uuid('user_id')->nullable()->change();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('passkeys')) {
            Schema::table('passkeys', function (Blueprint $table) {
                $table->uuid('user_id')->nullable(false)->change();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');
            });
        }

        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->uuid('user_id')->nullable(false)->change();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');
            });
        }

        if (Schema::hasTable('user_notification_channels')) {
            Schema::table('user_notification_channels', function (Blueprint $table) {
                $table->uuid('user_id')->nullable(false)->change();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');
            });
        }

        if (Schema::hasTable('system_error_logs')) {
            Schema::table('system_error_logs', function (Blueprint $table) {
                $table->uuid('user_id')->nullable()->change();
                $table->uuid('resolved_by_id')->nullable()->change();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();

                $table->foreign('resolved_by_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('model_has_roles')) {
            Schema::table('model_has_roles', function (Blueprint $table) {
                $table->uuid('model_id')->nullable(false)->change();
                $table->primary(['role_id', 'model_id', 'model_type']);
            });
        }

        if (Schema::hasTable('model_has_permissions')) {
            Schema::table('model_has_permissions', function (Blueprint $table) {
                $table->uuid('model_id')->nullable(false)->change();
                $table->primary(['permission_id', 'model_id', 'model_type']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting UUID primary keys in production should be done via backup restore.
    }
};
