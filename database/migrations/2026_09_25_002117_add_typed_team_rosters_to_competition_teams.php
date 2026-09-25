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
        Schema::table('competition_teams', function (Blueprint $table): void {
            $table->json('member_names')->nullable();
        });

        DB::table('competition_teams')->orderBy('id')->chunkById(100, function ($teams): void {
            foreach ($teams as $team) {
                $names = DB::table('competition_team_members')
                    ->join('participants', 'participants.id', '=', 'competition_team_members.participant_id')
                    ->where('competition_team_members.competition_team_id', $team->id)
                    ->orderBy('competition_team_members.id')
                    ->pluck('participants.name')
                    ->all();

                if ($names === []) {
                    $snapshot = DB::table('finalized_results')
                        ->where('competition_id', $team->competition_id)
                        ->where('department_id', $team->department_id)
                        ->where('entrant_name', $team->name)
                        ->latest('id')
                        ->value('member_names');
                    $names = is_string($snapshot) ? (json_decode($snapshot, true) ?: []) : [];
                }

                DB::table('competition_teams')->where('id', $team->id)
                    ->update(['member_names' => json_encode($names, JSON_THROW_ON_ERROR)]);
            }
        });

        $emptyGames = DB::table('competitions')
            ->whereNotIn('id', DB::table('competition_entries')->select('competition_id'))
            ->pluck('id');

        DB::table('finalized_results')->whereIn('competition_id', $emptyGames)->delete();
        DB::table('competitions')->whereIn('id', $emptyGames)
            ->whereNotNull('finalized_at')
            ->update(['finalized_at' => null, 'results_open' => true]);
    }

    public function down(): void
    {
        Schema::table('competition_teams', function (Blueprint $table): void {
            $table->dropColumn('member_names');
        });
    }
};
