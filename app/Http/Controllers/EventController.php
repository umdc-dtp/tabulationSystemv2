<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ScoringSystem;
use App\Http\Requests\StoreEventRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

final class EventController extends Controller
{
    public function index(): View
    {
        $events = Event::query()
            ->withCount(['participants', 'categories'])
            ->orderByDesc('start_date')
            ->get();

        return view('events.index', [
            'events' => $events,
            'scoringSystems' => ScoringSystem::cases(),
        ]);
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $scoringSystem = ScoringSystem::from($data['scoring_system']);

        $event = Event::query()->create([
            'creator_id' => $request->user()->getKey(),
            'name' => $data['event_name'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'scoring_system' => $scoringSystem,
            'other_scoring_system' => $scoringSystem === ScoringSystem::Other
                ? $data['other_scoring_system']
                : null,
        ]);

        return to_route('events.show', $event)
            ->with('status', 'Event created successfully.');
    }

    public function show(Event $event): View
    {
        $event->load([
            'participants' => fn ($query) => $query->orderBy('name'),
            'categories' => fn ($query) => $query->orderBy('name'),
            'categories.competitions' => fn ($query) => $query
                ->withCount(['criteria', 'results'])
                ->orderBy('name'),
        ]);

        return view('events.show', ['event' => $event]);
    }

    public function destroy(Event $event): RedirectResponse
    {
        $eventId = $event->getKey();

        $event->delete();

        Storage::disk('public')->deleteDirectory("participants/{$eventId}");

        return to_route('events.index')
            ->with('status', 'Event deleted successfully.');
    }

    public function toggleLeaderboardFreeze(Request $request, Event $event): RedirectResponse
    {
        $event->update([
            'leaderboard_frozen' => ! $event->leaderboard_frozen,
        ]);

        $message = $event->leaderboard_frozen
            ? 'The public leaderboard has been frozen.'
            : 'The public leaderboard is live again.';

        $route = $request->string('redirect_to')->toString() === 'dashboard'
            ? 'dashboard'
            : 'events.show';

        return $route === 'dashboard'
            ? to_route($route)->with('status', $message)
            : to_route($route, $event)->with('status', $message);
    }
}
