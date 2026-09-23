<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserAccountManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class UserController extends Controller
{
    public function __construct(
        private readonly UserAccountManager $accounts,
    ) {}

    public function index(): View
    {
        return view('users.index', [
            'users' => User::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->accounts->create($request->validated());

        return to_route('users.index')
            ->with('status', 'User account created successfully.');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->accounts->update(
            $user,
            $request->user(),
            $request->validated(),
        );

        return to_route('users.index')
            ->with('status', 'User account updated successfully.');
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        $isActive = $this->accounts->toggleStatus($user, $request->user());

        return to_route('users.index')->with(
            'status',
            $isActive
                ? 'User account activated successfully.'
                : 'User account deactivated successfully.',
        );
    }
}
