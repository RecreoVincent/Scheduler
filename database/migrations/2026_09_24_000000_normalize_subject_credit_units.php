<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('subjects')
            ->whereRaw("LOWER(TRIM(subject_type)) IN ('lecture', 'laboratory')")
            ->where('units', '!=', 3)
            ->update([
                'units' => 3,
                'updated_at' => now(),
            ]);

        DB::table('subjects')
            ->whereRaw("LOWER(TRIM(subject_type)) = 'internship'")
            ->where('units', '!=', 6)
            ->update([
                'units' => 6,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // The previous unit values cannot be reconstructed safely.
    }
};
