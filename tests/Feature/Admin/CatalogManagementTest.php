<?php

use App\Models\AdminAuditLog;
use App\Models\Category;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\Subcategory;
use App\Models\User;
use Database\Seeders\RoleSeeder;

/**
 * Vérifie la gestion administrative du catalogue.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

/**
 * Crée un administrateur de test.
 */
function catalogAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

test('un administrateur crée et modifie une catégorie', function () {
    $admin = catalogAdmin();

    $this->actingAs($admin)
        ->post('/admin/categories', [
            'name' => 'Plomberie',
            'description' => 'Installation et dépannage.',
            'color' => '#2563EB',
            'sort_order' => 3,
        ])
        ->assertSessionHasNoErrors();

    $category = Category::query()->where('name', 'Plomberie')->firstOrFail();

    expect($category->slug)->toBe('plomberie');
    expect($category->is_active)->toBeTrue();
    expect(AdminAuditLog::query()->where('action', 'category.created')->count())->toBe(1);

    $this->actingAs($admin)
        ->patch("/admin/categories/{$category->id}", [
            'name' => 'Plomberie générale',
            'description' => 'Mise à jour.',
            'sort_order' => 4,
        ])
        ->assertSessionHasNoErrors();

    expect($category->refresh()->name)->toBe('Plomberie générale');
});

test('un administrateur active ou désactive une catégorie', function () {
    $admin = catalogAdmin();
    $category = Category::factory()->create();

    $this->actingAs($admin)
        ->patch("/admin/categories/{$category->id}/activation", ['is_active' => false])
        ->assertSessionHasNoErrors();

    expect($category->refresh()->is_active)->toBeFalse();
    expect(AdminAuditLog::query()->where('action', 'category.activation.updated')->count())->toBe(1);
});

test('un administrateur crée une sous-catégorie rattachée à une catégorie', function () {
    $admin = catalogAdmin();
    $category = Category::factory()->create();

    $this->actingAs($admin)
        ->post('/admin/sous-categories', [
            'category_id' => $category->id,
            'name' => 'Robinetterie',
        ])
        ->assertSessionHasNoErrors();

    $subcategory = Subcategory::query()->where('name', 'Robinetterie')->firstOrFail();

    expect($subcategory->category_id)->toBe($category->id);
    expect($subcategory->slug)->toBe('robinetterie');
    expect(AdminAuditLog::query()->where('action', 'subcategory.created')->count())->toBe(1);
});

test('un administrateur désactive un service', function () {
    $admin = catalogAdmin();
    $profile = ProviderProfile::factory()->verified()->create();
    $service = Service::factory()->create(['provider_profile_id' => $profile->id]);

    $this->actingAs($admin)
        ->patch("/admin/services/{$service->id}/activation", ['is_active' => false])
        ->assertSessionHasNoErrors();

    expect($service->refresh()->is_active)->toBeFalse();
    expect(AdminAuditLog::query()->where('action', 'service.activation.updated')->count())->toBe(1);
});

test('un client ne peut pas gérer le catalogue', function () {
    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $this->actingAs($customer)->get('/admin/categories')->assertForbidden();
    $this->actingAs($customer)->get('/admin/services')->assertForbidden();
});
