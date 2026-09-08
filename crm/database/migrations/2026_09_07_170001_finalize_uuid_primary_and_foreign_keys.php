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

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');

            if (Schema::hasTable('recognized_documents') && Schema::hasColumn('recognized_documents', 'uuid')) {
                Schema::create('temp_rec_docs', function (Blueprint $table) {
                    $table->uuid('id')->primary();
                    $table->uuid('document_id');
                    $table->foreignId('template_id')->nullable();
                    $table->string('status')->default('processing');
                    $table->text('raw_text')->nullable();
                    $table->json('extracted_data')->nullable();
                    $table->decimal('confidence_score', 4, 3)->nullable();
                    $table->text('error_message')->nullable();
                    $table->timestamps();
                    $table->softDeletes();
                });
                DB::statement('INSERT INTO temp_rec_docs (id, document_id, template_id, status, raw_text, extracted_data, confidence_score, error_message, created_at, updated_at, deleted_at) SELECT uuid, new_document_id, template_id, status, raw_text, extracted_data, confidence_score, error_message, created_at, updated_at, deleted_at FROM recognized_documents');
                Schema::drop('recognized_documents');
                Schema::rename('temp_rec_docs', 'recognized_documents');
            }

            if (Schema::hasTable('tax_histories') && Schema::hasColumn('tax_histories', 'new_user_id')) {
                Schema::create('temp_tax_hist', function (Blueprint $table) {
                    $table->id();
                    $table->uuid('user_id');
                    $table->uuid('document_id')->nullable();
                    $table->integer('year');
                    $table->decimal('annual_income', 12, 2);
                    $table->decimal('tax_paid', 12, 2);
                    $table->integer('months_worked')->default(12);
                    $table->json('monthly_breakdown')->nullable();
                    $table->timestamps();
                    $table->softDeletes();
                });
                DB::statement('INSERT INTO temp_tax_hist (id, user_id, document_id, year, annual_income, tax_paid, months_worked, monthly_breakdown, created_at, updated_at, deleted_at) SELECT id, new_user_id, new_document_id, year, annual_income, tax_paid, months_worked, monthly_breakdown, created_at, updated_at, deleted_at FROM tax_histories');
                Schema::drop('tax_histories');
                Schema::rename('temp_tax_hist', 'tax_histories');
            }

            if (Schema::hasTable('calculated_pensions') && Schema::hasColumn('calculated_pensions', 'uuid')) {
                Schema::create('temp_calc_pens', function (Blueprint $table) {
                    $table->uuid('id')->primary();
                    $table->uuid('user_id');
                    $table->string('status')->default('completed');
                    $table->text('error_message')->nullable();
                    $table->decimal('final_pension', 12, 2)->nullable();
                    $table->decimal('base_pension', 12, 2)->nullable();
                    $table->decimal('zp_macroeconomic_average', 12, 2)->nullable();
                    $table->decimal('kz_wage_coefficient', 8, 4)->nullable();
                    $table->decimal('ks_service_coefficient', 8, 4)->nullable();
                    $table->integer('total_service_months')->nullable();
                    $table->string('pension_type')->nullable();
                    $table->string('disability_group')->nullable();
                    $table->json('input_parameters')->nullable();
                    $table->json('applied_benefits')->nullable();
                    $table->json('calculation_logs')->nullable();
                    $table->decimal('estimated_monthly_pension', 10, 2);
                    $table->decimal('total_accumulated_capital', 12, 2);
                    $table->json('calculation_breakdown')->nullable();
                    $table->timestamps();
                    $table->softDeletes();
                });
                DB::statement('INSERT INTO temp_calc_pens (id, user_id, status, error_message, final_pension, base_pension, zp_macroeconomic_average, kz_wage_coefficient, ks_service_coefficient, total_service_months, pension_type, disability_group, input_parameters, applied_benefits, calculation_logs, estimated_monthly_pension, total_accumulated_capital, calculation_breakdown, created_at, updated_at, deleted_at) SELECT uuid, new_user_id, status, error_message, final_pension, base_pension, zp_macroeconomic_average, kz_wage_coefficient, ks_service_coefficient, total_service_months, pension_type, disability_group, input_parameters, applied_benefits, calculation_logs, estimated_monthly_pension, total_accumulated_capital, calculation_breakdown, created_at, updated_at, deleted_at FROM calculated_pensions');
                Schema::drop('calculated_pensions');
                Schema::rename('temp_calc_pens', 'calculated_pensions');
            }

            if (Schema::hasTable('documents') && Schema::hasColumn('documents', 'uuid')) {
                Schema::create('temp_docs', function (Blueprint $table) {
                    $table->uuid('id')->primary();
                    $table->uuid('user_id');
                    $table->string('file_path');
                    $table->string('original_filename');
                    $table->string('document_type');
                    $table->string('status')->default('pending');
                    $table->timestamps();
                    $table->softDeletes();
                });
                DB::statement('INSERT INTO temp_docs (id, user_id, file_path, original_filename, document_type, status, created_at, updated_at, deleted_at) SELECT uuid, new_user_id, file_path, original_filename, document_type, status, created_at, updated_at, deleted_at FROM documents');
                Schema::drop('documents');
                Schema::rename('temp_docs', 'documents');
            }

            if (Schema::hasTable('audit_logs') && Schema::hasColumn('audit_logs', 'new_user_id')) {
                Schema::create('temp_audit_l', function (Blueprint $table) {
                    $table->id();
                    $table->uuid('user_id')->nullable();
                    $table->string('action');
                    $table->string('entity_type')->nullable();
                    $table->unsignedBigInteger('entity_id')->nullable();
                    $table->json('payload')->nullable();
                    $table->string('ip_address', 45)->nullable();
                    $table->text('user_agent')->nullable();
                    $table->timestamps();
                });
                DB::statement('INSERT INTO temp_audit_l (id, user_id, action, entity_type, entity_id, payload, ip_address, user_agent, created_at, updated_at) SELECT id, new_user_id, action, entity_type, entity_id, payload, ip_address, user_agent, created_at, updated_at FROM audit_logs');
                Schema::drop('audit_logs');
                Schema::rename('temp_audit_l', 'audit_logs');
            }

            if (Schema::hasTable('passkeys') && Schema::hasColumn('passkeys', 'new_user_id')) {
                Schema::create('temp_passk', function (Blueprint $table) {
                    $table->id();
                    $table->uuid('user_id');
                    $table->string('name');
                    $table->string('credential_id');
                    $table->json('credential');
                    $table->timestamp('last_used_at')->nullable();
                    $table->timestamps();
                });
                DB::statement('INSERT INTO temp_passk (id, user_id, name, credential_id, credential, last_used_at, created_at, updated_at) SELECT id, new_user_id, name, credential_id, credential, last_used_at, created_at, updated_at FROM passkeys');
                Schema::drop('passkeys');
                Schema::rename('temp_passk', 'passkeys');
            }

            if (Schema::hasTable('notifications') && Schema::hasColumn('notifications', 'new_user_id')) {
                Schema::create('temp_notif', function (Blueprint $table) {
                    $table->id();
                    $table->uuid('user_id');
                    $table->foreignId('notification_translation_id')->nullable();
                    $table->string('type');
                    $table->boolean('is_seen')->default(false);
                    $table->timestamps();
                });
                DB::statement('INSERT INTO temp_notif (id, user_id, notification_translation_id, type, is_seen, created_at, updated_at) SELECT id, new_user_id, notification_translation_id, type, is_seen, created_at, updated_at FROM notifications');
                Schema::drop('notifications');
                Schema::rename('temp_notif', 'notifications');
            }

            if (Schema::hasTable('user_notification_channels') && Schema::hasColumn('user_notification_channels', 'new_user_id')) {
                Schema::create('temp_user_notif_chan', function (Blueprint $table) {
                    $table->id();
                    $table->uuid('user_id');
                    $table->boolean('email_enabled')->default(true);
                    $table->boolean('telegram_enabled')->default(false);
                    $table->string('telegram_chat_id')->nullable();
                    $table->boolean('sms_enabled')->default(false);
                    $table->string('phone_number')->nullable();
                    $table->boolean('notify_calc_completed')->default(true);
                    $table->boolean('notify_document_processed')->default(true);
                    $table->boolean('notify_system_alerts')->default(true);
                    $table->boolean('notify_pension_updates')->default(false);
                    $table->timestamps();
                });
                DB::statement('INSERT INTO temp_user_notif_chan (id, user_id, email_enabled, telegram_enabled, telegram_chat_id, sms_enabled, phone_number, notify_calc_completed, notify_document_processed, notify_system_alerts, notify_pension_updates, created_at, updated_at) SELECT id, new_user_id, email_enabled, telegram_enabled, telegram_chat_id, sms_enabled, phone_number, notify_calc_completed, notify_document_processed, notify_system_alerts, notify_pension_updates, created_at, updated_at FROM user_notification_channels');
                Schema::drop('user_notification_channels');
                Schema::rename('temp_user_notif_chan', 'user_notification_channels');
            }

            if (Schema::hasTable('system_error_logs') && Schema::hasColumn('system_error_logs', 'new_user_id')) {
                Schema::create('temp_sys_err_logs', function (Blueprint $table) {
                    $table->id();
                    $table->uuid('user_id')->nullable();
                    $table->integer('status_code')->default(500);
                    $table->string('url', 2048);
                    $table->string('method', 10)->default('GET');
                    $table->string('exception_class');
                    $table->text('message');
                    $table->longText('stack_trace')->nullable();
                    $table->text('user_agent')->nullable();
                    $table->string('ip_address', 45)->nullable();
                    $table->boolean('is_resolved')->default(false);
                    $table->timestamp('resolved_at')->nullable();
                    $table->uuid('resolved_by_id')->nullable();
                    $table->timestamps();
                });
                DB::statement('INSERT INTO temp_sys_err_logs (id, user_id, status_code, url, method, exception_class, message, stack_trace, user_agent, ip_address, is_resolved, resolved_at, resolved_by_id, created_at, updated_at) SELECT id, new_user_id, status_code, url, method, exception_class, message, stack_trace, user_agent, ip_address, is_resolved, resolved_at, new_resolved_by_id, created_at, updated_at FROM system_error_logs');
                Schema::drop('system_error_logs');
                Schema::rename('temp_sys_err_logs', 'system_error_logs');
            }

            if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'new_user_id')) {
                Schema::create('temp_sess', function (Blueprint $table) {
                    $table->string('id')->primary();
                    $table->uuid('user_id')->nullable();
                    $table->string('ip_address', 45)->nullable();
                    $table->text('user_agent')->nullable();
                    $table->longText('payload');
                    $table->integer('last_activity');
                });
                DB::statement('INSERT INTO temp_sess (id, user_id, ip_address, user_agent, payload, last_activity) SELECT id, new_user_id, ip_address, user_agent, payload, last_activity FROM sessions');
                Schema::drop('sessions');
                Schema::rename('temp_sess', 'sessions');
            }

            if (Schema::hasTable('model_has_roles') && Schema::hasColumn('model_has_roles', 'new_model_id')) {
                Schema::create('temp_mod_roles', function (Blueprint $table) {
                    $table->unsignedBigInteger('role_id');
                    $table->string('model_type');
                    $table->uuid('model_id');
                    $table->primary(['role_id', 'model_id', 'model_type']);
                });
                DB::statement('INSERT INTO temp_mod_roles (role_id, model_type, model_id) SELECT role_id, model_type, new_model_id FROM model_has_roles');
                Schema::drop('model_has_roles');
                Schema::rename('temp_mod_roles', 'model_has_roles');
            }

            if (Schema::hasTable('model_has_permissions') && Schema::hasColumn('model_has_permissions', 'new_model_id')) {
                Schema::create('temp_mod_perms', function (Blueprint $table) {
                    $table->unsignedBigInteger('permission_id');
                    $table->string('model_type');
                    $table->uuid('model_id');
                    $table->primary(['permission_id', 'model_id', 'model_type']);
                });
                DB::statement('INSERT INTO temp_mod_perms (permission_id, model_type, model_id) SELECT permission_id, model_type, new_model_id FROM model_has_permissions');
                Schema::drop('model_has_permissions');
                Schema::rename('temp_mod_perms', 'model_has_permissions');
            }

            if (Schema::hasTable('users') && Schema::hasColumn('users', 'uuid')) {
                Schema::create('temp_usr', function (Blueprint $table) {
                    $table->uuid('id')->primary();
                    $table->string('first_name');
                    $table->string('last_name');
                    $table->string('email');
                    $table->timestamp('email_verified_at')->nullable();
                    $table->string('password');
                    $table->rememberToken();
                    $table->string('two_factor_secret')->nullable();
                    $table->text('two_factor_recovery_codes')->nullable();
                    $table->timestamp('two_factor_confirmed_at')->nullable();
                    $table->integer('target_retirement_year')->nullable();
                    $table->string('gender')->nullable();
                    $table->date('date_of_birth')->nullable();
                    $table->string('disability_group')->nullable();
                    $table->json('benefits')->nullable();
                    $table->boolean('is_suspended')->default(false);
                    $table->string('provider_name')->nullable();
                    $table->string('provider_id')->nullable();
                    $table->string('avatar')->nullable();
                    $table->timestamps();
                    $table->softDeletes();
                });
                DB::statement('INSERT INTO temp_usr (id, first_name, last_name, email, email_verified_at, password, remember_token, two_factor_secret, two_factor_recovery_codes, two_factor_confirmed_at, target_retirement_year, gender, date_of_birth, disability_group, benefits, is_suspended, provider_name, provider_id, avatar, created_at, updated_at, deleted_at) SELECT uuid, first_name, last_name, email, email_verified_at, password, remember_token, two_factor_secret, two_factor_recovery_codes, two_factor_confirmed_at, target_retirement_year, gender, date_of_birth, disability_group, benefits, is_suspended, provider_name, provider_id, avatar, created_at, updated_at, deleted_at FROM users');
                Schema::drop('users');
                Schema::rename('temp_usr', 'users');
            }

            DB::statement('PRAGMA foreign_keys = ON;');
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
