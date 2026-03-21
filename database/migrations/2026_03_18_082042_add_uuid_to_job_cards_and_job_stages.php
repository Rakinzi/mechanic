<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_cards', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->after('id');
        });

        DB::table('job_cards')->get()->each(function (object $row): void {
            DB::table('job_cards')->where('id', $row->id)->update(['uuid' => (string) Str::uuid()]);
        });

        Schema::table('job_cards', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable(false)->unique()->change();
        });

        Schema::table('job_stages', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->after('id');
        });

        DB::table('job_stages')->get()->each(function (object $row): void {
            DB::table('job_stages')->where('id', $row->id)->update(['uuid' => (string) Str::uuid()]);
        });

        Schema::table('job_stages', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('job_cards', function (Blueprint $table): void {
            $table->dropColumn('uuid');
        });

        Schema::table('job_stages', function (Blueprint $table): void {
            $table->dropColumn('uuid');
        });
    }
};
