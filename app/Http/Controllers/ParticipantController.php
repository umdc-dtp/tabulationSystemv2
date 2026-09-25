<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreParticipantRequest;
use App\Http\Requests\UpdateParticipantRequest;
use App\Models\Competition;
use App\Models\CompetitionEntry;
use App\Models\Event;
use App\Models\Participant;
use App\Services\CompetitionResultCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final class ParticipantController extends Controller
{
    public function store(StoreParticipantRequest $request, Event $event): RedirectResponse
    {
        $profilePicturePath = null;

        if ($request->hasFile('participant_image')) {
            $profilePicturePath = $request->file('participant_image')
                ?->store("participants/{$event->getKey()}", 'public');

            if ($profilePicturePath === false) {
                throw new RuntimeException('The participant profile picture could not be stored.');
            }
        }

        try {
            $event->participants()->create([
                'name' => $request->validated('participant_name'),
                'reference_no' => $request->validated('participant_reference'),
                'profile_picture_path' => $profilePicturePath,
                'department_id' => $request->validated('department_id'),
            ]);
        } catch (Throwable $exception) {
            if (is_string($profilePicturePath)) {
                Storage::disk('public')->delete($profilePicturePath);
            }

            throw $exception;
        }

        return to_route('events.show', $event)
            ->with('status', 'Participant added successfully.');
    }

    public function update(
        UpdateParticipantRequest $request,
        Event $event,
        Participant $participant,
        CompetitionResultCalculator $calculator,
    ): RedirectResponse {
        $newDepartmentId = $request->validated('department_id');

        $oldProfilePicturePath = $participant->profile_picture_path;
        $newProfilePicturePath = null;

        if ($request->hasFile('participant_image')) {
            $newProfilePicturePath = $request->file('participant_image')
                ?->store("participants/{$event->getKey()}", 'public');

            if ($newProfilePicturePath === false) {
                throw new RuntimeException('The participant profile picture could not be stored.');
            }
        }

        $profilePicturePath = $newProfilePicturePath
            ?? ($request->boolean('remove_profile_picture') ? null : $oldProfilePicturePath);

        $affectsResults = $participant->name !== $request->validated('participant_name')
            || (string) $participant->department_id !== (string) $newDepartmentId;

        try {
            DB::transaction(function () use ($participant, $request, $profilePicturePath, $affectsResults, $calculator): void {
                $competitionIds = $affectsResults
                    ? CompetitionEntry::query()->where('participant_id', $participant->getKey())->pluck('competition_id')
                    : collect();

                $participant->update([
                    'name' => $request->validated('participant_name'),
                    'reference_no' => $request->validated('participant_reference'),
                    'profile_picture_path' => $profilePicturePath,
                    'department_id' => $request->validated('department_id'),
                ]);

                Competition::query()->whereIn('id', $competitionIds)->get()
                    ->each(fn (Competition $competition) => $calculator->invalidate($competition));
            });
        } catch (Throwable $exception) {
            if (is_string($newProfilePicturePath)) {
                Storage::disk('public')->delete($newProfilePicturePath);
            }

            throw $exception;
        }

        if (
            is_string($oldProfilePicturePath)
            && $oldProfilePicturePath !== $profilePicturePath
        ) {
            Storage::disk('public')->delete($oldProfilePicturePath);
        }

        return to_route('events.show', $event)
            ->with('status', 'Participant updated successfully.');
    }

    public function destroy(Event $event, Participant $participant, CompetitionResultCalculator $calculator): RedirectResponse
    {
        $profilePicturePath = $participant->profile_picture_path;

        DB::transaction(function () use ($participant, $calculator): void {
            $competitionIds = CompetitionEntry::query()
                ->where('participant_id', $participant->getKey())
                ->pluck('competition_id');

            $participant->delete();

            Competition::query()->whereIn('id', $competitionIds)->get()
                ->each(fn (Competition $competition) => $calculator->invalidate($competition));
        });

        if (is_string($profilePicturePath)) {
            Storage::disk('public')->delete($profilePicturePath);
        }

        return to_route('events.show', $event)
            ->with('status', 'Participant deleted successfully.');
    }
}
