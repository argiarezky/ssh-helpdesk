<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('ticket_number_locks')) {
            Schema::create('ticket_number_locks', function (Blueprint $table) {
                $table->unsignedTinyInteger('id')->primary();
                $table->unsignedBigInteger('lock_version')->default(0);
            });
        }

        DB::transaction(function (): void {
            DB::table('ticket_number_locks')->insertOrIgnore(['id' => 1]);

            $ticketIds = DB::table('tickets')->orderBy('id')->pluck('id');

            if ($ticketIds->isEmpty()) {
                return;
            }

            do {
                $temporaryPrefix = '__TKT_RESEQUENCE_'.Str::uuid().'__';
            } while (DB::table('tickets')->where('ticket_number', 'like', $temporaryPrefix.'%')->exists());

            foreach ($ticketIds as $ticketId) {
                DB::table('tickets')->where('id', $ticketId)->update([
                    'ticket_number' => $temporaryPrefix.$ticketId,
                ]);
            }

            foreach ($ticketIds as $index => $ticketId) {
                DB::table('tickets')->where('id', $ticketId)->update([
                    'ticket_number' => sprintf('TKT-%03d', $index + 1),
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_number_locks');
    }
};
