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
            $table->string('first_name')->nullable()->after('name');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('last_name')->nullable()->after('middle_name');
            $table->string('suffix', 30)->nullable()->after('last_name');
        });

        DB::table('users')
            ->select(['id', 'name'])
            ->orderBy('id')
            ->chunkById(100, function ($users): void {
                foreach ($users as $user) {
                    $parts = preg_split('/\s+/', trim((string) $user->name)) ?: [];
                    $suffix = null;

                    if (count($parts) > 1 && in_array(strtolower(rtrim((string) end($parts), '.')), ['jr', 'sr', 'ii', 'iii', 'iv', 'v'], true)) {
                        $suffix = array_pop($parts);
                    }

                    $firstName = array_shift($parts);
                    $lastName = count($parts) > 0 ? array_pop($parts) : null;
                    $middleName = count($parts) > 0 ? implode(' ', $parts) : null;

                    DB::table('users')->where('id', $user->id)->update([
                        'first_name' => $firstName ?: null,
                        'middle_name' => $middleName,
                        'last_name' => $lastName,
                        'suffix' => $suffix,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'middle_name', 'last_name', 'suffix']);
        });
    }
};
