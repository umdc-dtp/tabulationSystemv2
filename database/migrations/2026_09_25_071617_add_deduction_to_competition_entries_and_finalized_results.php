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
            $table->decimal('deduction', 8, 2)->default(0);
        });

        Schema::table('finalized_results', function (Blueprint $table): void {
            $table->decimal('deduction', 8, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('finalized_results', function (Blueprint $table): void {
            $table->dropColumn('deduction');
        });

        Schema::table('competition_entries', function (Blueprint $table): void {
            $table->dropColumn('deduction');
        });
    }
};
