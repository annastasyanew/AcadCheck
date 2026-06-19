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
        Schema::table('journals', function (Blueprint $table) {
            $table->unsignedTinyInteger('eligibility_score')->default(0)->after('verification_status');
            $table->index('eligibility_score');
        });

        $invalidValues = ['nan', 'null', 'undefined', '-', '#'];
        $hasMeaningfulValue = fn ($value): bool => ($normalizedValue = strtolower(trim((string) ($value ?? '')))) !== ''
            && ! in_array($normalizedValue, $invalidValues, true);
        $hasValidUrl = fn ($value): bool => $hasMeaningfulValue($value)
            && filter_var(trim((string) $value), FILTER_VALIDATE_URL) !== false
            && preg_match('/^https?:\/\//i', trim((string) $value)) === 1;
        $calculateScore = function ($journal) use ($hasMeaningfulValue, $hasValidUrl): int {
            $score = 0;

            if ($hasMeaningfulValue($journal->sinta_level)) $score += 10;
            if ($hasMeaningfulValue($journal->subject_area)) $score += 15;
            if ($hasMeaningfulValue($journal->keywords)) $score += 20;
            if ($hasMeaningfulValue($journal->focus_scope)) $score += 30;
            if ($hasValidUrl($journal->website_url)) $score += 10;
            if ($hasValidUrl($journal->template_url)) $score += 10;
            if ($hasValidUrl($journal->author_guideline_url)) $score += 5;

            return min($score, 100);
        };

        DB::table('journals')
            ->select([
                'id',
                'sinta_level',
                'subject_area',
                'keywords',
                'focus_scope',
                'website_url',
                'template_url',
                'author_guideline_url',
            ])
            ->orderBy('id')
            ->chunkById(100, function ($journals) use ($calculateScore): void {
                foreach ($journals as $journal) {
                    DB::table('journals')
                        ->where('id', $journal->id)
                        ->update(['eligibility_score' => $calculateScore($journal)]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->dropIndex(['eligibility_score']);
            $table->dropColumn('eligibility_score');
        });
    }
};
