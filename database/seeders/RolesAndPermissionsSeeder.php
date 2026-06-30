<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'view admin dashboard',
            'manage stages',
            'manage users',
            'manage job cards',
            'review delay reports',
            'view reports',
            'view assigned stages',
            'run stage actions',
            'submit delay reports',
            'view own repairs',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission]);
        }

        $admin = Role::query()->firstOrCreate(['name' => 'admin']);
        $technician = Role::query()->firstOrCreate(['name' => 'technician']);
        $client = Role::query()->firstOrCreate(['name' => 'client']);

        $admin->syncPermissions($permissions);
        $technician->syncPermissions(['view assigned stages', 'run stage actions', 'submit delay reports']);
        $client->syncPermissions(['view own repairs']);
    }
}
