<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('subjects')->where('curriculum', 'Old')->update(['curriculum' => 'New']);

        Schema::table('subjects', function (Blueprint $table): void {
            $table->string('curriculum', 10)->default('New')->change();
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table): void {
            $table->string('curriculum', 10)->default('Old')->change();
        });
    }
};
