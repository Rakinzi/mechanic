<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_card_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_stage_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event');
            $table->text('description');
            $table->json('metadata')->nullable();
            $table->timestamp('happened_at');
            $table->timestamps();

            $table->index(['job_card_id', 'happened_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_card_audits');
    }
};
