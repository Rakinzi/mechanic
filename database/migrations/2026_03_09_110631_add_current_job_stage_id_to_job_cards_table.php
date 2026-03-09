<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_cards', function (Blueprint $table) {
            $table->foreignId('current_job_stage_id')->nullable()->after('status')->constrained('job_stages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('job_cards', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_job_stage_id');
        });
    }
};
