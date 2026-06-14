<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rubrics', function (Blueprint $table) {
            $table->foreignId('document_type_id')->after('id')->constrained()->cascadeOnDelete();
            $table->string('aspect_name')->after('document_type_id');
            $table->unsignedInteger('weight')->default(0)->after('aspect_name');
            $table->text('description')->nullable()->after('weight');
            $table->boolean('is_active')->default(true)->after('description');
            $table->unique(['document_type_id', 'aspect_name']);
        });

        Schema::table('analysis_results', function (Blueprint $table) {
            $table->foreignId('document_id')->after('id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_version_id')->after('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('total_score')->default(0)->after('document_version_id');
            $table->string('status')->after('total_score');
            $table->text('summary')->nullable()->after('status');
            $table->json('main_issues')->nullable()->after('summary');
            $table->json('recommendations')->nullable()->after('main_issues');
            $table->json('revision_priorities')->nullable()->after('recommendations');
            $table->json('raw_ai_response')->nullable()->after('revision_priorities');
            $table->index(['document_id', 'created_at']);
        });

        Schema::table('analysis_aspect_scores', function (Blueprint $table) {
            $table->foreignId('analysis_result_id')->after('id')->constrained()->cascadeOnDelete();
            $table->string('aspect_name')->after('analysis_result_id');
            $table->unsignedInteger('score')->default(0)->after('aspect_name');
            $table->string('status')->nullable()->after('score');
            $table->text('finding')->nullable()->after('status');
            $table->text('recommendation')->nullable()->after('finding');
        });
    }

    public function down(): void
    {
        Schema::table('analysis_aspect_scores', function (Blueprint $table) {
            $table->dropConstrainedForeignId('analysis_result_id');
            $table->dropColumn(['aspect_name', 'score', 'status', 'finding', 'recommendation']);
        });

        Schema::table('analysis_results', function (Blueprint $table) {
            $table->dropIndex(['document_id', 'created_at']);
            $table->dropConstrainedForeignId('document_version_id');
            $table->dropConstrainedForeignId('document_id');
            $table->dropColumn([
                'total_score',
                'status',
                'summary',
                'main_issues',
                'recommendations',
                'revision_priorities',
                'raw_ai_response',
            ]);
        });

        Schema::table('rubrics', function (Blueprint $table) {
            $table->dropUnique(['document_type_id', 'aspect_name']);
            $table->dropConstrainedForeignId('document_type_id');
            $table->dropColumn(['aspect_name', 'weight', 'description', 'is_active']);
        });
    }
};
