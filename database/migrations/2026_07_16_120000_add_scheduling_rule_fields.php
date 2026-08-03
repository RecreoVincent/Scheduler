<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->string('classification')->default('Major')->after('subject_type');
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->string('room_type')->default('Lecture')->after('name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->time('outside_work_end_time')->nullable()->after('employment_type');
        });

        DB::table('rooms')
            ->where(function ($query) {
                $query->where('name', 'like', '%lab%')
                    ->orWhere('name', 'like', '%laboratory%');
            })
            ->update(['room_type' => 'Laboratory']);
    }

    public function down(): void
    {
        Schema::table('subjects', fn (Blueprint $table) => $table->dropColumn('classification'));
        Schema::table('rooms', fn (Blueprint $table) => $table->dropColumn('room_type'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('outside_work_end_time'));
    }
};
