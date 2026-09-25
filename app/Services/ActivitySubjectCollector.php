<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

final class ActivitySubjectCollector
{
    private bool $active = false;

    /** @var array<string, array{operation: string, type: string, id: string, label: ?string}> */
    private array $records = [];

    public function begin(): void
    {
        $this->active = true;
        $this->records = [];
    }

    public function capture(string $operation, Model $model): void
    {
        if (! $this->active || $model instanceof ActivityLog || $model->getKey() === null) {
            return;
        }

        $record = [
            'operation' => $operation,
            'type' => class_basename($model),
            'id' => (string) $model->getKey(),
            'label' => $this->label($model),
        ];

        $key = implode(':', [$operation, $model::class, (string) $model->getKey()]);
        $this->records[$key] = $record;
    }

    /** @return list<array{operation: string, type: string, id: string, label: ?string}> */
    public function all(): array
    {
        return array_values($this->records);
    }

    public function reset(): void
    {
        $this->active = false;
        $this->records = [];
    }

    private function label(Model $model): ?string
    {
        foreach (['name', 'username', 'title'] as $attribute) {
            $value = $model->getAttribute($attribute);

            if (is_scalar($value) && (string) $value !== '') {
                return (string) $value;
            }
        }

        $rank = $model->getAttribute('rank');

        return is_scalar($rank) && (string) $rank !== ''
            ? 'Rank '.(string) $rank
            : null;
    }
}
