<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CompetitionEntry extends Model
{
    protected $fillable = ['participant_id', 'event_team_id', 'competed', 'win_total', 'loss_total', 'deduction'];

    protected function casts(): array
    {
        return ['competed' => 'boolean', 'win_total' => 'integer', 'loss_total' => 'integer', 'deduction' => 'decimal:2'];
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(EventTeam::class, 'event_team_id');
    }

    public function judgeScores(): HasMany
    {
        return $this->hasMany(JudgeScore::class);
    }
}
