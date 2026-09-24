<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Participant extends Model
{
    protected $fillable = ['name', 'reference_no', 'profile_picture_path'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function competitionResults(): HasMany
    {
        return $this->hasMany(CompetitionResult::class);
    }
}
