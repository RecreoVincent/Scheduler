<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
        });

        DB::table('users')
            ->select(['id', 'first_name', 'middle_name', 'last_name', 'suffix'])
            ->orderBy('id')
            ->chunkById(100, function ($users): void {
                foreach ($users as $user) {
                    $name = implode(' ', array_filter([
                        $user->first_name,
                        $user->middle_name,
                        $user->last_name,
                        $user->suffix,
                    ], fn (?string $part): bool => filled($part)));

                    DB::table('users')->where('id', $user->id)->update(['name' => $name]);
                }
            });
    }
};
