<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@garage.test'],
            [
                'name' => 'Garage Admin',
                'password' => 'password',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $admin->assignRole('admin');

        collect([
            ['name' => 'Mechanic One', 'email' => 'mechanic1@garage.test'],
            ['name' => 'Mechanic Two', 'email' => 'mechanic2@garage.test'],
            ['name' => 'Mechanic Three', 'email' => 'mechanic3@garage.test'],
        ])->each(function (array $mechanicData): void {
            $mechanic = User::query()->updateOrCreate(
                ['email' => $mechanicData['email']],
                [
                    'name' => $mechanicData['name'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );

            $mechanic->assignRole('mechanic');
        });

        collect([
            ['name' => 'Client One', 'email' => 'client1@garage.test'],
            ['name' => 'Client Two', 'email' => 'client2@garage.test'],
            ['name' => 'Client Three', 'email' => 'client3@garage.test'],
        ])->each(function (array $clientData): void {
            $client = User::query()->updateOrCreate(
                ['email' => $clientData['email']],
                [
                    'name' => $clientData['name'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );

            $client->assignRole('client');
        });
    }
}
