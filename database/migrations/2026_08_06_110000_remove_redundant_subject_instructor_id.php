<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('subjects', 'instructor_id')) {
            Schema::table('subjects', function (Blueprint $table): void {
                $table->dropIndex(['instructor_id']);
                $table->dropColumn('instructor_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table): void {
            $table->unsignedBigInteger('instructor_id')->nullable()->index();
        });

        DB::table('subject_instructor')->orderBy('priority')->orderBy('id')->get()
            ->unique('subject_id')->each(fn ($assignment) => DB::table('subjects')
                ->where('id', $assignment->subject_id)->update(['instructor_id' => $assignment->instructor_id]));
    }
};
