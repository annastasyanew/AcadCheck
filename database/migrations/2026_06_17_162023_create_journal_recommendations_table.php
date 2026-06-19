<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_recommendations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('document_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('journal_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->integer('fit_score')->default(0);
            $table->text('fit_reason')->nullable();
            $table->text('submission_risk')->nullable();
            $table->text('suggested_improvement')->nullable();
            $table->json('raw_ai_response')->nullable();

            $table->timestamps();

            $table->index('document_id');
            $table->index('journal_id');
            $table->index('fit_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_recommendations');
    }
};
