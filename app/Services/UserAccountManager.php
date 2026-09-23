<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AccountRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UserAccountManager
{
    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): User
    {
        return User::query()->create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function update(User $account, User $actor, array $attributes): void
    {
        DB::transaction(function () use ($account, $actor, $attributes): void {
            $lockedAccount = User::query()->lockForUpdate()->findOrFail($account->getKey());
            $newRole = AccountRole::from((string) $attributes['role']);

            if ($lockedAccount->is($actor) && $newRole !== AccountRole::Admin) {
                throw ValidationException::withMessages([
                    'role' => 'You cannot remove your own administrator role.',
                ]);
            }

            if (
                $lockedAccount->isAdmin()
                && $lockedAccount->is_active
                && $newRole !== AccountRole::Admin
                && $this->activeAdminCount() <= 1
            ) {
                throw ValidationException::withMessages([
                    'role' => 'At least one active administrator must remain.',
                ]);
            }

            if (blank($attributes['password'] ?? null)) {
                unset($attributes['password']);
            }

            $lockedAccount->update($attributes);
        });
    }

    public function toggleStatus(User $account, User $actor): bool
    {
        return DB::transaction(function () use ($account, $actor): bool {
            $lockedAccount = User::query()->lockForUpdate()->findOrFail($account->getKey());

            if ($lockedAccount->is($actor) && $lockedAccount->is_active) {
                throw ValidationException::withMessages([
                    'status' => 'You cannot deactivate your own account.',
                ]);
            }

            if (
                $lockedAccount->isAdmin()
                && $lockedAccount->is_active
                && $this->activeAdminCount() <= 1
            ) {
                throw ValidationException::withMessages([
                    'status' => 'The final active administrator cannot be deactivated.',
                ]);
            }

            $lockedAccount->update([
                'is_active' => ! $lockedAccount->is_active,
            ]);

            return $lockedAccount->is_active;
        });
    }

    private function activeAdminCount(): int
    {
        return User::query()
            ->where('role', AccountRole::Admin->value)
            ->where('is_active', true)
            ->count();
    }
}
