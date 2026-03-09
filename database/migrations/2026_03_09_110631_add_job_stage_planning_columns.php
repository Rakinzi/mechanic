<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_stages', function (Blueprint $table) {
            $table->unsignedInteger('planned_duration_value')->nullable()->after('sequence');
            $table->string('planned_duration_unit', 20)->nullable()->after('planned_duration_value');
            $table->timestamp('planned_due_at')->nullable()->after('due_at');
            $table->timestamp('blocked_at')->nullable()->after('paused_at');
            $table->timestamp('handoff_ready_at')->nullable()->after('completed_at');
            $table->timestamp('actual_started_at')->nullable()->after('started_at');
            $table->timestamp('actual_completed_at')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('job_stages', function (Blueprint $table) {
            $table->dropColumn([
                'planned_duration_value',
                'planned_duration_unit',
                'planned_due_at',
                'blocked_at',
                'handoff_ready_at',
                'actual_started_at',
                'actual_completed_at',
            ]);
        });
    }
};
