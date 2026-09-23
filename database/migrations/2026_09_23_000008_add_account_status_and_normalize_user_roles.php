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
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->after('role');
        });

        DB::table('users')
            ->whereNotIn('role', ['admin', 'auditor'])
            ->update(['role' => 'auditor']);

        if (! DB::table('users')->where('role', 'admin')->exists()) {
            $firstUserId = DB::table('users')->orderBy('id')->value('id');

            if ($firstUserId !== null) {
                DB::table('users')
                    ->where('id', $firstUserId)
                    ->update(['role' => 'admin']);
            }
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('auditor')->change();
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('users')
            ->whereNull('email')
            ->orderBy('id')
            ->eachById(function (object $user): void {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['email' => 'user_'.$user->id.'@local.invalid']);
            });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('user')->change();
            $table->string('email')->nullable(false)->change();
            $table->dropColumn('is_active');
        });
    }
};
