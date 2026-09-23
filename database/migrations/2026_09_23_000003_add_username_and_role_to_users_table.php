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
        $addedUsername = ! Schema::hasColumn('users', 'username');

        Schema::table('users', function (Blueprint $table) use ($addedUsername): void {
            if ($addedUsername) {
                $table->string('username')->nullable()->after('name');
            }

            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('user')->after('username');
            }
        });

        if (! $addedUsername) {
            return;
        }

        DB::table('users')
            ->select('id')
            ->orderBy('id')
            ->eachById(function (object $user): void {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['username' => 'user_'.$user->id]);
            });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('username')->nullable(false)->change();
            $table->unique('username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'username')) {
                $table->dropUnique(['username']);
                $table->dropColumn('username');
            }

            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
        });
    }
};
