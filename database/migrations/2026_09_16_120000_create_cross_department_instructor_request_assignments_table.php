<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cross_department_instructor_request_assignments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('instructor_request_id');
            $table->unsignedBigInteger('instructor_id');
            $table->unsignedTinyInteger('priority');
            $table->timestamps();

            $table->index('instructor_request_id', 'cdira_request_idx');
            $table->index('instructor_id', 'cdira_instructor_idx');
            $table->unique(['instructor_request_id', 'instructor_id'], 'cdira_request_instructor_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cross_department_instructor_request_assignments');
    }
};
