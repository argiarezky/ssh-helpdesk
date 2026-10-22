<?php

namespace App\Rules;

use App\Models\Ticket;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Translation\PotentiallyTranslatedString;

class SimilarActiveTicket implements ValidationRule
{
    public function __construct(
        private readonly string $reporterName,
        private readonly string $location,
        private readonly ?int $ignoreTicketId = null,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        $candidate = $this->normalize($value);

        if ($candidate === '') {
            return;
        }

        $similarTicketExists = Ticket::query()
            ->whereIn('status', Ticket::ACTIVE_STATUSES)
            ->whereRaw('LOWER(reporter_name) = LOWER(?)', [$this->reporterName])
            ->whereRaw('LOWER(location) = LOWER(?)', [$this->location])
            ->when($this->ignoreTicketId !== null, fn (Builder $query) => $query->whereKeyNot($this->ignoreTicketId))
            ->pluck('description')
            ->contains(function (string $description) use ($candidate): bool {
                similar_text($candidate, $this->normalize($description), $similarity);

                return $similarity >= 82;
            });

        if ($similarTicketExists) {
            $fail('Tiket aktif serupa sudah terdaftar untuk pelapor dan lokasi ini. Periksa tiket lama sebelum membuat laporan baru.');
        }
    }

    private function normalize(string $description): string
    {
        $normalized = mb_strtolower(trim($description));

        return preg_replace('/[^\pL\pN]+/u', '', $normalized) ?? $normalized;
    }
}
