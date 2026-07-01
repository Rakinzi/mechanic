<?php

namespace Tests\Feature\Feature\Technician;

use App\Enums\StageStatus;
use App\Models\JobCard;
use App\Models\JobStage;
use App\Models\Stage;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StageMediaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_technician_can_upload_photos_to_assigned_stage(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('public');

        [$technician, $jobStage] = $this->makeAssignedStage();

        $response = $this->actingAs($technician)->post(
            "/technician/job-stages/{$jobStage->uuid}/media",
            ['photos' => [UploadedFile::fake()->image('photo.jpg', 100, 100)]],
        );

        $response->assertRedirect();
        $this->assertCount(1, $jobStage->fresh()->getMedia('stage-photos'));
    }

    public function test_technician_can_upload_multiple_photos(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('public');

        [$technician, $jobStage] = $this->makeAssignedStage();

        $response = $this->actingAs($technician)->post(
            "/technician/job-stages/{$jobStage->uuid}/media",
            [
                'photos' => [
                    UploadedFile::fake()->image('one.jpg'),
                    UploadedFile::fake()->image('two.png'),
                    UploadedFile::fake()->image('three.webp'),
                ],
            ],
        );

        $response->assertRedirect();
        $this->assertCount(3, $jobStage->fresh()->getMedia('stage-photos'));
    }

    public function test_technician_cannot_upload_to_another_technicians_stage(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('public');

        [, $jobStage] = $this->makeAssignedStage();

        $otherTechnician = User::factory()->create();
        $otherTechnician->assignRole('technician');

        $response = $this->actingAs($otherTechnician)->post(
            "/technician/job-stages/{$jobStage->uuid}/media",
            ['photos' => [UploadedFile::fake()->image('photo.jpg')]],
        );

        $response->assertForbidden();
        $this->assertCount(0, $jobStage->fresh()->getMedia('stage-photos'));
    }

    public function test_upload_requires_at_least_one_photo(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        [$technician, $jobStage] = $this->makeAssignedStage();

        $response = $this->actingAs($technician)->post(
            "/technician/job-stages/{$jobStage->uuid}/media",
            ['photos' => []],
        );

        $response->assertSessionHasErrors('photos');
    }

    public function test_upload_rejects_non_image_files(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        [$technician, $jobStage] = $this->makeAssignedStage();

        $response = $this->actingAs($technician)->post(
            "/technician/job-stages/{$jobStage->uuid}/media",
            ['photos' => [UploadedFile::fake()->create('document.pdf', 500, 'application/pdf')]],
        );

        $response->assertSessionHasErrors('photos.0');
    }

    /**
     * @return array{0: User, 1: JobStage}
     */
    protected function makeAssignedStage(): array
    {
        $technician = User::factory()->create();
        $technician->assignRole('technician');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $stage = Stage::query()->where('name', 'Panel Beating')->firstOrFail();

        $jobCard = JobCard::factory()->create([
            'vehicle_id' => Vehicle::factory()->create()->id,
            'created_by' => $admin->id,
        ]);

        $jobStage = JobStage::factory()->create([
            'job_card_id' => $jobCard->id,
            'stage_id' => $stage->id,
            'assigned_technician_id' => $technician->id,
            'sequence' => 1,
            'status' => StageStatus::InProgress,
        ]);

        $jobStage->technicians()->sync([$technician->id]);

        return [$technician, $jobStage];
    }
}
