<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketAttachmentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_engineer_can_create_a_ticket_with_an_attachment(): void
    {
        Storage::fake('local');
        $engineer = User::factory()->engineer()->create();

        $this->actingAs($engineer)->post(route('tickets.store'), [
            'reporter_name' => 'Rina Putri',
            'location' => 'Ruang Server',
            'coordinator_name' => 'Ragil',
            'description' => 'Router utama tidak merespons.',
            'engineer_id' => $engineer->id,
            'status' => 'open',
            'priority' => 'high',
            'attachments' => [UploadedFile::fake()->createWithContent('router-log.pdf', "%PDF-1.4\nRouter log")],
        ])->assertRedirect();

        $attachment = TicketAttachment::query()->sole();
        Storage::disk('local')->assertExists($attachment->path);
        $this->assertDatabaseHas('ticket_attachments', [
            'ticket_id' => $attachment->ticket_id,
            'uploaded_by' => $engineer->id,
            'original_name' => 'router-log.pdf',
        ]);
    }

    public function test_engineer_can_upload_download_and_delete_a_ticket_attachment(): void
    {
        Storage::fake('local');
        $engineer = User::factory()->engineer()->create();
        $ticket = Ticket::factory()->create(['engineer_id' => $engineer->id]);
        $file = UploadedFile::fake()->createWithContent('network-log.pdf', "%PDF-1.4\nNetwork log");

        $this->actingAs($engineer)->put(route('tickets.update', $ticket), [
            'reporter_name' => $ticket->reporter_name,
            'location' => $ticket->location,
            'coordinator_name' => $ticket->coordinator_name,
            'description' => $ticket->description,
            'engineer_id' => $engineer->id,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'handling_notes' => $ticket->handling_notes,
            'attachments' => [$file],
        ])->assertRedirect(route('tickets.show', $ticket));

        $attachment = TicketAttachment::query()->sole();
        Storage::disk('local')->assertExists($attachment->path);
        $this->assertDatabaseHas('ticket_attachments', [
            'id' => $attachment->id,
            'ticket_id' => $ticket->id,
            'uploaded_by' => $engineer->id,
            'original_name' => 'network-log.pdf',
        ]);

        $this->get(route('tickets.attachments.download', [$ticket, $attachment]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('tickets.show', $ticket))->assertOk()->assertSee('network-log.pdf');

        $this->delete(route('tickets.attachments.destroy', [$ticket, $attachment]))->assertRedirect();

        Storage::disk('local')->assertMissing($attachment->path);
        $this->assertDatabaseMissing('ticket_attachments', ['id' => $attachment->id]);
    }

    public function test_engineer_cannot_upload_an_unsupported_attachment(): void
    {
        Storage::fake('local');
        $engineer = User::factory()->engineer()->create();
        $ticket = Ticket::factory()->create(['engineer_id' => $engineer->id]);

        $this->actingAs($engineer)->put(route('tickets.update', $ticket), [
            'reporter_name' => $ticket->reporter_name,
            'location' => $ticket->location,
            'coordinator_name' => $ticket->coordinator_name,
            'description' => $ticket->description,
            'engineer_id' => $engineer->id,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'handling_notes' => $ticket->handling_notes,
            'attachments' => [UploadedFile::fake()->createWithContent('malware.php', '<?php echo 1;')],
        ])->assertSessionHasErrors('attachments.0');

        $this->assertDatabaseCount('ticket_attachments', 0);
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id]);
        $this->assertCount(0, Storage::disk('local')->allFiles('ticket-attachments/'.$ticket->id));
    }

    public function test_deleting_ticket_removes_its_attachment_file_and_record(): void
    {
        Storage::fake('local');
        $engineer = User::factory()->engineer()->create();
        $ticket = Ticket::factory()->create(['engineer_id' => $engineer->id]);
        $path = 'ticket-attachments/'.$ticket->id.'/evidence.pdf';
        Storage::disk('local')->put($path, "%PDF-1.4\nEvidence");
        $attachment = $ticket->attachments()->create([
            'uploaded_by' => $engineer->id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => 'evidence.pdf',
            'mime_type' => 'application/pdf',
            'size' => 20,
        ]);

        $this->actingAs($engineer)->delete(route('tickets.destroy', $ticket))->assertRedirect(route('tickets.index'));

        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseMissing('ticket_attachments', ['id' => $attachment->id]);
    }

    public function test_attachment_route_is_scoped_to_its_parent_ticket(): void
    {
        Storage::fake('local');
        $engineer = User::factory()->engineer()->create();
        $ticket = Ticket::factory()->create(['engineer_id' => $engineer->id]);
        $otherTicket = Ticket::factory()->create(['engineer_id' => $engineer->id]);
        $attachment = $otherTicket->attachments()->create([
            'uploaded_by' => $engineer->id,
            'disk' => 'local',
            'path' => 'ticket-attachments/'.$otherTicket->id.'/private.pdf',
            'original_name' => 'private.pdf',
            'mime_type' => 'application/pdf',
            'size' => 20,
        ]);
        Storage::disk('local')->put($attachment->path, "%PDF-1.4\nPrivate");

        $this->actingAs($engineer)->get(route('tickets.attachments.download', [$ticket, $attachment]))->assertNotFound();
    }
}
