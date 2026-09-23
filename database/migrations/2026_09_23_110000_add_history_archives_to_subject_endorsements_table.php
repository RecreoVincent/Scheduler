<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subject_endorsements', function (Blueprint $table): void {
            $table->timestamp('from_department_archived_at')->nullable()->after('scheduled_at');
            $table->timestamp('to_department_archived_at')->nullable()->after('from_department_archived_at');
        });
    }

    public function down(): void
    {
        Schema::table('subject_endorsements', function (Blueprint $table): void {
            $table->dropColumn(['from_department_archived_at', 'to_department_archived_at']);
        });
    }
};
