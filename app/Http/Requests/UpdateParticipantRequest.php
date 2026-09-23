<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Event;
use App\Models\Participant;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');
        $participant = $this->route('participant');

        return $event instanceof Event
            && $participant instanceof Participant
            && (string) $participant->event_id === (string) $event->getKey();
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'participant_name' => ['required', 'string', 'max:255'],
            'participant_reference' => ['nullable', 'string', 'max:100'],
            'participant_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_profile_picture' => ['nullable', 'boolean'],
        ];
    }
}
