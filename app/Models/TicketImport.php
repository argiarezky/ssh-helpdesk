<?php

namespace App\Models;

use Database\Factories\TicketImportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketImport extends Model
{
    /** @use HasFactory<TicketImportFactory> */
    use HasFactory;

    protected $fillable = ['imported_by_id', 'imported_by_name', 'original_filename', 'total_rows', 'created_count', 'skipped_count'];

    protected function casts(): array
    {
        return [
            'total_rows' => 'integer',
            'created_count' => 'integer',
            'skipped_count' => 'integer',
        ];
    }

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by_id');
    }
}
