<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('job_stage_mechanics') && ! Schema::hasTable('job_stage_technicians')) {
            Schema::rename('job_stage_mechanics', 'job_stage_technicians');
        }

        if (Schema::hasTable('job_stages')
            && Schema::hasColumn('job_stages', 'assigned_mechanic_id')
            && ! Schema::hasColumn('job_stages', 'assigned_technician_id')) {
            Schema::table('job_stages', function (Blueprint $table): void {
                $table->renameColumn('assigned_mechanic_id', 'assigned_technician_id');
            });
        }

        $this->renameStage('Mechanics', 'Technicians');
        $this->renameRole('mechanic', 'technician');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('job_stage_technicians') && ! Schema::hasTable('job_stage_mechanics')) {
            Schema::rename('job_stage_technicians', 'job_stage_mechanics');
        }

        if (Schema::hasTable('job_stages')
            && Schema::hasColumn('job_stages', 'assigned_technician_id')
            && ! Schema::hasColumn('job_stages', 'assigned_mechanic_id')) {
            Schema::table('job_stages', function (Blueprint $table): void {
                $table->renameColumn('assigned_technician_id', 'assigned_mechanic_id');
            });
        }

        $this->renameStage('Technicians', 'Mechanics');
        $this->renameRole('technician', 'mechanic');
    }

    protected function renameStage(string $from, string $to): void
    {
        if (! Schema::hasTable('stages')) {
            return;
        }

        $existingSourceStage = DB::table('stages')->where('name', $from)->first();
        $existingTargetStage = DB::table('stages')->where('name', $to)->first();

        if ($existingSourceStage === null) {
            return;
        }

        if ($existingTargetStage === null) {
            DB::table('stages')
                ->where('id', $existingSourceStage->id)
                ->update(['name' => $to]);

            return;
        }

        DB::table('job_stages')
            ->where('stage_id', $existingSourceStage->id)
            ->update(['stage_id' => $existingTargetStage->id]);

        DB::table('stages')->where('id', $existingSourceStage->id)->delete();
    }

    protected function renameRole(string $from, string $to): void
    {
        $rolesTable = config('permission.table_names.roles', 'roles');
        $modelHasRolesTable = config('permission.table_names.model_has_roles', 'model_has_roles');
        $roleHasPermissionsTable = config('permission.table_names.role_has_permissions', 'role_has_permissions');
        $rolePivotKey = config('permission.column_names.role_pivot_key', 'role_id');

        if (! Schema::hasTable($rolesTable)) {
            return;
        }

        $sourceRole = DB::table($rolesTable)->where('name', $from)->first();
        $targetRole = DB::table($rolesTable)->where('name', $to)->first();

        if ($sourceRole === null) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return;
        }

        if ($targetRole === null) {
            DB::table($rolesTable)
                ->where('id', $sourceRole->id)
                ->update(['name' => $to]);

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return;
        }

        if (Schema::hasTable($modelHasRolesTable)) {
            DB::table($modelHasRolesTable)
                ->where($rolePivotKey, $sourceRole->id)
                ->update([$rolePivotKey => $targetRole->id]);
        }

        if (Schema::hasTable($roleHasPermissionsTable)) {
            $permissionPivotKey = config('permission.column_names.permission_pivot_key', 'permission_id');

            $permissionIds = DB::table($roleHasPermissionsTable)
                ->where($rolePivotKey, $sourceRole->id)
                ->pluck($permissionPivotKey);

            foreach ($permissionIds as $permissionId) {
                $alreadyLinked = DB::table($roleHasPermissionsTable)
                    ->where($rolePivotKey, $targetRole->id)
                    ->where($permissionPivotKey, $permissionId)
                    ->exists();

                if (! $alreadyLinked) {
                    DB::table($roleHasPermissionsTable)->insert([
                        $permissionPivotKey => $permissionId,
                        $rolePivotKey => $targetRole->id,
                    ]);
                }
            }

            DB::table($roleHasPermissionsTable)
                ->where($rolePivotKey, $sourceRole->id)
                ->delete();
        }

        DB::table($rolesTable)->where('id', $sourceRole->id)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
