<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_displayed(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Sign in to continue')
            ->assertSee('toggle-password')
            ->assertSee('global-loading-indicator');
    }

    public function test_user_can_sign_in_with_username_and_password(): void
    {
        $user = User::factory()->create([
            'username' => 'scorekeeper',
            'password' => Hash::make('secret-password'),
        ]);

        $this->post('/login', [
            'username' => 'scorekeeper',
            'password' => 'secret-password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_sign_in_with_an_invalid_password(): void
    {
        User::factory()->create([
            'username' => 'scorekeeper',
            'password' => Hash::make('secret-password'),
        ]);

        $this->from('/login')->post('/login', [
            'username' => 'scorekeeper',
            'password' => 'wrong-password',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_sign_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_users_table_has_the_requested_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('users', [
            'id',
            'name',
            'username',
            'role',
            'is_active',
            'password',
            'created_at',
            'updated_at',
        ]));
    }
}
