<?php

use App\Enums\AccountStatus;
use App\Models\User;
use Database\Seeders\RoleSeeder;

/**
 * Vérifie l'accès à l'administration des comptes et la sécurisation des
 * changements de rôle.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

/**
 * Crée un utilisateur disposant du rôle indiqué.
 */
function userWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('guests are redirected to the login page', function () {
    $this->get('/admin/users')->assertRedirect('/login');
});

test('a customer can not access the administration', function () {
    $user = userWithRole('customer');

    $this->actingAs($user)->get('/admin/users')->assertForbidden();
});

test('a professional can not access the administration', function () {
    $user = userWithRole('professional');

    $this->actingAs($user)->get('/admin/users')->assertForbidden();
});

test('an admin can list the accounts', function () {
    $admin = userWithRole('admin');
    $customer = User::factory()->create(['name' => 'Client Test']);

    $response = $this->actingAs($admin)->get('/admin/users');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/Users')
        ->where('users.total', 2)
        ->where('users.data.1.name', 'Client Test'),
    );
});

test('the account list can be filtered by status', function () {
    $admin = userWithRole('admin');
    User::factory()->create(['status' => AccountStatus::Suspended]);

    $response = $this->actingAs($admin)->get('/admin/users?status=suspended');

    $response->assertInertia(fn ($page) => $page->where('users.total', 1));
});

test('an admin can suspend an account', function () {
    $admin = userWithRole('admin');
    $customer = User::factory()->create();

    $this->actingAs($admin)
        ->patch("/admin/users/{$customer->id}/status", ['status' => 'suspended'])
        ->assertRedirect();

    expect($customer->fresh()->status)->toBe(AccountStatus::Suspended);
});

test('an admin can reactivate an account', function () {
    $admin = userWithRole('admin');
    $customer = User::factory()->create(['status' => AccountStatus::Suspended]);

    $this->actingAs($admin)
        ->patch("/admin/users/{$customer->id}/status", ['status' => 'active'])
        ->assertRedirect();

    expect($customer->fresh()->status)->toBe(AccountStatus::Active);
});

test('an unknown status is rejected', function () {
    $admin = userWithRole('admin');
    $customer = User::factory()->create();

    $this->actingAs($admin)
        ->patch("/admin/users/{$customer->id}/status", ['status' => 'inconnu'])
        ->assertSessionHasErrors('status');

    expect($customer->fresh()->status)->toBe(AccountStatus::Active);
});

test('an admin can not change their own status', function () {
    $admin = userWithRole('admin');

    $this->actingAs($admin)
        ->patch("/admin/users/{$admin->id}/status", ['status' => 'suspended'])
        ->assertForbidden();

    expect($admin->fresh()->status)->toBe(AccountStatus::Active);
});

test('an admin can not assign roles', function () {
    $admin = userWithRole('admin');
    $customer = User::factory()->create();

    $this->actingAs($admin)
        ->patch("/admin/users/{$customer->id}/roles", ['roles' => ['professional']])
        ->assertForbidden();

    expect($customer->fresh()->hasRole('professional'))->toBeFalse();
});

test('a super admin can assign roles', function () {
    $superAdmin = userWithRole('super-admin');
    $customer = User::factory()->create();

    $this->actingAs($superAdmin)
        ->patch("/admin/users/{$customer->id}/roles", ['roles' => ['professional']])
        ->assertRedirect();

    expect($customer->fresh()->hasRole('professional'))->toBeTrue();
});

test('a super admin can not change their own roles', function () {
    $superAdmin = userWithRole('super-admin');

    $this->actingAs($superAdmin)
        ->patch("/admin/users/{$superAdmin->id}/roles", ['roles' => ['customer']])
        ->assertForbidden();

    expect($superAdmin->fresh()->hasRole('super-admin'))->toBeTrue();
});

test('a super admin can demote another super admin', function () {
    $actor = userWithRole('super-admin');
    $other = userWithRole('super-admin');

    $this->actingAs($actor)
        ->patch("/admin/users/{$other->id}/roles", ['roles' => ['customer']])
        ->assertRedirect();

    expect($other->fresh()->hasRole('super-admin'))->toBeFalse();
    expect($actor->fresh()->hasRole('super-admin'))->toBeTrue();
});

test('a super admin can not change their own status', function () {
    $superAdmin = userWithRole('super-admin');

    $this->actingAs($superAdmin)
        ->patch("/admin/users/{$superAdmin->id}/status", ['status' => 'suspended'])
        ->assertForbidden();
});

test('an account detail page is only visible to an authorized administrator', function () {
    $admin = userWithRole('admin');
    $customer = User::factory()->create();

    $response = $this->actingAs($admin)->get("/admin/users/{$customer->id}");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/UserDetail')
        ->where('account.id', $customer->id),
    );
});

test('an admin can not open the detail of another account without permission', function () {
    $customer = userWithRole('customer');
    $other = User::factory()->create();

    $this->actingAs($customer)->get("/admin/users/{$other->id}")->assertForbidden();
});
