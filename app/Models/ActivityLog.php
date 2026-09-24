<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ActivityLog extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'actor_name',
        'actor_username',
        'interaction_type',
        'action',
        'description',
        'method',
        'route_name',
        'path',
        'status_code',
        'ip_address',
        'user_agent',
        'duration_ms',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'duration_ms' => 'integer',
            'context' => 'array',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return list<array{operation: string, type: string, id: string, label: ?string}> */
    public function affectedRecords(): array
    {
        $context = is_array($this->context) ? $this->context : [];
        $records = $context['records'] ?? [];

        if (is_array($records) && $records !== []) {
            $normalized = [];

            foreach ($records as $record) {
                if (! is_array($record) || ! isset($record['type'], $record['id'])) {
                    continue;
                }

                $normalized[] = [
                    'operation' => isset($record['operation'])
                        ? (string) $record['operation']
                        : 'related',
                    'type' => (string) $record['type'],
                    'id' => (string) $record['id'],
                    'label' => isset($record['label']) ? (string) $record['label'] : null,
                ];
            }

            return $normalized;
        }

        $resources = $context['resources'] ?? [];

        if (! is_array($resources)) {
            return [];
        }

        $fallback = [];

        foreach ($resources as $resource) {
            if (! is_array($resource) || ! isset($resource['type'], $resource['id'])) {
                continue;
            }

            $fallback[] = [
                'operation' => 'related',
                'type' => (string) $resource['type'],
                'id' => (string) $resource['id'],
                'label' => isset($resource['label']) ? (string) $resource['label'] : null,
            ];
        }

        return $fallback;
    }

    public function affectedRecordSummary(): string
    {
        $summaries = [];

        foreach ($this->affectedRecords() as $record) {
            $summary = sprintf(
                '%s %s #%s',
                ucfirst($record['operation']),
                $record['type'],
                $record['id'],
            );

            if ($record['label'] !== null && $record['label'] !== '') {
                $summary .= ' - '.$record['label'];
            }

            $summaries[] = $summary;
        }

        return implode('; ', $summaries);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if ($search === null || $search === '') {
            return $query;
        }

        return $query->where(function (Builder $query) use ($search): void {
            $query->whereLike('actor_name', "%{$search}%")
                ->orWhereLike('actor_username', "%{$search}%")
                ->orWhereLike('description', "%{$search}%")
                ->orWhereLike('action', "%{$search}%")
                ->orWhereLike('route_name', "%{$search}%")
                ->orWhereLike('method', "%{$search}%")
                ->orWhereLike('path', "%{$search}%")
                ->orWhereLike('ip_address', "%{$search}%");

            if (ctype_digit($search)) {
                $query->orWhere('status_code', (int) $search);
            }
        });
    }
}
