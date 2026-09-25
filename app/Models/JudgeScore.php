<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class JudgeScore extends Model
{
    protected $fillable = ['criterion_id', 'judge_number', 'score'];

    protected function casts(): array
    {
        return ['score' => 'decimal:2', 'judge_number' => 'integer'];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(CompetitionEntry::class, 'competition_entry_id');
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(Criterion::class);
    }
}
