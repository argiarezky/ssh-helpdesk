<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ticket_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 40)->index();
            $table->string('message');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        DB::table('tickets')->whereNotNull('handling_notes')->where('handling_notes', '<>', '')
            ->orderBy('id')->get(['id', 'handling_notes', 'updated_at'])->each(function (object $ticket): void {
                DB::table('ticket_activities')->insert([
                    'ticket_id' => $ticket->id,
                    'user_id' => null,
                    'action' => 'handling_note',
                    'message' => 'Catatan penanganan tersimpan sebelum riwayat aktivitas diaktifkan.',
                    'notes' => $ticket->handling_notes,
                    'created_at' => $ticket->updated_at,
                    'updated_at' => $ticket->updated_at,
                ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_activities');
    }
};
