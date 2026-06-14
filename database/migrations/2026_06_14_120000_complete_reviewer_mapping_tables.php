<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviewer_comments', function (Blueprint $table) {
            $table->foreignId('document_id')->after('id')->constrained()->cascadeOnDelete();
            $table->string('reviewer_label', 100)->after('document_id');
            $table->unsignedInteger('comment_number')->nullable()->after('reviewer_label');
            $table->text('original_comment')->after('comment_number');
            $table->string('related_section', 100)->nullable()->after('original_comment');
            $table->enum('priority', ['minor', 'major', 'critical'])->default('major')->after('related_section');
            $table->enum('status', [
                'pending',
                'in_progress',
                'done',
                'rejected_with_reason',
            ])->default('pending')->after('priority');
            $table->index(
                ['document_id', 'reviewer_label', 'comment_number'],
                'reviewer_comments_document_order_idx',
            );
        });

        Schema::table('reviewer_responses', function (Blueprint $table) {
            $table->foreignId('reviewer_comment_id')
                ->after('id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();
            $table->text('author_response')->nullable()->after('reviewer_comment_id');
            $table->text('revision_made')->nullable()->after('author_response');
            $table->string('revision_location')->nullable()->after('revision_made');
            $table->foreignId('revised_version_id')
                ->nullable()
                ->after('revision_location')
                ->constrained('document_versions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reviewer_responses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('revised_version_id');
            $table->dropConstrainedForeignId('reviewer_comment_id');
            $table->dropColumn(['author_response', 'revision_made', 'revision_location']);
        });

        Schema::table('reviewer_comments', function (Blueprint $table) {
            $table->dropIndex('reviewer_comments_document_order_idx');
            $table->dropConstrainedForeignId('document_id');
            $table->dropColumn([
                'reviewer_label',
                'comment_number',
                'original_comment',
                'related_section',
                'priority',
                'status',
            ]);
        });
    }
};
