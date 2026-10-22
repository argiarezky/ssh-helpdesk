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
        Schema::table('ticket_activities', function (Blueprint $table): void {
            $table->dropForeign(['ticket_id']);
            $table->foreign('ticket_id')->references('id')->on('tickets')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_activities', function (Blueprint $table): void {
            $table->dropForeign(['ticket_id']);
            $table->foreign('ticket_id')->references('id')->on('tickets')->nullOnDelete();
        });
    }
};
