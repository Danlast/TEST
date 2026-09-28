<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_users_and_update_role_with_audit_log(): void
    {
        $admin = $this->createUser('admin', UserRole::ADMIN);
        $target = $this->createUser('target', UserRole::USER);

        $this->actingAs($admin)
            ->get(route('admin.panel', ['role' => UserRole::USER->value]))
            ->assertOk()
            ->assertSee($target->username);

        $this->actingAs($admin)
            ->post(route('admin.users.update', $target), [
                'role' => UserRole::MODERATOR->value,
                'club_id' => '',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'role' => UserRole::MODERATOR->value,
            'club_id' => null,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'user.role_updated',
            'target_id' => $target->id,
        ]);
    }

    public function test_admin_cannot_remove_the_last_admin_role(): void
    {
        $admin = $this->createUser('admin', UserRole::ADMIN);

        $this->actingAs($admin)
            ->post(route('admin.users.update', $admin), [
                'role' => UserRole::USER->value,
                'club_id' => '',
            ])
            ->assertSessionHasErrors('role');
    }

    public function test_non_admin_cannot_access_admin_panel(): void
    {
        $user = $this->createUser('user', UserRole::USER);

        $this->actingAs($user)->get(route('admin.panel'))->assertForbidden();
        $this->get(route('admin.panel'))->assertForbidden();
    }

    private function createUser(string $username, UserRole $role): User
    {
        return User::create([
            'username' => $username . uniqid(),
            'email' => $username . uniqid() . '@example.test',
            'password' => 'password',
            'role' => $role,
        ]);
    }
}
