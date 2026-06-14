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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained()->restrictOnDelete();

            $table->string('title');
            $table->string('topic')->nullable();
            $table->text('keywords')->nullable();
            $table->text('description')->nullable();

            $table->enum('status', [
                'uploaded',
                'analyzed',
                'need_revision',
                'revised',
                'ready',
                'archived',
            ])->default('uploaded');

            $table->integer('latest_score')->nullable();
            $table->unsignedBigInteger('latest_version_id')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
