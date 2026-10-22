<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table): void {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->string('reporter_name');
            $table->string('location');
            $table->string('coordinator_name');
            $table->text('description');
            $table->foreignId('engineer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('open')->index();
            $table->string('priority', 32)->default('normal');
            $table->text('handling_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
