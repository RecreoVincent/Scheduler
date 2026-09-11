<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_handoffs', function (Blueprint $table) {
            $table->id();
            $table->string('course', 10);
            $table->string('academic_year', 20);
            $table->string('semester', 10);
            $table->timestamp('majors_sent_at')->nullable();
            $table->foreignId('majors_sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('minors_sent_back_at')->nullable();
            $table->foreignId('minors_sent_back_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['course', 'academic_year', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_handoffs');
    }
};
