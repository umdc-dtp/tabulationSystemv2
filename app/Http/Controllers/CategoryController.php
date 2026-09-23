<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;

final class CategoryController extends Controller
{
    public function store(StoreCategoryRequest $request, Event $event): RedirectResponse
    {
        $event->categories()->create([
            'name' => $request->validated('category_name'),
        ]);

        return to_route('events.show', $event)
            ->with('status', 'Category added successfully.');
    }
}
