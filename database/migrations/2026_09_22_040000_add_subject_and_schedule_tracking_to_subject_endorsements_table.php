<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subject_endorsements', function (Blueprint $table): void {
            $table->foreignId('subject_id')->nullable()->after('id')->constrained('subjects')->nullOnDelete();
            $table->foreignId('scheduled_by')->nullable()->after('endorsed_by')->constrained('users')->nullOnDelete();
            $table->timestamp('scheduled_at')->nullable()->after('scheduled_by');
        });
    }

    public function down(): void
    {
        Schema::table('subject_endorsements', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('subject_id');
            $table->dropConstrainedForeignId('scheduled_by');
            $table->dropColumn('scheduled_at');
        });
    }
};
