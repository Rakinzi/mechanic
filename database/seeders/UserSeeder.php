<?php

namespace Database\Seeders;

use App\Models\Client;
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
            ['name' => 'Technician One', 'email' => 'technician1@garage.test'],
            ['name' => 'Technician Two', 'email' => 'technician2@garage.test'],
            ['name' => 'Technician Three', 'email' => 'technician3@garage.test'],
        ])->each(function (array $technicianData): void {
            $technician = User::query()->updateOrCreate(
                ['email' => $technicianData['email']],
                [
                    'name' => $technicianData['name'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );

            $technician->assignRole('technician');
        });

        collect([
            ['name' => 'Client One', 'email' => 'client1@garage.test'],
            ['name' => 'Client Two', 'email' => 'client2@garage.test'],
            ['name' => 'Client Three', 'email' => 'client3@garage.test'],
        ])->each(function (array $clientData): void {
            $user = User::query()->updateOrCreate(
                ['email' => $clientData['email']],
                [
                    'name' => $clientData['name'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );

            $user->assignRole('client');

            Client::query()->updateOrCreate(
                ['email' => $clientData['email']],
                [
                    'user_id' => $user->id,
                    'name' => $clientData['name'],
                    'email' => $clientData['email'],
                ]
            );
        });
    }
}
