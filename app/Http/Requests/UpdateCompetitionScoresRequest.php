<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\Competition;
use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateCompetitionScoresRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');
        $category = $this->route('category');
        $competition = $this->route('competition');

        return $event instanceof Event
            && $category instanceof Category
            && $competition instanceof Competition
            && (string) $category->event_id === (string) $event->getKey()
            && (string) $competition->category_id === (string) $category->getKey()
            && (! $competition->scoresAreFinalized() || $this->user()?->isAdmin() === true);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $event = $this->route('event');
        $competition = $this->route('competition');

        if (! $event instanceof Event || ! $competition instanceof Competition) {
            return [];
        }

        $participantIds = $event->participants()
            ->pluck('id')
            ->map(static fn (int|string $id): string => (string) $id)
            ->all();

        if (! $competition->usesCriteriaScoring()) {
            return [
                'entries' => ['sometimes', Rule::array($participantIds)],
                'entries.*' => ['boolean'],
                'wins' => ['required', Rule::array($participantIds)],
                'wins.*' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            ];
        }

        $criteria = $competition->criteria()->orderBy('id')->get();
        $criterionIds = $criteria
            ->pluck('id')
            ->map(static fn (int|string $id): string => (string) $id)
            ->all();

        $rules = [
            'entries' => ['sometimes', Rule::array($participantIds)],
            'entries.*' => ['boolean'],
            'scores' => ['required', Rule::array($participantIds)],
        ];

        foreach ($participantIds as $participantId) {
            $rules["scores.{$participantId}"] = ['nullable', Rule::array($criterionIds)];

            foreach ($criteria as $criterion) {
                $rules["scores.{$participantId}.{$criterion->getKey()}"] = [
                    'nullable',
                    'numeric',
                    'min:0',
                    'max:'.$criterion->max_score,
                ];
            }
        }

        return $rules;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'scores.*.*.max' => 'A score cannot exceed the criterion maximum.',
            'wins.*.integer' => 'Wins must be a whole number.',
        ];
    }
}
