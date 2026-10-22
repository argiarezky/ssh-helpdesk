<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('ticket_activities')->whereNull('ticket_id')->delete();
    }

    /**
     * Orphaned activity rows cannot be restored because they have no ticket reference.
     */
    public function down(): void {}
};
