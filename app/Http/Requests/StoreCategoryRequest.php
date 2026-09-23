<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $event = $this->route('event');

        return [
            'category_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')
                    ->where(fn (Builder $query): Builder => $query->where(
                        'event_id',
                        $event instanceof Event ? $event->getKey() : null,
                    )),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'category_name.unique' => 'This category already exists in the event.',
        ];
    }
}
