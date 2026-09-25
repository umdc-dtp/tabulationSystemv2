<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexActivityLogRequest extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => [
                'nullable',
                Rule::in(['created_at', 'actor_name', 'action', 'method', 'status_code']),
            ],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }

    /** @return array{q: ?string, sort: string, direction: string} */
    public function filters(): array
    {
        $validated = $this->validated();
        $search = trim((string) ($validated['q'] ?? ''));

        return [
            'q' => $search === '' ? null : $search,
            'sort' => (string) ($validated['sort'] ?? 'created_at'),
            'direction' => (string) ($validated['direction'] ?? 'desc'),
        ];
    }
}
