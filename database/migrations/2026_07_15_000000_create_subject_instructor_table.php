<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_instructor', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subject_id')->index();
            $table->unsignedBigInteger('instructor_id')->index();
            $table->timestamps();
            $table->unique(['subject_id', 'instructor_id']);
        });

        DB::table('subjects')
            ->whereNotNull('instructor_id')
            ->orderBy('id')
            ->eachById(function ($subject): void {
                DB::table('subject_instructor')->insertOrIgnore([
                    'subject_id' => $subject->id,
                    'instructor_id' => $subject->instructor_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_instructor');
    }
};
