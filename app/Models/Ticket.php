<?php

namespace App\Models;

use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Ticket extends Model
{
    public const ACTIVE_STATUSES = ['open', 'pending', 'in_progress'];

    public const RESPONSIBLE_ENGINEERS = ['Affan', 'Nalar', 'Kadek', 'Hakim', 'Hari', 'Rasyid', 'Irzam'];

    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    protected $fillable = ['ticket_number', 'legacy_ticket_number', 'reporter_name', 'location', 'coordinator_name', 'description', 'engineer_id', 'status', 'pending_reminded_at', 'priority', 'due_at', 'due_reminded_at', 'archived_at', 'handling_notes', 'report_source', 'project_name', 'reporter_unit', 'issue_category', 'outage_category', 'devices', 'pic_name', 'pic_contact', 'delivery_status', 'technician_name', 'assigned_engineer_name', 'sla_target', 'action_type', 'team_plan', 'resolved_at', 'duration_text'];

    protected static function booted(): void
    {
        static::deleting(function (Ticket $ticket): void {
            DB::table('notifications')->where('data->ticket_id', $ticket->getKey())->delete();
            $ticket->attachments()->get()->each(function (TicketAttachment $attachment): void {
                Storage::disk($attachment->disk)->delete($attachment->path);
            });
        });
    }

    protected function casts(): array
    {
        return [
            'pending_reminded_at' => 'datetime',
            'due_at' => 'datetime',
            'due_reminded_at' => 'datetime',
            'archived_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function getDurationAttribute(): ?string
    {
        if (filled($this->duration_text)) {
            return $this->duration_text;
        }

        if ($this->resolved_at === null) {
            return null;
        }

        $minutes = $this->created_at->diffInMinutes($this->resolved_at);
        $days = intdiv($minutes, 1440);
        $hours = intdiv($minutes % 1440, 60);
        $remainingMinutes = $minutes % 60;
        $parts = [];

        if ($days > 0) {
            $parts[] = $days.' hari';
        }

        if ($hours > 0) {
            $parts[] = $hours.' jam';
        }

        if ($remainingMinutes > 0 || $parts === []) {
            $parts[] = $remainingMinutes.' menit';
        }

        return implode(' ', $parts);
    }

    public function engineer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'engineer_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TicketActivity::class)->latest();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class)->latest();
    }
}
