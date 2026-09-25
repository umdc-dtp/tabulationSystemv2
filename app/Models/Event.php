<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ScoringSystem;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

final class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    protected $fillable = [
        'creator_id',
        'name',
        'start_date',
        'end_date',
        'scoring_system',
        'other_scoring_system',
        'leaderboard_frozen',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'immutable_date',
            'end_date' => 'immutable_date',
            'scoring_system' => ScoringSystem::class,
            'leaderboard_frozen' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(EventTeam::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function competitions(): HasManyThrough
    {
        return $this->hasManyThrough(Competition::class, Category::class);
    }

    public function scoringSystemLabel(): string
    {
        if ($this->scoring_system === ScoringSystem::Other) {
            return $this->other_scoring_system ?: $this->scoring_system->label();
        }

        return $this->scoring_system->label();
    }
}
