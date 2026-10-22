<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ticket_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('imported_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('imported_by_name');
            $table->string('original_filename');
            $table->unsignedInteger('total_rows');
            $table->unsignedInteger('created_count');
            $table->unsignedInteger('skipped_count');
            $table->timestamps();
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_imports');
    }
};
