<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\Event;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreCompetitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');
        $category = $this->route('category');

        return $event instanceof Event
            && $category instanceof Category
            && $category->event_id === $event->getKey();
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'competition_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('competitions', 'name')
                    ->where(fn (Builder $query): Builder => $query->where(
                        'category_id',
                        $category instanceof Category ? $category->getKey() : null,
                    )),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'competition_name.unique' => 'This competition already exists in the category.',
        ];
    }
}
