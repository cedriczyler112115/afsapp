<?php

namespace Tests\Feature;

use App\Models\DocumentLog;
use App\Models\DocumentSource;
use App\Models\IncomingDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingLogEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_owner_can_update_manual_update_text(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $doc = IncomingDocument::create([
            'created_by' => $user->id,
            'subject' => 'Subject',
        ]);

        $log = DocumentLog::create([
            'incoming_document_id' => $doc->id,
            'user_id' => $user->id,
            'action_type' => 'UPDATED',
            'action_timestamp' => now(),
            'remarks' => json_encode([
                'kind' => 'manual_update_v1',
                'return_from' => ['id' => 1, 'name' => 'Records', 'source_type' => 'section'],
                'update_text' => 'Old text',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        $resp = $this->putJson(route('incoming-documents.logs.update', [$doc, $log]), [
            'update_text' => 'New text',
        ]);

        $resp->assertOk();
        $resp->assertJsonPath('data.update_text', 'New text');

        $updated = DocumentLog::findOrFail($log->id);
        $decoded = json_decode((string) $updated->remarks, true);
        $this->assertSame('New text', (string) ($decoded['update_text'] ?? ''));
    }

    public function test_non_owner_non_admin_cannot_update_log(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $doc = IncomingDocument::create([
            'created_by' => $owner->id,
            'subject' => 'Subject',
        ]);

        $log = DocumentLog::create([
            'incoming_document_id' => $doc->id,
            'user_id' => $owner->id,
            'action_type' => 'RECEIVED',
            'action_timestamp' => now(),
            'remarks' => 'Initial',
        ]);

        $this->actingAs($other);
        $resp = $this->putJson(route('incoming-documents.logs.update', [$doc, $log]), [
            'remarks' => 'Changed',
        ]);

        $resp->assertStatus(403);
    }

    public function test_admin_can_update_another_users_log(): void
    {
        $admin = User::factory()->create();
        $owner = User::factory()->create();

        $doc = IncomingDocument::create([
            'created_by' => $owner->id,
            'subject' => 'Subject',
        ]);

        $log = DocumentLog::create([
            'incoming_document_id' => $doc->id,
            'user_id' => $owner->id,
            'action_type' => 'RECEIVED',
            'action_timestamp' => now(),
            'remarks' => 'Initial',
        ]);

        $this->actingAs($admin);
        $resp = $this->putJson(route('incoming-documents.logs.update', [$doc, $log]), [
            'remarks' => 'Admin edit',
        ]);

        $resp->assertOk();
        $this->assertDatabaseHas('document_logs', [
            'id' => $log->id,
            'remarks' => 'Admin edit',
        ]);
    }

    public function test_show_page_renders_edit_button_for_owner(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $doc = IncomingDocument::create([
            'created_by' => $user->id,
            'subject' => 'Subject',
        ]);

        DocumentLog::create([
            'incoming_document_id' => $doc->id,
            'user_id' => $user->id,
            'action_type' => 'RECEIVED',
            'action_timestamp' => now(),
            'remarks' => 'Initial',
        ]);

        $resp = $this->get(route('incoming-documents.show', $doc));
        $resp->assertOk();
        $resp->assertSee('js-edit-tracking-log', false);
        $resp->assertSee('editTrackingLogModal', false);
    }

    public function test_owner_can_edit_remarks_when_remarks_is_not_json_even_if_action_type_updated(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $doc = IncomingDocument::create([
            'created_by' => $user->id,
            'subject' => 'Subject',
        ]);

        $log = DocumentLog::create([
            'incoming_document_id' => $doc->id,
            'user_id' => $user->id,
            'action_type' => 'UPDATED',
            'action_timestamp' => now(),
            'remarks' => 'plain text update',
        ]);

        $resp = $this->putJson(route('incoming-documents.logs.update', [$doc, $log]), [
            'remarks' => 'changed text',
        ]);

        $resp->assertOk();
        $this->assertDatabaseHas('document_logs', [
            'id' => $log->id,
            'remarks' => 'changed text',
        ]);
    }

    public function test_admin_can_add_manual_update_log(): void
    {
        $admin = User::factory()->create();
        $owner = User::factory()->create();

        $source = DocumentSource::create([
            'source_type' => 'section',
            'name' => 'Records',
            'is_active' => 1,
        ]);

        $doc = IncomingDocument::create([
            'created_by' => $owner->id,
            'subject' => 'Subject',
        ]);

        $this->actingAs($admin);
        $resp = $this->postJson(route('incoming-documents.add-update', $doc), [
            'manual_update_party' => 'to',
            'document_from_type' => 'section',
            'return_from_document_source_id' => $source->id,
            'update_text' => 'Admin note',
        ]);

        $resp->assertOk();
        $resp->assertJson(['success' => true]);

        $this->assertDatabaseHas('document_logs', [
            'incoming_document_id' => $doc->id,
            'user_id' => $admin->id,
            'action_type' => 'UPDATED',
        ]);
    }
}
