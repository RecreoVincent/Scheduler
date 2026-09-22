<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('last_login_at')->nullable()->after('account_status')->index();
        });

        // Existing accounts with the internal roster address were created by
        // the roster-login flow, so preserve their already-active state.
        DB::table('users')
            ->where('role', 'student')
            ->where('account_status', 'active')
            ->where('email', 'like', 'student-%@roster.mcc.local')
            ->update(['last_login_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['last_login_at']);
            $table->dropColumn('last_login_at');
        });
    }
};
