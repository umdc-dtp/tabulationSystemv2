<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\CompetitionScoringMethod;
use App\Models\Category;
use App\Models\Competition;
use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateCompetitionScoringMethodRequest extends FormRequest
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
            && (string) $competition->category_id === (string) $category->getKey();
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'scoring_method' => ['required', Rule::enum(CompetitionScoringMethod::class)],
        ];
    }
}
