<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RankScore extends Model
{
    protected $fillable = ['rank', 'points'];

    protected function casts(): array
    {
        return [
            'rank' => 'integer',
            'points' => 'decimal:2',
        ];
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }
}
