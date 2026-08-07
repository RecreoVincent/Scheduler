<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ms365_student_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('display_name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->uuid('object_id')->nullable()->index();
            $table->string('license')->nullable();
            $table->boolean('is_blocked')->default(false);
            $table->timestamp('soft_deleted_at')->nullable();
            $table->timestamp('ms365_created_at')->nullable();
            $table->timestamp('last_imported_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ms365_student_accounts');
    }
};
