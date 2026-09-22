<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subject_endorsements', function (Blueprint $table): void {
            $table->string('subject_name', 150)->nullable()->after('subject_code');
        });
    }

    public function down(): void
    {
        Schema::table('subject_endorsements', function (Blueprint $table): void {
            $table->dropColumn('subject_name');
        });
    }
};
