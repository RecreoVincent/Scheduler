<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cross_department_instructor_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subject_id');
            $table->string('requesting_department', 10);
            $table->string('requested_department', 10);
            $table->foreignId('requested_by')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('assigned_instructor_id')->nullable();
            $table->foreignId('fulfilled_by')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamps();

            $table->index(['requested_department', 'status'], 'cd_instructor_request_requested_status_idx');
            $table->index(['requesting_department', 'status'], 'cd_instructor_request_requesting_status_idx');
            $table->foreign('subject_id', 'cd_instructor_request_subject_fk')->references('id')->on('subjects')->cascadeOnDelete();
            $table->foreign('requested_by', 'cd_instructor_request_requester_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('assigned_instructor_id', 'cd_instructor_request_assignee_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('fulfilled_by', 'cd_instructor_request_fulfiller_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cross_department_instructor_requests');
    }
};
