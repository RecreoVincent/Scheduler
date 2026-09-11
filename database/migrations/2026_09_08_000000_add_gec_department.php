<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $nextSortOrder = ((int) DB::table('departments')->max('sort_order')) + 1;

        $gecDepartmentId = DB::table('departments')->insertGetId([
            'code' => 'GEC',
            'name' => 'General Education Course',
            'program_name' => 'General Education Courses',
            'logo_path' => null,
            'email' => null,
            'sort_order' => $nextSortOrder,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('users')
            ->where('role', 'gec')
            ->update(['course' => 'GEC', 'department_id' => $gecDepartmentId, 'updated_at' => $now]);
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'gec')->update(['course' => null, 'department_id' => null]);
        DB::table('departments')->where('code', 'GEC')->delete();
    }
};
