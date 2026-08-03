<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subject_instructor', function (Blueprint $table): void {
            $table->unsignedTinyInteger('priority')->default(1)->after('instructor_id');
        });

        DB::table('subject_instructor')
            ->select('subject_id')
            ->distinct()
            ->orderBy('subject_id')
            ->pluck('subject_id')
            ->each(function ($subjectId): void {
                DB::table('subject_instructor')
                    ->where('subject_id', $subjectId)
                    ->orderBy('id')
                    ->pluck('id')
                    ->each(fn ($id, $index) => DB::table('subject_instructor')
                        ->where('id', $id)
                        ->update(['priority' => $index + 1]));
            });
    }

    public function down(): void
    {
        Schema::table('subject_instructor', function (Blueprint $table): void {
            $table->dropColumn('priority');
        });
    }
};
