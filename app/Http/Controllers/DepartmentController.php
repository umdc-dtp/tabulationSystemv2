<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class DepartmentController extends Controller
{
    public function store(Request $request, Event $event): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('departments')->where('event_id', $event->getKey())],
        ]);

        $event->departments()->create($data);

        return to_route('events.show', $event)->with('status', 'Department added successfully.');
    }

    public function update(Request $request, Event $event, Department $department): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('departments')->where('event_id', $event->getKey())->ignore($department)],
        ]);

        $department->update($data);

        return to_route('events.show', $event)->with('status', 'Department updated successfully.');
    }
}
