<?php

namespace Tests\Feature\Feature\Garage;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_seeder_creates_default_role_users(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(UserSeeder::class);

        $this->assertTrue(User::query()->where('email', 'admin@garage.test')->firstOrFail()->hasRole('admin'));
        $this->assertCount(3, User::query()->role('mechanic')->get());
        $this->assertCount(3, User::query()->role('client')->get());
    }
}
