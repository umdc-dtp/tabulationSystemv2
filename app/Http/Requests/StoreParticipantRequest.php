<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'participant_name' => ['required', 'string', 'max:255'],
            'participant_reference' => ['nullable', 'string', 'max:100'],
            'participant_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'department_id' => [
                Rule::requiredIf($this->route('event') instanceof Event && $this->route('event')->departments()->exists()),
                'nullable', 'integer',
                Rule::exists('departments', 'id')->where('event_id', $this->route('event') instanceof Event ? $this->route('event')->getKey() : null),
            ],
        ];
    }
}
