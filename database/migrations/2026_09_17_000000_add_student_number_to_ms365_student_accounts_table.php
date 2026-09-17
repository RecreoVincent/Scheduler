<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ms365_student_accounts', function (Blueprint $table): void {
            $table->string('student_number')->nullable()->index()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('ms365_student_accounts', function (Blueprint $table): void {
            $table->dropIndex(['student_number']);
            $table->dropColumn('student_number');
        });
    }
};
