<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $stages = [
            ['sequence' => 1, 'name' => 'Panel Beating', 'sla_value' => 12, 'sla_unit' => 'hours'],
            ['sequence' => 2, 'name' => 'Mechanics', 'sla_value' => 8, 'sla_unit' => 'hours'],
            ['sequence' => 3, 'name' => 'Spraypainting', 'sla_value' => 10, 'sla_unit' => 'hours'],
            ['sequence' => 4, 'name' => 'Buffing', 'sla_value' => 4, 'sla_unit' => 'hours'],
            ['sequence' => 5, 'name' => 'Carwash', 'sla_value' => 2, 'sla_unit' => 'hours'],
            ['sequence' => 6, 'name' => 'Waxing n Polishing', 'sla_value' => 3, 'sla_unit' => 'hours'],
            ['sequence' => 7, 'name' => 'Release', 'sla_value' => 1, 'sla_unit' => 'hours'],
        ];

        foreach ($stages as $stage) {
            $existingId = DB::table('stages')
                ->where('sequence', $stage['sequence'])
                ->value('id');

            if ($existingId !== null) {
                DB::table('stages')
                    ->where('id', $existingId)
                    ->update([
                        'name' => sprintf('__stage_sync_%d__', $stage['sequence']),
                        'updated_at' => now(),
                    ]);
            }
        }

        foreach ($stages as $stage) {
            $existingId = DB::table('stages')
                ->where('sequence', $stage['sequence'])
                ->value('id');

            if ($existingId !== null) {
                DB::table('stages')
                    ->where('id', $existingId)
                    ->update($stage + ['is_active' => true, 'updated_at' => now()]);

                continue;
            }

            DB::table('stages')->insert($stage + [
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('stages')
            ->whereNotIn('sequence', array_column($stages, 'sequence'))
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void {}
};
