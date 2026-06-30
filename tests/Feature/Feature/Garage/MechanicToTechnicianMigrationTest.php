<?php

namespace Tests\Feature\Feature\Garage;

use App\Models\JobCard;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MechanicToTechnicianMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function migration(): Migration
    {
        return require base_path('database/migrations/2026_06_30_103249_rename_mechanic_schema_and_data_to_technician.php');
    }

    public function test_upgrade_migration_renames_existing_mechanic_schema_and_data(): void
    {
        Schema::rename('job_stage_technicians', 'job_stage_mechanics');
        Schema::table('job_stages', function (Blueprint $table): void {
            $table->renameColumn('assigned_technician_id', 'assigned_mechanic_id');
        });

        $user = User::factory()->create();
        $jobCard = JobCard::factory()->create();
        $stage = Stage::factory()->create([
            'name' => 'Mechanics',
            'sequence' => 99,
        ]);

        DB::table('job_stages')->insert([
            'job_card_id' => $jobCard->id,
            'stage_id' => $stage->id,
            'assigned_mechanic_id' => $user->id,
            'sequence' => 1,
            'status' => 'NOT_STARTED',
            'started_at' => null,
            'actual_started_at' => null,
            'paused_at' => null,
            'blocked_at' => null,
            'due_at' => now(),
            'planned_due_at' => now(),
            'completed_at' => null,
            'actual_completed_at' => null,
            'handoff_ready_at' => null,
            'last_status_changed_at' => now(),
            'latest_note' => 'Legacy mechanic assignment',
            'planned_duration_value' => 4,
            'planned_duration_unit' => 'hours',
            'uuid' => (string) str()->uuid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $jobStageId = DB::table('job_stages')->value('id');

        DB::table('job_stage_mechanics')->insert([
            'job_stage_id' => $jobStageId,
            'user_id' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roleId = DB::table('roles')->insertGetId([
            'name' => 'mechanic',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('model_has_roles')->insert([
            'role_id' => $roleId,
            'model_type' => User::class,
            'model_id' => $user->id,
        ]);

        $permissionId = DB::table('permissions')->insertGetId([
            'name' => 'view assigned stages',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('role_has_permissions')->insert([
            'permission_id' => $permissionId,
            'role_id' => $roleId,
        ]);

        $this->migration()->up();
        $technicianStageId = DB::table('stages')->where('name', 'Technicians')->value('id');

        $this->assertTrue(Schema::hasTable('job_stage_technicians'));
        $this->assertFalse(Schema::hasTable('job_stage_mechanics'));
        $this->assertTrue(Schema::hasColumn('job_stages', 'assigned_technician_id'));
        $this->assertFalse(Schema::hasColumn('job_stages', 'assigned_mechanic_id'));

        $this->assertDatabaseHas('job_stages', [
            'id' => $jobStageId,
            'assigned_technician_id' => $user->id,
            'stage_id' => $technicianStageId,
        ]);
        $this->assertDatabaseHas('job_stage_technicians', [
            'job_stage_id' => $jobStageId,
            'user_id' => $user->id,
        ]);
        $this->assertNotNull($technicianStageId);
        $this->assertDatabaseMissing('stages', ['id' => $stage->id, 'name' => 'Mechanics']);
        $this->assertDatabaseHas('roles', [
            'id' => $roleId,
            'name' => 'technician',
        ]);
        $this->assertDatabaseHas('model_has_roles', [
            'role_id' => $roleId,
            'model_type' => User::class,
            'model_id' => $user->id,
        ]);
        $this->assertDatabaseHas('role_has_permissions', [
            'permission_id' => $permissionId,
            'role_id' => $roleId,
        ]);
    }
}
