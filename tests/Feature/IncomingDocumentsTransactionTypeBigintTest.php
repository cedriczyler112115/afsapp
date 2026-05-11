<?php

namespace Tests\Feature;

use App\Models\IncomingDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IncomingDocumentsTransactionTypeBigintTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_requires_transaction_type(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $typeId = (int) DB::table('document_types')->insertGetId([
            'name' => 'Memo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $sourceId = (int) DB::table('document_sources')->insertGetId([
            'name' => 'Section A',
            'source_type' => 'section',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $resp = $this->post(route('incoming-documents.store'), [
            'transaction_type' => null,
            'document_reference_number' => 'REF-REQ-001',
            'date_received' => now()->toDateString(),
            'document_from_type' => 'section',
            'document_source_id' => $sourceId,
            'drn' => 'DRN-REQ-001',
            'document_type_id' => $typeId,
            'subject' => 'Subject',
        ]);

        $resp->assertSessionHasErrors(['transaction_type']);
    }

    public function test_index_table_shows_document_type_column_and_value(): void
    {
        $user = User::factory()->create(['name' => 'Creator']);
        $this->actingAs($user);

        IncomingDocument::create([
            'created_by' => $user->id,
            'received_by' => $user->id,
            'document_reference_number' => 'DOC-TX-001',
            'transaction_type' => 2,
            'subject' => 'Outgoing Doc',
            'current_status' => 'RECEIVED',
            'date_received' => now(),
            'document_from_type' => 'section',
        ]);

        $resp = $this->get(route('incoming-documents.index'));
        $resp->assertOk();
        $resp->assertSee('Document Type');
        $resp->assertSee('OUTGOING');
        $resp->assertSee('DOC-TX-001');
    }
}
