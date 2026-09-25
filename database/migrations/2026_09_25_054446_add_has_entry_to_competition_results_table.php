<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competition_results', function (Blueprint $table): void {
            $table->boolean('has_entry')->default(true)->after('participant_id');
        });
    }

    public function down(): void
    {
        Schema::table('competition_results', function (Blueprint $table): void {
            $table->dropColumn('has_entry');
        });
    }
};
