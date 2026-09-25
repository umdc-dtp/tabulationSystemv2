<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\Competition;
use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateCompetitionDeductionsRequest extends FormRequest
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
        $competition = $this->route('competition');

        if (! $competition instanceof Competition) {
            return [];
        }

        $competition->loadMissing('results.criterionScores');
        $maximumDeductions = [];

        foreach ($competition->results as $result) {
            $baseScore = $competition->usesCriteriaScoring()
                ? (float) $result->criterionScores->sum(
                    static fn ($score): float => (float) $score->score,
                )
                : ($result->wins === null ? null : (float) $result->wins);

            if ($baseScore !== null) {
                $maximumDeductions[(string) $result->participant_id] = min(
                    $baseScore,
                    999999.99,
                );
            }
        }

        $participantIds = array_keys($maximumDeductions);

        if ($participantIds === []) {
            return [
                'deductions' => ['required', 'prohibited'],
            ];
        }

        $rules = [
            'deductions' => ['required', Rule::array($participantIds)],
        ];

        foreach ($maximumDeductions as $participantId => $maximum) {
            $rules["deductions.{$participantId}"] = [
                'nullable',
                'numeric',
                'min:0',
                'max:'.$maximum,
            ];
        }

        return $rules;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'deductions.prohibited' => 'Save at least one participant score before entering deductions.',
            'deductions.*.max' => 'A deduction cannot exceed the participant\'s saved base score.',
        ];
    }
}
