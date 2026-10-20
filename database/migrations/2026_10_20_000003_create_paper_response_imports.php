<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paper_response_imports', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('period_id');
            $table->unsignedBigInteger('response_id')->unique();
            $table->char('code_hash', 64);
            $table->char('source_file_sha256', 64)->index();
            $table->date('submitted_date');
            $table->timestamp('imported_at');
            $table->unique(['period_id', 'code_hash'], 'paper_import_period_code_unique');
            $table->foreign('period_id')->references('id')->on('assessment_periods')->restrictOnDelete();
            $table->foreign('response_id')->references('id')->on('survey_responses')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Paper-import provenance is required to preserve historical response sources and duplicate protection. Restore a reviewed database backup to roll it back.');
    }
};
