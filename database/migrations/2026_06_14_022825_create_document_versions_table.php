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
        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();

            $table->integer('version_number');
            $table->string('file_path');
            $table->string('file_original_name');
            $table->string('file_type');
            $table->integer('file_size')->nullable();

            $table->longText('extracted_text')->nullable();
            $table->text('revision_note')->nullable();
            $table->timestamp('uploaded_at')->nullable();

            $table->timestamps();

            $table->unique(['document_id', 'version_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_versions');
    }
};
