<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor_name')->nullable();
            $table->string('actor_username')->nullable();
            $table->string('interaction_type', 32);
            $table->string('action');
            $table->string('description');
            $table->string('method', 10);
            $table->string('route_name')->nullable();
            $table->text('path');
            $table->unsignedSmallInteger('status_code');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->unsignedInteger('duration_ms');
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
            $table->index(['interaction_type', 'created_at']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
