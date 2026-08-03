<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'Monday' => 'M - W',
            'Wednesday' => 'M - W',
            'Tuesday' => 'T - Th',
            'Thursday' => 'T - Th',
            'Friday' => 'F - S',
            'Saturday' => 'F - S',
        ] as $day => $pattern) {
            DB::table('class_schedules')->where('day', $day)->update(['day' => $pattern]);
        }
    }

    public function down(): void
    {
        foreach (['M - W' => 'Monday', 'T - Th' => 'Tuesday', 'F - S' => 'Friday'] as $pattern => $day) {
            DB::table('class_schedules')->where('day', $pattern)->update(['day' => $day]);
        }
    }
};
