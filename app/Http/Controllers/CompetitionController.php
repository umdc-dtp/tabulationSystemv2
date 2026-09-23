<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompetitionRequest;
use App\Http\Requests\UpdateCompetitionScoringMethodRequest;
use App\Models\Category;
use App\Models\Competition;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class CompetitionController extends Controller
{
    public function store(
        StoreCompetitionRequest $request,
        Event $event,
        Category $category,
    ): RedirectResponse {
        $category->competitions()->create([
            'name' => $request->validated('competition_name'),
        ]);

        return to_route('events.show', $event)
            ->with('status', 'Contest added successfully.');
    }

    public function show(
        Event $event,
        Category $category,
        Competition $competition,
    ): View {
        $competition->load([
            'criteria' => fn ($query) => $query->orderBy('name'),
            'rankScores' => fn ($query) => $query->orderBy('rank'),
        ]);

        return view('events.competitions.show', [
            'event' => $event,
            'category' => $category,
            'competition' => $competition,
        ]);
    }

    public function updateScoringMethod(
        UpdateCompetitionScoringMethodRequest $request,
        Event $event,
        Category $category,
        Competition $competition,
    ): RedirectResponse {
        $competition->update([
            'scoring_method' => $request->validated('scoring_method'),
        ]);

        return to_route('events.categories.competitions.show', [
            $event,
            $category,
            $competition,
        ])->with('status', 'Contest scoring method updated successfully.');
    }
}
