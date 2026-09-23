<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ScoringSystem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'event_name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'scoring_system' => ['required', Rule::enum(ScoringSystem::class)],
            'other_scoring_system' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf($this->string('scoring_system')->toString() === ScoringSystem::Other->value),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => 'The event end date must be on or after the start date.',
            'other_scoring_system.required' => 'Please describe the scoring system.',
        ];
    }
}
