<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Category extends Model
{
    protected $fillable = ['name'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function competitions(): HasMany
    {
        return $this->hasMany(Competition::class);
    }
}
