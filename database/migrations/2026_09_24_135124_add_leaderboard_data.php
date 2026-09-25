<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->unique(['event_id', 'name']);
        });

        Schema::table('participants', function (Blueprint $table): void {
            $table->foreignId('department_id')->nullable()->after('event_id')->constrained()->nullOnDelete();
        });

        Schema::table('competitions', function (Blueprint $table): void {
            $table->string('entrant_type')->default('individual');
            $table->unsignedSmallInteger('judge_count')->default(1);
            $table->boolean('results_open')->default(true);
            $table->timestamp('finalized_at')->nullable();
        });

        Schema::create('competition_teams', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->unique(['competition_id', 'department_id']);
        });

        Schema::create('competition_team_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('competition_team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->unique(['competition_team_id', 'participant_id'], 'team_member_unique');
        });

        Schema::create('competition_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('participant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('competition_team_id')->nullable()->constrained()->cascadeOnDelete();
            $table->boolean('competed')->default(false);
            $table->unsignedInteger('win_total')->nullable();
            $table->timestamps();
            $table->unique(['competition_id', 'participant_id']);
            $table->unique(['competition_id', 'competition_team_id'], 'entry_team_unique');
        });

        Schema::create('judge_scores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('competition_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('criterion_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('judge_number');
            $table->decimal('score', 8, 2);
            $table->timestamps();
            $table->unique(['competition_entry_id', 'criterion_id', 'judge_number'], 'judge_score_unique');
        });

        Schema::create('finalized_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('entrant_name');
            $table->json('member_names')->nullable();
            $table->unsignedInteger('rank');
            $table->decimal('result_value', 10, 2);
            $table->decimal('points', 10, 2);
            $table->timestamps();
            $table->index(['competition_id', 'rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finalized_results');
        Schema::dropIfExists('judge_scores');
        Schema::dropIfExists('competition_entries');
        Schema::dropIfExists('competition_team_members');
        Schema::dropIfExists('competition_teams');

        Schema::table('competitions', function (Blueprint $table): void {
            $table->dropColumn(['entrant_type', 'judge_count', 'results_open', 'finalized_at']);
        });

        Schema::table('participants', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('department_id');
        });

        Schema::dropIfExists('departments');
    }
};
