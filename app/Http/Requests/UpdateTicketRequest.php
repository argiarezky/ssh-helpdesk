<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use App\Rules\SimilarActiveTicket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isEngineer() ?? false;
    }

    public function rules(): array
    {
        return [
            'reporter_name' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'coordinator_name' => ['required', 'string', 'max:255'],
            'legacy_ticket_number' => ['nullable', 'string', 'max:80'],
            'report_source' => ['nullable', 'string', 'max:255'],
            'project_name' => ['nullable', 'string', 'max:255'],
            'reporter_unit' => ['nullable', 'string', 'max:255'],
            'issue_category' => ['nullable', 'string', 'max:255'],
            'outage_category' => ['nullable', 'string', 'max:255'],
            'devices' => ['nullable', 'string', 'max:5000'],
            'pic_name' => ['nullable', 'string', 'max:255'],
            'pic_contact' => ['nullable', 'string', 'max:255'],
            'delivery_status' => ['nullable', 'string', 'max:100'],
            'technician_name' => ['nullable', 'string', 'max:255'],
            'sla_target' => ['nullable', 'string', 'max:100'],
            'action_type' => ['nullable', 'string', 'max:100'],
            'team_plan' => ['nullable', 'string', 'max:5000'],
            'resolved_at' => ['nullable', 'date'],
            'duration_text' => ['nullable', 'string', 'max:100'],
            'description' => [
                'required',
                'string',
                'max:10000',
                Rule::unique('tickets', 'description')
                    ->where('reporter_name', $this->input('reporter_name'))
                    ->where('location', $this->input('location'))
                    ->whereIn('status', Ticket::ACTIVE_STATUSES)
                    ->ignore($this->route('ticket')),
                new SimilarActiveTicket((string) $this->input('reporter_name'), (string) $this->input('location'), $this->route('ticket')?->id),
            ],
            'engineer_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', 'engineer')],
            'assigned_engineer_name' => ['required_without:engineer_id', 'nullable', 'string', Rule::in(Ticket::RESPONSIBLE_ENGINEERS)],
            'status' => ['required', Rule::in(['open', 'pending', 'in_progress', 'resolved', 'closed'])],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'due_at' => ['nullable', 'date'],
            'handling_notes' => ['nullable', 'string', 'max:10000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'description.unique' => 'Tiket aktif dengan pelapor, lokasi, dan deskripsi yang sama sudah ada.',
            'attachments.max' => 'Maksimal 5 lampiran untuk setiap pengiriman.',
            'attachments.*.mimes' => 'Lampiran hanya boleh berupa JPG, PNG, WEBP, atau PDF.',
            'attachments.*.max' => 'Ukuran setiap lampiran maksimal 10 MB.',
        ];
    }
}
