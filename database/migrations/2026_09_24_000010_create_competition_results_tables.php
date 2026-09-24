<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('wins')->nullable();
            $table->timestamps();

            $table->unique(['competition_id', 'participant_id']);
        });

        Schema::create('criterion_scores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('competition_result_id')->constrained()->cascadeOnDelete();
            $table->foreignId('criterion_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 8, 2);
            $table->timestamps();

            $table->unique(['competition_result_id', 'criterion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('criterion_scores');
        Schema::dropIfExists('competition_results');
    }
};
