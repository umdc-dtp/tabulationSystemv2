<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competition_entries', function (Blueprint $table): void {
            $table->unsignedInteger('loss_total')->nullable()->after('win_total');
        });

        Schema::table('finalized_results', function (Blueprint $table): void {
            $table->unsignedInteger('loss_total')->nullable()->after('result_value');
        });
    }

    public function down(): void
    {
        Schema::table('finalized_results', function (Blueprint $table): void {
            $table->dropColumn('loss_total');
        });

        Schema::table('competition_entries', function (Blueprint $table): void {
            $table->dropColumn('loss_total');
        });
    }
};
