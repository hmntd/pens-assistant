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
        if (Schema::hasTable('users') && (Schema::getColumnListing('users')[0] ?? null) === 'id') {
            return;
        }

        // -------------------------------------------------------------------
        // 1. Drop existing Foreign Key Constraints across all tables
        // -------------------------------------------------------------------
        if (Schema::hasTable('recognized_documents')) {
            Schema::table('recognized_documents', function (Blueprint $table) {
                $table->dropForeign(['document_id']);
            });
        }

        if (Schema::hasTable('tax_histories')) {
            Schema::table('tax_histories', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropForeign(['document_id']);
            });
        }

        if (Schema::hasTable('calculated_pensions')) {
            Schema::table('calculated_pensions', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }

        if (Schema::hasTable('documents')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }

        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }

        if (Schema::hasTable('passkeys')) {
            Schema::table('passkeys', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }

        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }

        if (Schema::hasTable('user_notification_channels')) {
            Schema::table('user_notification_channels', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }

        if (Schema::hasTable('system_error_logs')) {
            Schema::table('system_error_logs', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropForeign(['resolved_by_id']);
            });
        }

        // -------------------------------------------------------------------
        // 2. Rebuild 'users' table layout
        // -------------------------------------------------------------------
        if (Schema::hasTable('users')) {
            Schema::create('temp_users', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('first_name');
                $table->string('last_name');
                $table->string('email')->unique();
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

            DB::statement('INSERT INTO temp_users (id, first_name, last_name, email, email_verified_at, password, remember_token, two_factor_secret, two_factor_recovery_codes, two_factor_confirmed_at, target_retirement_year, gender, date_of_birth, disability_group, benefits, is_suspended, provider_name, provider_id, avatar, created_at, updated_at, deleted_at) SELECT id, first_name, last_name, email, email_verified_at, password, remember_token, two_factor_secret, two_factor_recovery_codes, two_factor_confirmed_at, target_retirement_year, gender, date_of_birth, disability_group, benefits, is_suspended, provider_name, provider_id, avatar, created_at, updated_at, deleted_at FROM users');

            Schema::drop('users');
            Schema::rename('temp_users', 'users');
        }

        // -------------------------------------------------------------------
        // 3. Rebuild 'documents' table layout
        // -------------------------------------------------------------------
        if (Schema::hasTable('documents')) {
            Schema::create('temp_documents', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('user_id');
                $table->string('file_path');
                $table->string('original_filename');
                $table->string('document_type');
                $table->string('status')->default('pending');
                $table->timestamps();
                $table->softDeletes();
            });

            DB::statement('INSERT INTO temp_documents (id, user_id, file_path, original_filename, document_type, status, created_at, updated_at, deleted_at) SELECT id, user_id, file_path, original_filename, document_type, status, created_at, updated_at, deleted_at FROM documents');

            Schema::drop('documents');
            Schema::rename('temp_documents', 'documents');
        }

        // -------------------------------------------------------------------
        // 4. Rebuild 'calculated_pensions' table layout
        // -------------------------------------------------------------------
        if (Schema::hasTable('calculated_pensions')) {
            Schema::create('temp_calculated_pensions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('user_id');
                $table->string('status')->default('completed')->index();
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

            DB::statement('INSERT INTO temp_calculated_pensions (id, user_id, status, error_message, final_pension, base_pension, zp_macroeconomic_average, kz_wage_coefficient, ks_service_coefficient, total_service_months, pension_type, disability_group, input_parameters, applied_benefits, calculation_logs, estimated_monthly_pension, total_accumulated_capital, calculation_breakdown, created_at, updated_at, deleted_at) SELECT id, user_id, status, error_message, final_pension, base_pension, zp_macroeconomic_average, kz_wage_coefficient, ks_service_coefficient, total_service_months, pension_type, disability_group, input_parameters, applied_benefits, calculation_logs, estimated_monthly_pension, total_accumulated_capital, calculation_breakdown, created_at, updated_at, deleted_at FROM calculated_pensions');

            Schema::drop('calculated_pensions');
            Schema::rename('temp_calculated_pensions', 'calculated_pensions');
        }

        // -------------------------------------------------------------------
        // 5. Rebuild 'recognized_documents' table layout
        // -------------------------------------------------------------------
        if (Schema::hasTable('recognized_documents')) {
            Schema::create('temp_recognized_documents', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('document_id')->unique();
                $table->foreignId('template_id')->nullable()->constrained('document_templates')->nullOnDelete();
                $table->enum('status', ['processing', 'success', 'needs_review', 'failed'])->default('processing');
                $table->text('raw_text')->nullable();
                $table->jsonb('extracted_data')->nullable();
                $table->decimal('confidence_score', 4, 3)->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });

            DB::statement('INSERT INTO temp_recognized_documents (id, document_id, template_id, status, raw_text, extracted_data, confidence_score, error_message, created_at, updated_at, deleted_at) SELECT id, document_id, template_id, status, raw_text, extracted_data, confidence_score, error_message, created_at, updated_at, deleted_at FROM recognized_documents');

            Schema::drop('recognized_documents');
            Schema::rename('temp_recognized_documents', 'recognized_documents');
        }

        // -------------------------------------------------------------------
        // 6. Rebuild 'tax_histories' table layout
        // -------------------------------------------------------------------
        if (Schema::hasTable('tax_histories')) {
            Schema::create('temp_tax_histories', function (Blueprint $table) {
                $table->id();
                $table->uuid('user_id');
                $table->uuid('document_id')->nullable();
                $table->integer('year');
                $table->decimal('annual_income', 12, 2);
                $table->decimal('tax_paid', 12, 2);
                $table->integer('months_worked')->default(12);
                $table->jsonb('monthly_breakdown')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['user_id', 'year']);
            });

            DB::statement('INSERT INTO temp_tax_histories (id, user_id, document_id, year, annual_income, tax_paid, months_worked, monthly_breakdown, created_at, updated_at, deleted_at) SELECT id, user_id, document_id, year, annual_income, tax_paid, months_worked, monthly_breakdown, created_at, updated_at, deleted_at FROM tax_histories');

            Schema::drop('tax_histories');
            Schema::rename('temp_tax_histories', 'tax_histories');
        }

        // -------------------------------------------------------------------
        // 7. Rebuild 'audit_logs' table layout
        // -------------------------------------------------------------------
        if (Schema::hasTable('audit_logs')) {
            Schema::create('temp_audit_logs', function (Blueprint $table) {
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

            DB::statement('INSERT INTO temp_audit_logs (id, user_id, action, entity_type, entity_id, payload, ip_address, user_agent, created_at, updated_at) SELECT id, user_id, action, entity_type, entity_id, payload, ip_address, user_agent, created_at, updated_at FROM audit_logs');

            Schema::drop('audit_logs');
            Schema::rename('temp_audit_logs', 'audit_logs');
        }

        // -------------------------------------------------------------------
        // 8. Rebuild 'passkeys' table layout
        // -------------------------------------------------------------------
        if (Schema::hasTable('passkeys')) {
            Schema::create('temp_passkeys', function (Blueprint $table) {
                $table->id();
                $table->uuid('user_id');
                $table->string('name');
                $table->string('credential_id')->unique();
                $table->json('credential');
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();

                $table->index('user_id');
            });

            DB::statement('INSERT INTO temp_passkeys (id, user_id, name, credential_id, credential, last_used_at, created_at, updated_at) SELECT id, user_id, name, credential_id, credential, last_used_at, created_at, updated_at FROM passkeys');

            Schema::drop('passkeys');
            Schema::rename('temp_passkeys', 'passkeys');
        }

        // -------------------------------------------------------------------
        // 9. Rebuild 'notifications' table layout
        // -------------------------------------------------------------------
        if (Schema::hasTable('notifications')) {
            Schema::create('temp_notifications', function (Blueprint $table) {
                $table->id();
                $table->uuid('user_id');
                $table->foreignId('notification_translation_id')->nullable()->constrained('notification_translations')->nullOnDelete();
                $table->string('type');
                $table->boolean('is_seen')->default(false);
                $table->timestamps();
            });

            DB::statement('INSERT INTO temp_notifications (id, user_id, notification_translation_id, type, is_seen, created_at, updated_at) SELECT id, user_id, notification_translation_id, type, is_seen, created_at, updated_at FROM notifications');

            Schema::drop('notifications');
            Schema::rename('temp_notifications', 'notifications');
        }

        // -------------------------------------------------------------------
        // 10. Rebuild 'user_notification_channels' table layout
        // -------------------------------------------------------------------
        if (Schema::hasTable('user_notification_channels')) {
            Schema::create('temp_user_notification_channels', function (Blueprint $table) {
                $table->id();
                $table->uuid('user_id')->unique();
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

            DB::statement('INSERT INTO temp_user_notification_channels (id, user_id, email_enabled, telegram_enabled, telegram_chat_id, sms_enabled, phone_number, notify_calc_completed, notify_document_processed, notify_system_alerts, notify_pension_updates, created_at, updated_at) SELECT id, user_id, email_enabled, telegram_enabled, telegram_chat_id, sms_enabled, phone_number, notify_calc_completed, notify_document_processed, notify_system_alerts, notify_pension_updates, created_at, updated_at FROM user_notification_channels');

            Schema::drop('user_notification_channels');
            Schema::rename('temp_user_notification_channels', 'user_notification_channels');
        }

        // -------------------------------------------------------------------
        // 11. Rebuild 'system_error_logs' table layout
        // -------------------------------------------------------------------
        if (Schema::hasTable('system_error_logs')) {
            Schema::create('temp_system_error_logs', function (Blueprint $table) {
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

                $table->index(['is_resolved', 'created_at']);
                $table->index('status_code');
            });

            DB::statement('INSERT INTO temp_system_error_logs (id, user_id, status_code, url, method, exception_class, message, stack_trace, user_agent, ip_address, is_resolved, resolved_at, resolved_by_id, created_at, updated_at) SELECT id, user_id, status_code, url, method, exception_class, message, stack_trace, user_agent, ip_address, is_resolved, resolved_at, resolved_by_id, created_at, updated_at FROM system_error_logs');

            Schema::drop('system_error_logs');
            Schema::rename('temp_system_error_logs', 'system_error_logs');
        }

        // -------------------------------------------------------------------
        // 12. Re-establish Foreign Key Constraints
        // -------------------------------------------------------------------
        Schema::table('documents', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('calculated_pensions', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('recognized_documents', function (Blueprint $table) {
            $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
        });

        Schema::table('tax_histories', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('document_id')->references('id')->on('documents')->nullOnDelete();
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('passkeys', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('user_notification_channels', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('system_error_logs', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('resolved_by_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
