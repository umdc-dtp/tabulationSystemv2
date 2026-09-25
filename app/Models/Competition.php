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

    protected $fillable = ['name', 'scoring_method', 'non_podium_points', 'judge_count', 'results_open', 'finalized_at'];

    protected function casts(): array
    {
        return [
            'scoring_method' => CompetitionScoringMethod::class,
            'non_podium_points' => 'decimal:2',
            'judge_count' => 'integer',
            'results_open' => 'boolean',
            'finalized_at' => 'datetime',
        ];
    }

    public function usesCriteriaScoring(): bool
    {
        return $this->scoring_method === CompetitionScoringMethod::Criteria;
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

    public function entries(): HasMany
    {
        return $this->hasMany(CompetitionEntry::class);
    }

    public function finalizedResults(): HasMany
    {
        return $this->hasMany(FinalizedResult::class);
    }
}
