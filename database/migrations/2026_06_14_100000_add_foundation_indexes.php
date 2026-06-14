<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->unique('name');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->index(['user_id', 'status']);
            $table->index('latest_version_id');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['latest_version_id']);
        });

        Schema::table('document_types', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });
    }
};
