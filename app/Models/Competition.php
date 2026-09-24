<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CompetitionScoringMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Competition extends Model
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'scoring_method' => 'criteria',
    ];

    protected $fillable = [
        'name',
        'scoring_method',
        'non_podium_points',
        'scores_finalized_at',
    ];

    protected function casts(): array
    {
        return [
            'scoring_method' => CompetitionScoringMethod::class,
            'non_podium_points' => 'decimal:2',
            'scores_finalized_at' => 'immutable_datetime',
        ];
    }

    public function usesCriteriaScoring(): bool
    {
        return $this->scoring_method === CompetitionScoringMethod::Criteria;
    }

    public function scoresAreFinalized(): bool
    {
        return $this->scores_finalized_at !== null;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(Criterion::class);
    }

    public function rankScores(): HasMany
    {
        return $this->hasMany(RankScore::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(CompetitionResult::class);
    }
}
