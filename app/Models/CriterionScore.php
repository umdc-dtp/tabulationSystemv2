<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CriterionScore extends Model
{
    protected $fillable = ['criterion_id', 'score'];

    protected function casts(): array
    {
        return ['score' => 'decimal:2'];
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(CompetitionResult::class, 'competition_result_id');
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(Criterion::class);
    }
}
