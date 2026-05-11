<?php

namespace Tests\Feature;

use App\Models\DocumentSource;
use App\Models\IncomingDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IncomingDocumentAddUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_update_creates_updated_log_with_json_remarks(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $source = DocumentSource::create([
            'source_type' => 'section',
            'name' => 'Records',
            'is_active' => 1,
        ]);

        $doc = IncomingDocument::create([
            'created_by' => $user->id,
            'received_by' => $user->id,
            'document_reference_number' => 'DRN-ADD-UPDATE-001',
            'subject' => 'Test Subject',
        ]);

        $resp = $this->postJson(route('incoming-documents.add-update', $doc), [
            'document_from_type' => 'section',
            'return_from_document_source_id' => $source->id,
            'update_text' => 'Some update text',
        ]);

        $resp->assertOk();
        $resp->assertJsonPath('success', true);

        $log = DB::table('document_logs')
            ->where('incoming_document_id', $doc->id)
            ->where('action_type', 'UPDATED')
            ->first();

        $this->assertNotNull($log);
        $decoded = json_decode((string) $log->remarks, true);
        $this->assertIsArray($decoded);
        $this->assertSame('manual_update_v1', $decoded['kind'] ?? null);
        $this->assertSame('section', $decoded['document_from_type'] ?? null);
        $this->assertSame($source->id, (int) ($decoded['return_from']['id'] ?? 0));
        $this->assertSame('Records', (string) ($decoded['return_from']['name'] ?? ''));
        $this->assertSame('section', (string) ($decoded['return_from']['source_type'] ?? ''));
        $this->assertSame('Some update text', (string) ($decoded['update_text'] ?? ''));
    }

    public function test_add_update_requires_active_document_source(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $inactive = DocumentSource::create([
            'source_type' => 'section',
            'name' => 'Inactive',
            'is_active' => 0,
        ]);

        $doc = IncomingDocument::create([
            'created_by' => $user->id,
            'received_by' => $user->id,
            'document_reference_number' => 'DRN-ADD-UPDATE-002',
            'subject' => 'Test Subject',
        ]);

        $resp = $this->postJson(route('incoming-documents.add-update', $doc), [
            'document_from_type' => 'section',
            'return_from_document_source_id' => $inactive->id,
            'update_text' => 'Some update text',
        ]);

        $resp->assertStatus(422);
        $resp->assertJsonValidationErrors(['return_from_document_source_id']);
    }

    public function test_add_update_rejects_mismatched_source_type(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $source = DocumentSource::create([
            'source_type' => 'staff',
            'name' => 'Staff Name',
            'is_active' => 1,
        ]);

        $doc = IncomingDocument::create([
            'created_by' => $user->id,
            'received_by' => $user->id,
            'document_reference_number' => 'DRN-ADD-UPDATE-003',
            'subject' => 'Test Subject',
        ]);

        $resp = $this->postJson(route('incoming-documents.add-update', $doc), [
            'document_from_type' => 'section',
            'return_from_document_source_id' => $source->id,
            'update_text' => 'Some update text',
        ]);

        $resp->assertStatus(422);
        $resp->assertJsonValidationErrors(['return_from_document_source_id']);
    }

    public function test_add_update_forbidden_for_non_participant(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $doc = IncomingDocument::create([
            'created_by' => $owner->id,
            'received_by' => $owner->id,
            'document_reference_number' => 'DRN-ADD-UPDATE-004',
            'subject' => 'Test Subject',
        ]);

        $source = DocumentSource::create([
            'source_type' => 'section',
            'name' => 'Records',
            'is_active' => 1,
        ]);

        $this->actingAs($other);

        $resp = $this->postJson(route('incoming-documents.add-update', $doc), [
            'document_from_type' => 'section',
            'return_from_document_source_id' => $source->id,
            'update_text' => 'Some update text',
        ]);

        $resp->assertForbidden();
    }
}
