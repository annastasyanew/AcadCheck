<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journals', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('publisher')->nullable();
            $table->string('sinta_level')->nullable();
            $table->string('subject_area')->nullable();

            $table->text('focus_scope')->nullable();
            $table->text('keywords')->nullable();

            $table->string('p_issn')->nullable();
            $table->string('e_issn')->nullable();

            $table->string('website_url')->nullable();
            $table->string('editor_url')->nullable();
            $table->string('template_url')->nullable();
            $table->string('author_guideline_url')->nullable();

            $table->text('indexing')->nullable();
            $table->string('impact')->nullable();
            $table->string('h5_index')->nullable();
            $table->string('citations_5yr')->nullable();
            $table->string('citations_total')->nullable();

            $table->string('source_url')->nullable();
            $table->text('raw_text')->nullable();

            $table->boolean('is_active')->default(false);
            $table->string('verification_status')->default('pending_review');
            $table->date('last_verified_at')->nullable();

            $table->timestamps();

            $table->index('sinta_level');
            $table->index('subject_area');
            $table->index('is_active');
            $table->index('verification_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journals');
    }
};
