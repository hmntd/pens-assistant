<?php

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
        Schema::create('tax_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            $table->foreignUuid('document_id')->nullable()->constrained('documents')->nullOnDelete();

            $table->integer('year');
            $table->decimal('annual_income', 12, 2);
            $table->decimal('tax_paid', 12, 2);
            $table->integer('months_worked')->default(12);

            $table->timestamps();

            $table->unique(['user_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_histories');
    }
};
