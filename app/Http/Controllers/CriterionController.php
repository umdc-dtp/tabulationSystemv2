<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCriterionRequest;
use App\Models\Category;
use App\Models\Competition;
use App\Models\Criterion;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;

final class CriterionController extends Controller
{
    public function store(
        StoreCriterionRequest $request,
        Event $event,
        Category $category,
        Competition $competition,
    ): RedirectResponse {
        $competition->criteria()->create([
            'name' => $request->validated('criterion_name'),
            'max_score' => $request->validated('max_score'),
        ]);

        return to_route('events.categories.competitions.show', [
            $event,
            $category,
            $competition,
        ])->with('status', 'Criterion added successfully.');
    }

    public function destroy(
        Event $event,
        Category $category,
        Competition $competition,
        Criterion $criterion,
    ): RedirectResponse {
        abort_unless(
            $competition->usesCriteriaScoring(),
            403,
            'Criteria can only be managed for criteria-based contests.',
        );

        $criterion->delete();

        return to_route('events.categories.competitions.show', [
            $event,
            $category,
            $competition,
        ])->with('status', 'Criterion deleted successfully.');
    }
}
