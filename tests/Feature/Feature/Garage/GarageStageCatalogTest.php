<?php

namespace Tests\Feature\Feature\Garage;

use App\Models\Stage;
use Database\Seeders\GarageDemoSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GarageStageCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_garage_demo_seeder_uses_the_workshop_stage_catalog(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(GarageDemoSeeder::class);

        $this->assertSame([
            'Panel Beating',
            'Technicians',
            'Spraypainting',
            'Buffing',
            'Carwash',
            'Waxing n Polishing',
            'Release',
        ], Stage::query()->where('is_active', true)->orderBy('sequence')->pluck('name')->all());
    }
}
