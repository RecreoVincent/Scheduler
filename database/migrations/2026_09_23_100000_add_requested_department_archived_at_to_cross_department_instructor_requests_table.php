<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cross_department_instructor_requests', function (Blueprint $table): void {
            $table->timestamp('requested_department_archived_at')->nullable()->after('archived_at');
            $table->index(
                ['requested_department', 'status', 'requested_department_archived_at'],
                'cd_instructor_request_incoming_history_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::table('cross_department_instructor_requests', function (Blueprint $table): void {
            $table->dropIndex('cd_instructor_request_incoming_history_idx');
            $table->dropColumn('requested_department_archived_at');
        });
    }
};
