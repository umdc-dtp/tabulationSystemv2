<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FinalizedResult extends Model
{
    protected $fillable = ['department_id', 'entrant_name', 'member_names', 'rank', 'result_value', 'loss_total', 'deduction', 'points'];

    protected function casts(): array
    {
        return ['member_names' => 'array', 'rank' => 'integer', 'result_value' => 'decimal:2', 'loss_total' => 'integer', 'deduction' => 'decimal:2', 'points' => 'decimal:2'];
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
