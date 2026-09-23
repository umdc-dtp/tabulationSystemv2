<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table): void {
            $table->string('profile_picture_path')->nullable()->after('reference_no');
        });

        Schema::create('criteria', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('max_score', 8, 2);
            $table->timestamps();

            $table->unique(['competition_id', 'name']);
        });

        Schema::create('rank_scores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('rank');
            $table->decimal('points', 8, 2);
            $table->timestamps();

            $table->unique(['competition_id', 'rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rank_scores');
        Schema::dropIfExists('criteria');

        Schema::table('participants', function (Blueprint $table): void {
            $table->dropColumn('profile_picture_path');
        });
    }
};
