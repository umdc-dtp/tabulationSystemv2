<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ResetCompetitionScoresRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true
            && $this->route('event') instanceof Event;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'reset_scope' => ['required', Rule::in(['selected', 'all'])],
            'competition_ids' => ['exclude_unless:reset_scope,selected', 'required_if:reset_scope,selected', 'array', 'min:1'],
            'competition_ids.*' => ['integer', 'distinct', 'exists:competitions,id'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'competition_ids.required_if' => 'Select at least one contest to reset.',
            'competition_ids.min' => 'Select at least one contest to reset.',
        ];
    }
}
