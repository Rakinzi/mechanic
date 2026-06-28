<?php

namespace Tests\Feature\Feature\Garage;

use App\Models\Client;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminUserCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_user(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Created User',
            'email' => 'created-user@garage.test',
            'phone' => '1234567890',
            'role' => 'mechanic',
            'password' => 'password123',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'created-user@garage.test',
            'name' => 'Created User',
        ]);

        $created = User::query()->where('email', 'created-user@garage.test')->firstOrFail();
        $this->assertTrue($created->hasRole('mechanic'));
    }

    public function test_admin_creating_client_role_user_also_creates_client_record(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New Client User',
            'email' => 'new-client@garage.test',
            'phone' => '0812345678',
            'role' => 'client',
            'password' => 'password123',
            'is_active' => true,
        ])->assertRedirect();

        $user = User::query()->where('email', 'new-client@garage.test')->firstOrFail();
        $this->assertTrue($user->hasRole('client'));
        $this->assertDatabaseHas('clients', [
            'user_id' => $user->id,
            'name' => 'New Client User',
            'email' => 'new-client@garage.test',
        ]);
    }

    public function test_admin_creating_client_user_links_to_existing_walk_in_client_with_same_email(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $walkIn = Client::factory()->create([
            'user_id' => null,
            'name' => 'Walk In',
            'email' => 'walkin@garage.test',
        ]);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Real Name Now',
            'email' => 'walkin@garage.test',
            'role' => 'client',
            'password' => 'password123',
            'is_active' => true,
        ])->assertRedirect();

        $user = User::query()->where('email', 'walkin@garage.test')->firstOrFail();
        $this->assertDatabaseHas('clients', [
            'id' => $walkIn->id,
            'user_id' => $user->id,
            'name' => 'Real Name Now',
        ]);
        $this->assertDatabaseCount('clients', 1);
    }

    public function test_admin_changing_user_role_to_client_creates_client_record_if_missing(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $target = User::factory()->create(['name' => 'Promoted User', 'email' => 'promoted@garage.test']);
        $target->assignRole('mechanic');

        $this->assertDatabaseMissing('clients', ['user_id' => $target->id]);

        $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'name' => 'Promoted User',
            'email' => 'promoted@garage.test',
            'role' => 'client',
            'password' => '',
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('clients', [
            'user_id' => $target->id,
            'name' => 'Promoted User',
            'email' => 'promoted@garage.test',
        ]);
    }

    public function test_updating_client_user_details_syncs_client_record(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $target = User::factory()->create(['name' => 'Old Name', 'email' => 'oldname@garage.test']);
        $target->assignRole('client');
        Client::factory()->create(['user_id' => $target->id, 'name' => 'Old Name', 'email' => 'oldname@garage.test']);

        $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'name' => 'New Name',
            'email' => 'newname@garage.test',
            'phone' => '0800000001',
            'role' => 'client',
            'password' => '',
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('clients', [
            'user_id' => $target->id,
            'name' => 'New Name',
            'email' => 'newname@garage.test',
        ]);
    }

    public function test_admin_can_update_user(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $target = User::factory()->create([
            'name' => 'Before Name',
            'email' => 'before@garage.test',
        ]);
        $target->assignRole('client');

        $response = $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'name' => 'After Name',
            'email' => 'after@garage.test',
            'phone' => '9999999999',
            'role' => 'mechanic',
            'password' => '',
            'is_active' => false,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'name' => 'After Name',
            'email' => 'after@garage.test',
            'is_active' => false,
        ]);
        $this->assertTrue($target->fresh()->hasRole('mechanic'));
    }

    public function test_admin_can_delete_another_user_but_not_self(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $target = User::factory()->create();
        $target->assignRole('client');

        $deleteTargetResponse = $this->actingAs($admin)->delete(route('admin.users.destroy', $target));
        $deleteTargetResponse->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $target->id]);

        $deleteSelfResponse = $this->actingAs($admin)->delete(route('admin.users.destroy', $admin));
        $deleteSelfResponse->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $deleteSelfResponse->assertSessionHas('error', 'You cannot delete your own account.');
    }

    public function test_non_admin_cannot_manage_users(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $technician = User::factory()->create();
        $technician->assignRole('mechanic');

        $response = $this->actingAs($technician)->post(route('admin.users.store'), [
            'name' => 'Nope',
            'email' => 'nope@garage.test',
            'role' => 'client',
            'password' => 'password123',
        ]);

        $response->assertForbidden();
    }

    public function test_admin_can_filter_users_by_role_and_search(): void
    {
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $technician = User::factory()->create([
            'name' => 'Target Technician',
            'email' => 'target-technician@garage.test',
        ]);
        $technician->assignRole('mechanic');

        $client = User::factory()->create([
            'name' => 'Target Client',
            'email' => 'target-client@garage.test',
        ]);
        $client->assignRole('client');

        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            'role' => 'mechanic',
            'search' => 'target-technician',
        ]));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/UserManagement')
                ->where('filters.role', 'mechanic')
                ->where('filters.search', 'target-technician')
                ->where('users.data.0.email', 'target-technician@garage.test')
                ->missing('users.data.1')
            );
    }

    public function test_admin_users_list_is_paginated(): void
    {
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        User::factory()->count(15)->create()->each(function (User $user): void {
            $user->assignRole('client');
        });

        $response = $this->actingAs($admin)->get(route('admin.users.index', ['page' => 2]));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/UserManagement')
                ->where('users.current_page', 2)
                ->has('users.data')
            );
    }
}
