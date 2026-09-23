<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\AccountRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $account = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore(
                    $account instanceof User ? $account->getKey() : null,
                ),
            ],
            'role' => ['required', Rule::enum(AccountRole::class)],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ];
    }
}
