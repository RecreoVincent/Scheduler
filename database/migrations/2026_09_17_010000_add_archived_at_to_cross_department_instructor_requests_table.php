<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cross_department_instructor_requests', function (Blueprint $table): void {
            $table->timestamp('archived_at')->nullable()->after('fulfilled_at');
            $table->index(
                ['requesting_department', 'status', 'archived_at'],
                'cd_instructor_request_outgoing_history_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::table('cross_department_instructor_requests', function (Blueprint $table): void {
            $table->dropIndex('cd_instructor_request_outgoing_history_idx');
            $table->dropColumn('archived_at');
        });
    }
};
