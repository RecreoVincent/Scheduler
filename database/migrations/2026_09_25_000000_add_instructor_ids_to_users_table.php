<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('instructor_id', 9)->nullable()->unique()->after('id');
        });

        Schema::create('instructor_id_sequences', function (Blueprint $table) {
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedSmallInteger('next_sequence')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instructor_id_sequences');

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['instructor_id']);
            $table->dropColumn('instructor_id');
        });
    }
};
