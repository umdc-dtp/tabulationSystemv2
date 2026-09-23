@extends('layouts.admin')

@section('title', 'Users')
@section('heading', 'User management')

@section('content')
    <section class="rounded-2xl bg-gradient-to-br from-maroon-700 to-maroon-950 p-7 text-white shadow-xl shadow-maroon-950/10 sm:p-9">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-maroon-200">Account administration</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight">Users</h2>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-maroon-100/80">Create and maintain administrator and auditor accounts. Deactivated accounts cannot sign in.</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <span class="rounded-xl bg-white/10 px-4 py-2 text-sm font-semibold ring-1 ring-inset ring-white/15">{{ $users->where('is_active', true)->count() }} active</span>
                <span class="rounded-xl bg-white/10 px-4 py-2 text-sm font-semibold ring-1 ring-inset ring-white/15">{{ $users->count() }} total</span>
                <button type="button" data-dialog-target="add-user-dialog" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-white px-5 text-sm font-semibold text-maroon-800 transition hover:bg-maroon-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-white/30">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M12 5v14M5 12h14" stroke-linecap="round" />
                    </svg>
                    Add user
                </button>
            </div>
        </div>
    </section>

    @if ($errors->any())
        <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
            <p class="font-semibold">Please correct the highlighted account form.</p>
            <p class="mt-1">{{ $errors->first() }}</p>
        </div>
    @endif

    <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-slate-950">User accounts</h3>
                <p class="mt-1 text-sm text-slate-500">Edit account information or change account access.</p>
            </div>
            <button type="button" data-dialog-target="add-user-dialog" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-maroon-700 px-4 text-sm font-semibold text-white transition hover:bg-maroon-800">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" stroke-linecap="round" />
                </svg>
                Add user
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[960px] text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-6 py-3.5 font-semibold">User</th>
                        <th class="px-5 py-3.5 font-semibold">Username</th>
                        <th class="px-5 py-3.5 font-semibold">Role</th>
                        <th class="px-5 py-3.5 font-semibold">Status</th>
                        <th class="px-5 py-3.5 font-semibold">Added</th>
                        <th class="px-5 py-3.5 font-semibold">Updated</th>
                        <th class="px-6 py-3.5 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $account)
                        <tr @class(['transition hover:bg-slate-50/80', 'bg-slate-50/40' => ! $account->is_active])>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span @class([
                                        'grid h-10 w-10 shrink-0 place-items-center rounded-xl text-sm font-bold',
                                        'bg-maroon-50 text-maroon-700' => $account->is_active,
                                        'bg-slate-100 text-slate-400' => ! $account->is_active,
                                    ])>
                                        {{ strtoupper(substr($account->name, 0, 1)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="max-w-52 truncate font-semibold text-slate-900">{{ $account->name }}</span>
                                            @if ($account->is(auth()->user()))
                                                <span class="rounded-full bg-maroon-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-maroon-700">You</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-slate-600">&#64;{{ $account->username }}</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $account->role->label() }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <span @class([
                                    'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold',
                                    'bg-emerald-50 text-emerald-700' => $account->is_active,
                                    'bg-slate-100 text-slate-500' => ! $account->is_active,
                                ])>
                                    <span @class([
                                        'h-1.5 w-1.5 rounded-full',
                                        'bg-emerald-500' => $account->is_active,
                                        'bg-slate-400' => ! $account->is_active,
                                    ])></span>
                                    {{ $account->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-500">
                                <span class="block font-medium text-slate-700">{{ $account->created_at->format('M j, Y') }}</span>
                                <span class="mt-0.5 block">{{ $account->created_at->format('g:i A') }}</span>
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-500">
                                <span class="block font-medium text-slate-700">{{ $account->updated_at->format('M j, Y') }}</span>
                                <span class="mt-0.5 block">{{ $account->updated_at->diffForHumans() }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" data-dialog-target="edit-user-dialog-{{ $account->id }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-maroon-200 hover:bg-maroon-50 hover:text-maroon-700">
                                        Edit
                                    </button>

                                    <form method="POST" action="{{ route('users.toggle-status', $account) }}" onsubmit="return confirm('{{ $account->is_active ? 'Deactivate' : 'Activate' }} this account?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" @disabled($account->is(auth()->user()) && $account->is_active) @class([
                                            'rounded-lg px-3 py-2 text-xs font-semibold transition',
                                            'bg-red-50 text-red-700 hover:bg-red-100' => $account->is_active && ! $account->is(auth()->user()),
                                            'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' => ! $account->is_active,
                                            'cursor-not-allowed bg-slate-100 text-slate-400' => $account->is(auth()->user()) && $account->is_active,
                                        ])>
                                            {{ $account->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-14 text-center text-sm text-slate-400">No user accounts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <dialog id="add-user-dialog" aria-labelledby="add-user-title" class="user-dialog rounded-2xl bg-white p-0 text-slate-900 shadow-2xl">
        <div class="max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-6 py-5">
                <div>
                    <h3 id="add-user-title" class="text-xl font-semibold text-slate-950">Add user</h3>
                    <p class="mt-1 text-sm text-slate-500">Create an Admin or Auditor account.</p>
                </div>
                <button type="button" data-dialog-close class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close add user dialog">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M6 6l12 12M18 6 6 18" stroke-linecap="round" />
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('users.store') }}" class="space-y-4 p-6">
                @csrf
                <input type="hidden" name="form_context" value="create_user">

                @if (old('form_context') === 'create_user' && $errors->any())
                    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                        <ul class="list-inside list-disc">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="add_name" class="mb-1.5 block text-sm font-semibold text-slate-700">Full name</label>
                        <input id="add_name" name="name" type="text" value="{{ old('form_context') === 'create_user' ? old('name') : '' }}" required autocomplete="name" class="h-11 w-full rounded-xl border border-slate-300 px-4 text-sm outline-none transition focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="add_username" class="mb-1.5 block text-sm font-semibold text-slate-700">Username</label>
                        <input id="add_username" name="username" type="text" value="{{ old('form_context') === 'create_user' ? old('username') : '' }}" required autocomplete="off" class="h-11 w-full rounded-xl border border-slate-300 px-4 text-sm outline-none transition focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10">
                    </div>
                    <div>
                        <label for="add_role" class="mb-1.5 block text-sm font-semibold text-slate-700">Role</label>
                        <select id="add_role" name="role" required class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none transition focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10">
                            @foreach (\App\Enums\AccountRole::cases() as $role)
                                <option value="{{ $role->value }}" @selected(old('form_context') === 'create_user' ? old('role', \App\Enums\AccountRole::Auditor->value) === $role->value : $role === \App\Enums\AccountRole::Auditor)>{{ $role->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="add_is_active" class="mb-1.5 block text-sm font-semibold text-slate-700">Status</label>
                        <select id="add_is_active" name="is_active" required class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none transition focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10">
                            <option value="1" @selected(old('form_context') !== 'create_user' || old('is_active', '1') === '1')>Active</option>
                            <option value="0" @selected(old('form_context') === 'create_user' && old('is_active') === '0')>Inactive</option>
                        </select>
                    </div>
                    <div>
                        <label for="add_password" class="mb-1.5 block text-sm font-semibold text-slate-700">Password</label>
                        <input id="add_password" name="password" type="password" required autocomplete="new-password" class="h-11 w-full rounded-xl border border-slate-300 px-4 text-sm outline-none transition focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10">
                        <p class="mt-1 text-xs text-slate-400">Use at least 8 characters.</p>
                    </div>
                    <div>
                        <label for="add_password_confirmation" class="mb-1.5 block text-sm font-semibold text-slate-700">Confirm password</label>
                        <input id="add_password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="h-11 w-full rounded-xl border border-slate-300 px-4 text-sm outline-none transition focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10">
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-slate-200 pt-5">
                    <button type="button" data-dialog-close class="h-11 rounded-xl border border-slate-300 px-5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="h-11 rounded-xl bg-maroon-700 px-5 text-sm font-semibold text-white transition hover:bg-maroon-800">Create user</button>
                </div>
            </form>
        </div>
    </dialog>

    @foreach ($users as $account)
        @php($editContext = 'edit_user_'.$account->id)
        @php($editingThisAccount = old('form_context') === $editContext)

        <dialog id="edit-user-dialog-{{ $account->id }}" aria-labelledby="edit-user-title-{{ $account->id }}" class="user-dialog rounded-2xl bg-white p-0 text-slate-900 shadow-2xl">
            <div class="max-h-[90vh] overflow-y-auto">
                <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-6 py-5">
                    <div>
                        <h3 id="edit-user-title-{{ $account->id }}" class="text-xl font-semibold text-slate-950">Edit user</h3>
                        <p class="mt-1 text-sm text-slate-500">Update {{ $account->name }}'s account information.</p>
                    </div>
                    <button type="button" data-dialog-close class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close edit user dialog">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M6 6l12 12M18 6 6 18" stroke-linecap="round" />
                        </svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('users.update', $account) }}" class="space-y-4 p-6">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="form_context" value="{{ $editContext }}">

                    @if ($editingThisAccount && $errors->any())
                        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                            <ul class="list-inside list-disc">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="edit_name_{{ $account->id }}" class="mb-1.5 block text-sm font-semibold text-slate-700">Full name</label>
                            <input id="edit_name_{{ $account->id }}" name="name" type="text" value="{{ $editingThisAccount ? old('name') : $account->name }}" required class="h-11 w-full rounded-xl border border-slate-300 px-4 text-sm outline-none transition focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10">
                        </div>
                        <div>
                            <label for="edit_username_{{ $account->id }}" class="mb-1.5 block text-sm font-semibold text-slate-700">Username</label>
                            <input id="edit_username_{{ $account->id }}" name="username" type="text" value="{{ $editingThisAccount ? old('username') : $account->username }}" required class="h-11 w-full rounded-xl border border-slate-300 px-4 text-sm outline-none transition focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="edit_role_{{ $account->id }}" class="mb-1.5 block text-sm font-semibold text-slate-700">Role</label>
                            <select id="edit_role_{{ $account->id }}" name="role" required class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none transition focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10">
                                @foreach (\App\Enums\AccountRole::cases() as $role)
                                    <option value="{{ $role->value }}" @selected(($editingThisAccount ? old('role') : $account->role->value) === $role->value) @disabled($account->is(auth()->user()) && $role !== \App\Enums\AccountRole::Admin)>{{ $role->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="edit_password_{{ $account->id }}" class="mb-1.5 block text-sm font-semibold text-slate-700">New password <span class="font-normal text-slate-400">(optional)</span></label>
                            <input id="edit_password_{{ $account->id }}" name="password" type="password" autocomplete="new-password" class="h-11 w-full rounded-xl border border-slate-300 px-4 text-sm outline-none transition focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10">
                        </div>
                        <div>
                            <label for="edit_password_confirmation_{{ $account->id }}" class="mb-1.5 block text-sm font-semibold text-slate-700">Confirm new password</label>
                            <input id="edit_password_confirmation_{{ $account->id }}" name="password_confirmation" type="password" autocomplete="new-password" class="h-11 w-full rounded-xl border border-slate-300 px-4 text-sm outline-none transition focus:border-maroon-600 focus:ring-4 focus:ring-maroon-600/10">
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-200 pt-5">
                        <button type="button" data-dialog-close class="h-11 rounded-xl border border-slate-300 px-5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="h-11 rounded-xl bg-maroon-700 px-5 text-sm font-semibold text-white transition hover:bg-maroon-800">Save changes</button>
                    </div>
                </form>
            </div>
        </dialog>
    @endforeach
@endsection

@push('scripts')
    <script>
        (() => {
            const openDialog = (id) => {
                const dialog = document.getElementById(id);

                if (dialog instanceof HTMLDialogElement && !dialog.open) {
                    dialog.showModal();
                }
            };

            document.querySelectorAll('[data-dialog-target]').forEach((button) => {
                button.addEventListener('click', () => openDialog(button.dataset.dialogTarget));
            });

            document.querySelectorAll('[data-dialog-close]').forEach((button) => {
                button.addEventListener('click', () => button.closest('dialog')?.close());
            });

            document.querySelectorAll('dialog').forEach((dialog) => {
                dialog.addEventListener('click', (event) => {
                    if (event.target === dialog) {
                        dialog.close();
                    }
                });
            });

            const formContext = @json(old('form_context'));

            if (formContext === 'create_user') {
                openDialog('add-user-dialog');
            } else if (formContext?.startsWith('edit_user_')) {
                openDialog(formContext.replace('edit_user_', 'edit-user-dialog-'));
            }
        })();
    </script>
@endpush
