<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AccountRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_an_auditor_account(): void
    {
        $admin = User::factory()->create(['role' => AccountRole::Admin]);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Audit Officer',
                'username' => 'auditor.one',
                'role' => AccountRole::Auditor->value,
                'password' => 'secure-password',
                'password_confirmation' => 'secure-password',
                'is_active' => true,
            ])
            ->assertRedirect(route('users.index'));

        $account = User::query()->where('username', 'auditor.one')->sole();

        $this->assertSame(AccountRole::Auditor, $account->role);
        $this->assertTrue($account->is_active);
        $this->assertTrue(Hash::check('secure-password', $account->password));
    }

    public function test_admin_can_edit_an_account_and_change_its_password(): void
    {
        $admin = User::factory()->create(['role' => AccountRole::Admin]);
        $account = User::factory()->create(['role' => AccountRole::Auditor]);

        $this->actingAs($admin)
            ->patch(route('users.update', $account), [
                'name' => 'Updated Auditor',
                'username' => 'updated.auditor',
                'role' => AccountRole::Admin->value,
                'password' => 'updated-password',
                'password_confirmation' => 'updated-password',
            ])
            ->assertRedirect(route('users.index'));

        $account->refresh();

        $this->assertSame('Updated Auditor', $account->name);
        $this->assertSame('updated.auditor', $account->username);
        $this->assertSame(AccountRole::Admin, $account->role);
        $this->assertTrue(Hash::check('updated-password', $account->password));
    }

    public function test_admin_can_deactivate_and_reactivate_an_account(): void
    {
        $admin = User::factory()->create(['role' => AccountRole::Admin]);
        $account = User::factory()->create([
            'role' => AccountRole::Auditor,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->patch(route('users.toggle-status', $account))
            ->assertRedirect(route('users.index'));

        $this->assertFalse($account->refresh()->is_active);

        $this->actingAs($admin)
            ->patch(route('users.toggle-status', $account))
            ->assertRedirect(route('users.index'));

        $this->assertTrue($account->refresh()->is_active);
    }

    public function test_admin_cannot_deactivate_their_own_account(): void
    {
        $admin = User::factory()->create(['role' => AccountRole::Admin]);

        $this->actingAs($admin)
            ->patch(route('users.toggle-status', $admin))
            ->assertSessionHasErrors('status');

        $this->assertTrue($admin->refresh()->is_active);
    }

    public function test_auditor_cannot_manage_user_accounts(): void
    {
        $auditor = User::factory()->create(['role' => AccountRole::Auditor]);

        $this->actingAs($auditor)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_inactive_account_cannot_sign_in(): void
    {
        User::factory()->create([
            'username' => 'inactive.user',
            'password' => 'secure-password',
            'is_active' => false,
        ]);

        $this->post(route('login.store'), [
            'username' => 'inactive.user',
            'password' => 'secure-password',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_deactivated_authenticated_account_is_signed_out(): void
    {
        $account = User::factory()->create([
            'role' => AccountRole::Auditor,
            'is_active' => false,
        ]);

        $this->actingAs($account)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }
    public function test_users_page_displays_add_modal_and_account_table(): void
    {
        $admin = User::factory()->create(['role' => AccountRole::Admin]);
        User::factory()->create([
            'name' => 'Table Auditor',
            'username' => 'table.auditor',
            'role' => AccountRole::Auditor,
        ]);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('data-dialog-target="add-user-dialog"', escape: false)
            ->assertSee('id="add-user-dialog"', escape: false)
            ->assertSee('<table', escape: false)
            ->assertSee('global-loading-indicator')
            ->assertSee('table.auditor');
    }

}
