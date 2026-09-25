<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_teams', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('member_names');
            $table->timestamps();
        });

        Schema::table('competition_entries', function (Blueprint $table): void {
            $table->foreignId('event_team_id')->nullable()->constrained('event_teams')->cascadeOnDelete();
            $table->unique(['competition_id', 'event_team_id'], 'entry_event_team_unique');
        });

        $eventIds = DB::table('competitions')
            ->join('categories', 'categories.id', '=', 'competitions.category_id')
            ->pluck('categories.event_id', 'competitions.id');

        DB::table('competition_teams')->orderBy('id')->chunkById(100, function ($teams) use ($eventIds): void {
            foreach ($teams as $team) {
                $eventId = $eventIds->get($team->competition_id);

                if ($eventId === null) {
                    continue;
                }

                $eventTeamId = DB::table('event_teams')->insertGetId([
                    'event_id' => $eventId,
                    'department_id' => $team->department_id,
                    'name' => $team->name,
                    'member_names' => $team->member_names ?: '[]',
                    'created_at' => $team->created_at,
                    'updated_at' => $team->updated_at,
                ]);

                DB::table('competition_entries')
                    ->where('competition_team_id', $team->id)
                    ->update(['event_team_id' => $eventTeamId]);
            }
        });
    }

    public function down(): void
    {
        if (DB::table('event_teams')->exists()) {
            throw new RuntimeException('Cannot roll back event teams without deleting registered team data.');
        }

        Schema::table('competition_entries', function (Blueprint $table): void {
            $table->dropUnique('entry_event_team_unique');
            $table->dropConstrainedForeignId('event_team_id');
        });

        Schema::dropIfExists('event_teams');
    }
};
