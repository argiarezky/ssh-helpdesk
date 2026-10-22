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
        Schema::table('tickets', function (Blueprint $table): void {
            $table->string('legacy_ticket_number')->nullable()->after('ticket_number');
            $table->string('report_source')->nullable();
            $table->string('project_name')->nullable();
            $table->string('reporter_unit')->nullable();
            $table->string('issue_category')->nullable();
            $table->string('outage_category')->nullable();
            $table->text('devices')->nullable();
            $table->string('pic_name')->nullable();
            $table->string('pic_contact')->nullable();
            $table->string('delivery_status')->nullable();
            $table->string('technician_name')->nullable();
            $table->string('sla_target')->nullable();
            $table->string('action_type')->nullable();
            $table->text('team_plan')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->string('duration_text', 100)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropColumn([
                'legacy_ticket_number', 'report_source', 'project_name', 'reporter_unit',
                'issue_category', 'outage_category', 'devices', 'pic_name', 'pic_contact',
                'delivery_status', 'technician_name', 'sla_target', 'action_type',
                'team_plan', 'resolved_at', 'duration_text',
            ]);
        });
    }
};
