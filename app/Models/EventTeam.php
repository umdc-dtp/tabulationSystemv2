<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\EventTeamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class EventTeam extends Model
{
    /** @use HasFactory<EventTeamFactory> */
    use HasFactory;

    protected $fillable = ['department_id', 'name', 'member_names'];

    protected function casts(): array
    {
        return ['member_names' => 'array'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(CompetitionEntry::class);
    }
}
