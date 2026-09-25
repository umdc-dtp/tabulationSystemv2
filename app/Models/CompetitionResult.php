<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CompetitionResult extends Model
{
    protected $fillable = ['participant_id', 'has_entry', 'wins', 'deduction'];

    protected function casts(): array
    {
        return [
            'has_entry' => 'boolean',
            'wins' => 'integer',
            'deduction' => 'decimal:2',
        ];
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function criterionScores(): HasMany
    {
        return $this->hasMany(CriterionScore::class);
    }
}
