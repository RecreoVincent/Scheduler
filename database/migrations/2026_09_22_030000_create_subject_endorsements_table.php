<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_endorsements', function (Blueprint $table): void {
            $table->id();
            $table->string('from_department', 10);
            $table->string('to_department', 10);
            $table->string('subject_code', 30);
            $table->string('subject_type', 20);
            $table->decimal('units', 4, 1);
            $table->foreignId('endorsed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['from_department', 'created_at'], 'subject_endorsement_source_created_idx');
            $table->index(['to_department', 'created_at'], 'subject_endorsement_destination_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_endorsements');
    }
};
