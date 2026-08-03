<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('employment_type', 'part_time')->update([
            'employment_type' => 'flexible_part_time',
        ]);
    }

    public function down(): void
    {
        DB::table('users')->where('employment_type', 'flexible_part_time')->update([
            'employment_type' => 'part_time',
        ]);
    }
};
