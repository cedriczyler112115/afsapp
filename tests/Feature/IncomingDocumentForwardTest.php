<?php

namespace Tests\Feature;

use App\Models\IncomingDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class IncomingDocumentForwardTest extends TestCase
{
    use RefreshDatabase;

    public function test_group_lookup_returns_only_active_groups(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        DB::table('group')->insert([
            ['group_name' => 'Active A', 'status' => 1, 'created_by' => null, 'date_created' => now()],
            ['group_name' => 'Inactive B', 'status' => 0, 'created_by' => null, 'date_created' => now()],
        ]);

        $resp = $this->getJson(route('incoming-documents.lookups.groups'));

        $resp->assertOk();
        $resp->assertJsonPath('success', true);
        $this->assertCount(1, $resp->json('groups'));
        $this->assertSame('Active A', $resp->json('groups.0.name'));
    }

    public function test_staff_search_requires_minimum_two_characters(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $resp = $this->getJson(route('incoming-documents.lookups.staff', ['q' => 'a', 'offset' => 0]));

        $resp->assertOk();
        $resp->assertJsonPath('success', true);
        $this->assertSame([], $resp->json('items'));
    }

    public function test_forward_to_group_sets_forwarded_to_group_id_and_allows_group_member_access(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $this->actingAs($owner);

        $groupId = (int) DB::table('group')->insertGetId([
            'group_name' => 'Team A',
            'status' => 1,
            'created_by' => null,
            'date_created' => now(),
        ]);

        $member->forceFill(['group_id' => $groupId])->save();

        $doc = IncomingDocument::create([
            'created_by' => $owner->id,
            'received_by' => $owner->id,
            'document_reference_number' => 'DRN-TEST-001',
            'subject' => 'Test Subject',
        ]);

        $resp = $this->post(route('incoming-documents.forward', $doc), [
            'forward_to' => 'group',
            'forwarded_to_group_id' => $groupId,
            'forward_staff_mode' => '0',
            'forward_remarks' => 'Forwarded to group',
        ]);

        $resp->assertRedirect(route('incoming-documents.show', $doc));

        $doc->refresh();
        $this->assertSame('FORWARDED', $doc->current_status);
        $this->assertSame($groupId, (int) $doc->forwarded_to_group_id);
        $this->assertNull($doc->forwarded_to_user_id);

        $log = DB::table('document_logs')->where('incoming_document_id', $doc->id)->where('action_type', 'FORWARDED')->first();
        $this->assertNotNull($log);
        $decoded = json_decode((string) $log->remarks, true);
        $this->assertIsArray($decoded);
        $this->assertSame('forward_recipients_v1', $decoded['kind'] ?? null);
        $this->assertSame('group', $decoded['mode'] ?? null);
        $this->assertCount(1, $decoded['recipients'] ?? []);
        $this->assertSame($member->id, (int) ($decoded['recipients'][0]['id'] ?? 0));

        $this->actingAs($member);
        $this->get(route('incoming-documents.show', $doc))->assertOk();
    }

    public function test_forward_to_group_does_not_add_current_user_as_recipient(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $this->actingAs($owner);

        $groupId = (int) DB::table('group')->insertGetId([
            'group_name' => 'Team Self',
            'status' => 1,
            'created_by' => null,
            'date_created' => now(),
        ]);

        $owner->forceFill(['group_id' => $groupId])->save();
        $member->forceFill(['group_id' => $groupId])->save();

        $doc = IncomingDocument::create([
            'created_by' => $owner->id,
            'received_by' => $owner->id,
            'document_reference_number' => 'DRN-TEST-SELF-001',
            'subject' => 'Test Subject',
        ]);

        $resp = $this->post(route('incoming-documents.forward', $doc), [
            'forward_to' => 'group',
            'forwarded_to_group_id' => $groupId,
            'forward_staff_mode' => '0',
            'forward_remarks' => 'Forwarded to group',
        ]);

        $resp->assertRedirect(route('incoming-documents.show', $doc));

        $this->assertDatabaseMissing('incoming_document_forward_recipients', [
            'incoming_document_id' => $doc->id,
            'user_id' => $owner->id,
        ]);
        $this->assertDatabaseHas('incoming_document_forward_recipients', [
            'incoming_document_id' => $doc->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_forward_multi_staff_rejects_including_current_user(): void
    {
        $owner = User::factory()->create();
        $staff1 = User::factory()->create();
        $this->actingAs($owner);

        $doc = IncomingDocument::create([
            'created_by' => $owner->id,
            'received_by' => $owner->id,
            'document_reference_number' => 'DRN-TEST-SELF-002',
            'subject' => 'Test Subject',
        ]);

        $resp = $this->from(route('incoming-documents.show', $doc))->post(route('incoming-documents.forward', $doc), [
            'forward_to' => 'group',
            'forward_staff_mode' => '1',
            'forwarded_to_user_ids' => [$owner->id, $staff1->id],
        ]);

        $resp->assertRedirect(route('incoming-documents.show', $doc));
        $resp->assertSessionHasErrors(['forwarded_to_user_ids.0']);
    }

    public function test_forward_multi_staff_requires_at_least_one_user(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);

        $doc = IncomingDocument::create([
            'created_by' => $owner->id,
            'received_by' => $owner->id,
            'document_reference_number' => 'DRN-TEST-002',
            'subject' => 'Test Subject',
        ]);

        $resp = $this->from(route('incoming-documents.show', $doc))->post(route('incoming-documents.forward', $doc), [
            'forward_to' => 'group',
            'forward_staff_mode' => '1',
            'forwarded_to_user_ids' => [],
        ]);

        $resp->assertRedirect(route('incoming-documents.show', $doc));
        $resp->assertSessionHasErrors(['forwarded_to_user_ids']);
    }

    public function test_forward_multi_staff_creates_recipients_and_allows_recipient_access(): void
    {
        $owner = User::factory()->create();
        $staff1 = User::factory()->create();
        $staff2 = User::factory()->create();
        $this->actingAs($owner);

        $doc = IncomingDocument::create([
            'created_by' => $owner->id,
            'received_by' => $owner->id,
            'document_reference_number' => 'DRN-TEST-003',
            'subject' => 'Test Subject',
        ]);

        $resp = $this->post(route('incoming-documents.forward', $doc), [
            'forward_to' => 'group',
            'forward_staff_mode' => '1',
            'forwarded_to_user_ids' => [$staff1->id, $staff2->id],
            'forward_remarks' => 'Forwarded to staff',
        ]);

        $resp->assertRedirect(route('incoming-documents.show', $doc));

        $this->assertDatabaseHas('incoming_document_forward_recipients', [
            'incoming_document_id' => $doc->id,
            'user_id' => $staff1->id,
        ]);
        $this->assertDatabaseHas('incoming_document_forward_recipients', [
            'incoming_document_id' => $doc->id,
            'user_id' => $staff2->id,
        ]);

        $this->assertSame(1, DB::table('document_logs')->where('incoming_document_id', $doc->id)->where('action_type', 'FORWARDED')->count());
        $log = DB::table('document_logs')->where('incoming_document_id', $doc->id)->where('action_type', 'FORWARDED')->first();
        $decoded = json_decode((string) $log->remarks, true);
        $this->assertIsArray($decoded);
        $this->assertSame('forward_recipients_v1', $decoded['kind'] ?? null);
        $this->assertSame('staff', $decoded['mode'] ?? null);
        $this->assertCount(2, $decoded['recipients'] ?? []);

        $this->actingAs($staff1);
        $this->get(route('incoming-documents.show', $doc))->assertOk();
    }

    public function test_received_documents_list_renders_one_row_per_batch(): void
    {
        $user = User::factory()->create(['name' => 'Batch User']);
        $receiver = User::factory()->create(['name' => 'Receiver User']);
        $this->actingAs($user);

        $typeId = (int) DB::table('document_types')->insertGetId([
            'name' => 'Memo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $sourceId = (int) DB::table('document_sources')->insertGetId([
            'name' => 'Office A',
            'source_type' => 'OFFICE',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $doc1 = IncomingDocument::create([
            'created_by' => $user->id,
            'received_by' => $user->id,
            'document_source_id' => $sourceId,
            'document_type_id' => $typeId,
            'transaction_type' => 1,
            'document_reference_number' => 'BATCH-DOC-001',
            'drn' => 'DRN-001',
            'subject' => 'Subject 1',
            'current_status' => 'FORWARDED',
            'date_forwarded' => now(),
        ]);
        $doc2 = IncomingDocument::create([
            'created_by' => $user->id,
            'received_by' => $user->id,
            'document_source_id' => $sourceId,
            'document_type_id' => $typeId,
            'document_reference_number' => 'BATCH-DOC-002',
            'drn' => 'DRN-002',
            'subject' => 'Subject 2',
            'current_status' => 'FORWARDED',
            'date_forwarded' => now(),
        ]);

        DB::table('incoming_documents')->where('id', $doc1->id)->update(['transaction_type' => 1]);
        DB::table('incoming_documents')->where('id', $doc2->id)->update(['transaction_type' => 1]);

        $batchId = (int) DB::table('batch_received')->insertGetId([
            'batch_staff_name' => $user->id.' - '.$user->name,
            'created_by' => $user->id,
            'date_created' => now(),
            'status' => 0,
        ]);

        DB::table('incoming_document_forward_recipients')->insert([
            [
                'incoming_document_id' => $doc1->id,
                'user_id' => $user->id,
                'date_received' => now(),
                'received_by' => $receiver->id,
                'received_in_behalf' => null,
                'batch_id' => $batchId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'incoming_document_id' => $doc2->id,
                'user_id' => $user->id,
                'date_received' => now(),
                'received_by' => $receiver->id,
                'received_in_behalf' => null,
                'batch_id' => $batchId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $resp = $this->get(route('inbox.batch.received', ['per_page' => 10]));
        $resp->assertOk();

        $html = (string) $resp->getContent();
        $this->assertStringContainsString('Transaction Type', $html);
        $this->assertStringContainsString('BATCH-DOC-001', $html);
        $this->assertStringContainsString('BATCH-DOC-002', $html);
        $this->assertStringContainsString('INCOMING', $html);

        preg_match_all('/<tr\\b[^>]*data-batch-id="/i', $html, $matches);
        $this->assertSame(1, count($matches[0]));
    }

    public function test_batch_documents_endpoint_returns_all_documents_in_batch(): void
    {
        $user = User::factory()->create(['name' => 'Batch User']);
        $receiver = User::factory()->create(['name' => 'Receiver User']);
        $this->actingAs($user);

        $typeId = (int) DB::table('document_types')->insertGetId([
            'name' => 'Memo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $sourceId = (int) DB::table('document_sources')->insertGetId([
            'name' => 'Office A',
            'source_type' => 'OFFICE',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $doc1 = IncomingDocument::create([
            'created_by' => $user->id,
            'received_by' => $user->id,
            'document_source_id' => $sourceId,
            'document_type_id' => $typeId,
            'document_reference_number' => 'BATCH-DOC-001',
            'drn' => 'DRN-001',
            'subject' => 'Subject 1',
            'current_status' => 'FORWARDED',
            'date_forwarded' => now(),
        ]);
        $doc2 = IncomingDocument::create([
            'created_by' => $user->id,
            'received_by' => $user->id,
            'document_source_id' => $sourceId,
            'document_type_id' => $typeId,
            'document_reference_number' => 'BATCH-DOC-002',
            'drn' => 'DRN-002',
            'subject' => 'Subject 2',
            'current_status' => 'FORWARDED',
            'date_forwarded' => now(),
        ]);

        DB::table('incoming_documents')->where('id', $doc1->id)->update(['transaction_type' => 1]);
        DB::table('incoming_documents')->where('id', $doc2->id)->update(['transaction_type' => 1]);

        $batchId = (int) DB::table('batch_received')->insertGetId([
            'batch_staff_name' => $user->id.' - '.$user->name,
            'created_by' => $user->id,
            'date_created' => now(),
            'status' => 0,
        ]);

        DB::table('incoming_document_forward_recipients')->insert([
            [
                'incoming_document_id' => $doc1->id,
                'user_id' => $user->id,
                'date_received' => now(),
                'received_by' => $receiver->id,
                'received_in_behalf' => null,
                'batch_id' => $batchId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'incoming_document_id' => $doc2->id,
                'user_id' => $user->id,
                'date_received' => now(),
                'received_by' => $receiver->id,
                'received_in_behalf' => null,
                'batch_id' => $batchId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $resp = $this->getJson(route('inbox.batch.documents', ['batch' => $batchId]));
        $resp->assertOk();
        $resp->assertJsonPath('success', true);
        $resp->assertJsonPath('batch_id', $batchId);
        $resp->assertJsonCount(2, 'documents');

        $payload = $resp->json();
        $this->assertSame(['BATCH-DOC-001', 'BATCH-DOC-002'], array_values(array_map(fn ($d) => (string) ($d['document_number'] ?? ''), (array) ($payload['documents'] ?? []))));
        $this->assertSame(['Subject 1', 'Subject 2'], array_values(array_map(fn ($d) => (string) ($d['subject'] ?? ''), (array) ($payload['documents'] ?? []))));
        $this->assertSame([1, 1], array_values(array_map(fn ($d) => (int) ($d['transaction_type'] ?? 0), (array) ($payload['documents'] ?? []))));
    }

    public function test_inbox_index_shows_transaction_type_column(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $typeId = (int) DB::table('document_types')->insertGetId([
            'name' => 'Memo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $sourceId = (int) DB::table('document_sources')->insertGetId([
            'name' => 'Office A',
            'source_type' => 'OFFICE',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $doc = IncomingDocument::create([
            'created_by' => $user->id,
            'received_by' => $user->id,
            'document_source_id' => $sourceId,
            'document_type_id' => $typeId,
            'document_reference_number' => 'INBOX-TX-001',
            'drn' => 'DRN-INBOX-001',
            'subject' => 'Inbox Tx',
            'transaction_type' => 2,
            'current_status' => 'FORWARDED',
            'date_forwarded' => now(),
        ]);

        DB::table('incoming_document_forward_recipients')->insert([
            'incoming_document_id' => $doc->id,
            'user_id' => $user->id,
            'date_received' => null,
            'received_by' => null,
            'received_in_behalf' => null,
            'batch_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $resp = $this->get(route('inbox.index'));
        $resp->assertOk();
        $resp->assertSee('OUTGOING');
        $resp->assertSee('INBOX-TX-001');
    }

    public function test_batch_receive_page_shows_transaction_type_column(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $typeId = (int) DB::table('document_types')->insertGetId([
            'name' => 'Memo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $sourceId = (int) DB::table('document_sources')->insertGetId([
            'name' => 'Office A',
            'source_type' => 'OFFICE',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $doc = IncomingDocument::create([
            'created_by' => $user->id,
            'received_by' => $user->id,
            'document_source_id' => $sourceId,
            'document_type_id' => $typeId,
            'document_reference_number' => 'ROUTE-TX-001',
            'drn' => 'DRN-ROUTE-001',
            'subject' => 'Route Slip Tx',
            'transaction_type' => 2,
            'current_status' => 'FORWARDED',
            'date_forwarded' => now(),
        ]);

        DB::table('incoming_document_forward_recipients')->insert([
            'incoming_document_id' => $doc->id,
            'user_id' => $user->id,
            'date_received' => null,
            'received_by' => null,
            'received_in_behalf' => null,
            'batch_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $resp = $this->get(route('inbox.batch'));
        $resp->assertOk();
        $resp->assertSee('OUTGOING');
        $resp->assertSee('ROUTE-TX-001');
    }

    public function test_admin_can_create_selected_batch_for_recipient_and_staff_name_uses_recipient(): void
    {
        $admin = User::factory()->create(['id' => 1, 'name' => 'Admin User']);
        $recipient = User::factory()->create(['name' => 'Recipient User']);
        $this->actingAs($admin);

        $typeId = (int) DB::table('document_types')->insertGetId([
            'name' => 'Memo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $sourceId = (int) DB::table('document_sources')->insertGetId([
            'name' => 'Office A',
            'source_type' => 'OFFICE',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $doc1 = IncomingDocument::create([
            'created_by' => $admin->id,
            'received_by' => $admin->id,
            'document_source_id' => $sourceId,
            'document_type_id' => $typeId,
            'document_reference_number' => 'BATCH-DOC-101',
            'drn' => 'DRN-101',
            'subject' => 'Subject 101',
            'current_status' => 'FORWARDED',
            'date_forwarded' => now(),
        ]);
        $doc2 = IncomingDocument::create([
            'created_by' => $admin->id,
            'received_by' => $admin->id,
            'document_source_id' => $sourceId,
            'document_type_id' => $typeId,
            'document_reference_number' => 'BATCH-DOC-102',
            'drn' => 'DRN-102',
            'subject' => 'Subject 102',
            'current_status' => 'FORWARDED',
            'date_forwarded' => now(),
        ]);

        $now = now();
        $rid1 = (int) DB::table('incoming_document_forward_recipients')->insertGetId([
            'incoming_document_id' => $doc1->id,
            'user_id' => $recipient->id,
            'date_received' => null,
            'received_by' => null,
            'received_in_behalf' => null,
            'batch_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $rid2 = (int) DB::table('incoming_document_forward_recipients')->insertGetId([
            'incoming_document_id' => $doc2->id,
            'user_id' => $recipient->id,
            'date_received' => null,
            'received_by' => null,
            'received_in_behalf' => null,
            'batch_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $resp = $this->postJson(route('inbox.batch.create'), [
            'user_id' => $recipient->id,
            'recipient_ids' => [$rid1, $rid2],
        ]);

        $resp->assertOk();
        $resp->assertJsonPath('success', true);

        $batchId = (int) $resp->json('batch_id');
        $this->assertGreaterThan(0, $batchId);

        $this->assertDatabaseHas('batch_received', [
            'id' => $batchId,
            'batch_staff_name' => $recipient->id.' - '.$recipient->name,
            'created_by' => $admin->id,
        ]);

        $this->assertDatabaseHas('incoming_document_forward_recipients', [
            'id' => $rid1,
            'batch_id' => $batchId,
        ]);
        $this->assertDatabaseHas('incoming_document_forward_recipients', [
            'id' => $rid2,
            'batch_id' => $batchId,
        ]);
    }

    public function test_admin_can_set_and_use_recipient_pin_for_batch_receive(): void
    {
        $admin = User::factory()->create(['id' => 1, 'name' => 'Admin User']);
        $recipient = User::factory()->create(['name' => 'Recipient User']);
        $this->actingAs($admin);

        $setPin = $this->postJson(route('inbox.batch.pin.create'), [
            'user_id' => $recipient->id,
            'pin' => '1234',
            'pin_confirm' => '1234',
        ]);
        $setPin->assertOk();
        $setPin->assertJsonPath('success', true);

        $status = $this->postJson(route('inbox.batch.pin.status'), [
            'user_id' => $recipient->id,
        ]);
        $status->assertOk();
        $status->assertJsonPath('success', true);
        $status->assertJsonPath('has_pin', true);

        $batchId = (int) DB::table('batch_received')->insertGetId([
            'batch_staff_name' => $recipient->id.' - '.$recipient->name,
            'created_by' => $admin->id,
            'date_created' => now(),
            'status' => 0,
        ]);

        $typeId = (int) DB::table('document_types')->insertGetId([
            'name' => 'Memo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $sourceId = (int) DB::table('document_sources')->insertGetId([
            'name' => 'Office A',
            'source_type' => 'OFFICE',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $doc = IncomingDocument::create([
            'created_by' => $admin->id,
            'received_by' => $admin->id,
            'document_source_id' => $sourceId,
            'document_type_id' => $typeId,
            'document_reference_number' => 'BATCH-DOC-201',
            'drn' => 'DRN-201',
            'subject' => 'Subject 201',
            'current_status' => 'FORWARDED',
            'date_forwarded' => now(),
        ]);

        DB::table('incoming_document_forward_recipients')->insert([
            'incoming_document_id' => $doc->id,
            'user_id' => $recipient->id,
            'date_received' => null,
            'received_by' => null,
            'received_in_behalf' => null,
            'batch_id' => $batchId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $receive = $this->postJson(route('inbox.batch.receive', ['batch' => $batchId]), [
            'user_id' => $recipient->id,
            'pin' => '1234',
        ]);
        $receive->assertOk();
        $receive->assertJsonPath('success', true);

        $this->assertDatabaseHas('batch_received', [
            'id' => $batchId,
            'status' => 1,
        ]);

        $this->assertDatabaseHas('batch_received_audits', [
            'batch_id' => $batchId,
            'user_id' => $recipient->id,
        ]);

        $recipientRow = DB::table('incoming_document_forward_recipients')
            ->where('batch_id', $batchId)
            ->where('user_id', $recipient->id)
            ->first(['date_received', 'received_by']);
        $this->assertNotNull($recipientRow);
        $this->assertNotNull($recipientRow->date_received);
        $this->assertSame($recipient->id, (int) $recipientRow->received_by);

        $doc->refresh();
        $this->assertSame('RECEIVED', (string) $doc->current_status);
        $this->assertSame($recipient->id, (int) $doc->received_by);

        $this->assertDatabaseHas('document_logs', [
            'incoming_document_id' => $doc->id,
            'user_id' => $admin->id,
            'action_type' => 'INBOX_RECEIVED',
            'status_to' => 'INBOX_RECEIVED',
            'related_user_id' => $recipient->id,
        ]);
    }

    public function test_user_can_create_pin_and_it_is_hashed(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $resp = $this->postJson(route('inbox.batch.pin.create'), [
            'pin' => '1234',
            'pin_confirm' => '1234',
        ]);

        $resp->assertOk();
        $resp->assertJsonPath('success', true);
        $resp->assertJsonPath('pin', '1234');

        $user->refresh();
        $this->assertNotNull($user->pin_hash);
        $this->assertStringContainsString('$2y$10$', (string) $user->pin_hash);
        $this->assertTrue(Hash::check('1234', (string) $user->pin_hash));
        $this->assertNotNull($user->pin_fingerprint);
    }

    public function test_pin_create_requires_matching_digits(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $resp = $this->postJson(route('inbox.batch.pin.create'), [
            'pin' => '1234',
            'pin_confirm' => '9999',
        ]);

        $resp->assertStatus(422);
        $resp->assertJsonValidationErrors(['pin_confirm']);
    }

    public function test_pin_can_be_shared_between_users(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->actingAs($userA);
        $this->postJson(route('inbox.batch.pin.create'), [
            'pin' => '1234',
            'pin_confirm' => '1234',
        ])->assertOk();

        $this->actingAs($userB);
        $this->postJson(route('inbox.batch.pin.create'), [
            'pin' => '1234',
            'pin_confirm' => '1234',
        ])->assertOk();
    }

    public function test_receive_with_pin_locks_after_three_failed_attempts(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $user->pin_hash = Hash::make('1234', ['rounds' => 10]);
        $user->pin_fingerprint = hash_hmac('sha256', '1234', (string) config('app.key'));
        $user->pin_failed_attempts = 0;
        $user->pin_locked_until = null;
        $user->save();

        $batchId = (int) DB::table('batch_received')->insertGetId([
            'batch_staff_name' => $user->id.' - '.$user->name,
            'created_by' => $user->id,
            'date_created' => now(),
            'status' => 0,
        ]);

        $doc = IncomingDocument::create([
            'created_by' => $user->id,
            'received_by' => $user->id,
            'document_reference_number' => 'BATCH-PIN-LOCK-001',
            'subject' => 'Lock Test',
            'current_status' => 'FORWARDED',
            'date_forwarded' => now(),
        ]);

        DB::table('incoming_document_forward_recipients')->insert([
            'incoming_document_id' => $doc->id,
            'user_id' => $user->id,
            'date_received' => null,
            'received_by' => null,
            'received_in_behalf' => null,
            'batch_id' => $batchId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson(route('inbox.batch.receive', ['batch' => $batchId]), ['pin' => '0000'])->assertStatus(422);
        $this->postJson(route('inbox.batch.receive', ['batch' => $batchId]), ['pin' => '0000'])->assertStatus(422);
        $this->postJson(route('inbox.batch.receive', ['batch' => $batchId]), ['pin' => '0000'])->assertStatus(423);

        $this->postJson(route('inbox.batch.receive', ['batch' => $batchId]), ['pin' => '1234'])->assertStatus(423);

        $this->travel(31)->seconds();

        $ok = $this->postJson(route('inbox.batch.receive', ['batch' => $batchId]), ['pin' => '1234']);
        $ok->assertOk();
        $ok->assertJsonPath('success', true);

        $this->assertDatabaseHas('batch_received', [
            'id' => $batchId,
            'status' => 1,
        ]);
        $this->assertDatabaseHas('batch_received_audits', [
            'batch_id' => $batchId,
            'user_id' => $user->id,
        ]);

        $recipientRow = DB::table('incoming_document_forward_recipients')
            ->where('batch_id', $batchId)
            ->where('user_id', $user->id)
            ->first(['date_received', 'received_by']);
        $this->assertNotNull($recipientRow);
        $this->assertNotNull($recipientRow->date_received);
        $this->assertSame($user->id, (int) $recipientRow->received_by);

        $this->assertDatabaseHas('document_logs', [
            'incoming_document_id' => $doc->id,
            'user_id' => $user->id,
            'action_type' => 'INBOX_RECEIVED',
            'status_to' => 'INBOX_RECEIVED',
            'related_user_id' => $user->id,
        ]);
    }

    public function test_batch_receive_rolls_back_when_document_archived(): void
    {
        $admin = User::factory()->create(['id' => 1, 'name' => 'Admin User']);
        $recipient = User::factory()->create(['name' => 'Recipient User']);
        $this->actingAs($admin);

        $this->postJson(route('inbox.batch.pin.create'), [
            'user_id' => $recipient->id,
            'pin' => '1234',
            'pin_confirm' => '1234',
        ])->assertOk();

        $batchId = (int) DB::table('batch_received')->insertGetId([
            'batch_staff_name' => $recipient->id.' - '.$recipient->name,
            'created_by' => $admin->id,
            'date_created' => now(),
            'status' => 0,
        ]);

        $doc = IncomingDocument::create([
            'created_by' => $admin->id,
            'received_by' => $admin->id,
            'document_reference_number' => 'BATCH-ARCH-001',
            'subject' => 'Archived Doc',
            'current_status' => 'FORWARDED',
            'date_forwarded' => now(),
        ]);

        DB::table('incoming_document_forward_recipients')->insert([
            'incoming_document_id' => $doc->id,
            'user_id' => $recipient->id,
            'date_received' => null,
            'received_by' => null,
            'received_in_behalf' => null,
            'batch_id' => $batchId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $doc->delete();

        $receive = $this->postJson(route('inbox.batch.receive', ['batch' => $batchId]), [
            'user_id' => $recipient->id,
            'pin' => '1234',
        ]);
        $receive->assertStatus(422);

        $this->assertDatabaseHas('batch_received', [
            'id' => $batchId,
            'status' => 0,
        ]);

        $recipientRow = DB::table('incoming_document_forward_recipients')
            ->where('batch_id', $batchId)
            ->where('user_id', $recipient->id)
            ->first(['date_received', 'received_by']);
        $this->assertNotNull($recipientRow);
        $this->assertNull($recipientRow->date_received);
        $this->assertNull($recipientRow->received_by);

        $this->assertDatabaseMissing('document_logs', [
            'incoming_document_id' => $doc->id,
            'action_type' => 'INBOX_RECEIVED',
        ]);
        $this->assertDatabaseMissing('batch_received_audits', [
            'batch_id' => $batchId,
        ]);
    }

    public function test_backfill_recipients_received_fields_from_document(): void
    {
        $receiver = User::factory()->create();
        $doc = IncomingDocument::create([
            'created_by' => $receiver->id,
            'received_by' => $receiver->id,
            'document_reference_number' => 'BF-RECIPIENT-001',
            'subject' => 'Backfill Recipient Test',
            'date_received' => now()->toDateString(),
        ]);

        $recipientId = (int) DB::table('incoming_document_forward_recipients')->insertGetId([
            'incoming_document_id' => $doc->id,
            'user_id' => $receiver->id,
            'date_received' => null,
            'received_by' => null,
            'received_in_behalf' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $deletedDoc = IncomingDocument::create([
            'created_by' => $receiver->id,
            'received_by' => $receiver->id,
            'document_reference_number' => 'BF-RECIPIENT-002',
            'subject' => 'Backfill Recipient Deleted Doc',
            'date_received' => now()->toDateString(),
        ]);
        $deletedDoc->delete();

        $deletedRecipientId = (int) DB::table('incoming_document_forward_recipients')->insertGetId([
            'incoming_document_id' => $deletedDoc->id,
            'user_id' => $receiver->id,
            'date_received' => null,
            'received_by' => null,
            'received_in_behalf' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require base_path('database/migrations/2026_03_16_000007_backfill_incoming_document_forward_recipients_received_fields.php');
        $migration->up();

        $this->assertDatabaseHas('incoming_document_forward_recipients', [
            'id' => $recipientId,
            'received_by' => $receiver->id,
        ]);
        $this->assertDatabaseHas('incoming_document_forward_recipients', [
            'id' => $recipientId,
            'date_received' => \Carbon\Carbon::parse($doc->date_received)->startOfDay()->toDateTimeString(),
        ]);

        $this->assertDatabaseHas('incoming_document_forward_recipients', [
            'id' => $deletedRecipientId,
            'date_received' => null,
            'received_by' => null,
        ]);
    }

    public function test_batch_receive_page_populates_recipient_dropdown_for_non_admin(): void
    {
        $viewer = User::factory()->create(['name' => 'Viewer User', 'level_id' => 2]);
        $other = User::factory()->create(['name' => 'Other User', 'level_id' => 2]);
        $this->actingAs($viewer);

        $resp = $this->get(route('inbox.batch'));
        $resp->assertOk();
        $resp->assertSee('My Inbox');
        $resp->assertSee('name="user_id"', false);
        $resp->assertSee('value="'.$other->id.'"', false);
    }

    public function test_batch_receive_page_excludes_logged_in_user_from_recipient_dropdown(): void
    {
        $viewer = User::factory()->create(['name' => 'Viewer User', 'level_id' => 2]);
        User::factory()->create(['name' => 'Other User', 'level_id' => 2]);
        $this->actingAs($viewer);

        $resp = $this->get(route('inbox.batch'));
        $resp->assertOk();
        $resp->assertDontSee('value="'.$viewer->id.'"', false);
    }

    public function test_batch_receive_page_does_not_403_when_non_admin_targets_other_user(): void
    {
        $viewer = User::factory()->create(['name' => 'Viewer User', 'level_id' => 2]);
        $other = User::factory()->create(['name' => 'Other User', 'level_id' => 2]);
        $this->actingAs($viewer);

        $resp = $this->get(route('inbox.batch', ['user_id' => $other->id]));
        $resp->assertOk();
    }

    public function test_batch_create_does_not_403_when_non_admin_targets_other_user(): void
    {
        $viewer = User::factory()->create(['name' => 'Viewer User', 'level_id' => 2]);
        $recipient = User::factory()->create(['name' => 'Recipient User', 'level_id' => 2]);
        $this->actingAs($viewer);

        $typeId = (int) DB::table('document_types')->insertGetId([
            'name' => 'Memo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $sourceId = (int) DB::table('document_sources')->insertGetId([
            'name' => 'Office A',
            'source_type' => 'OFFICE',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $doc1 = IncomingDocument::create([
            'created_by' => $viewer->id,
            'received_by' => $viewer->id,
            'document_source_id' => $sourceId,
            'document_type_id' => $typeId,
            'document_reference_number' => 'BATCH-DOC-201',
            'drn' => 'DRN-201',
            'subject' => 'Subject 201',
            'current_status' => 'FORWARDED',
            'date_forwarded' => now(),
        ]);

        $now = now();
        $rid = (int) DB::table('incoming_document_forward_recipients')->insertGetId([
            'incoming_document_id' => $doc1->id,
            'user_id' => $recipient->id,
            'date_received' => null,
            'received_by' => null,
            'received_in_behalf' => null,
            'batch_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $resp = $this->postJson(route('inbox.batch.create'), [
            'user_id' => $recipient->id,
            'recipient_ids' => [$rid],
        ]);
        $resp->assertOk();
        $resp->assertJsonPath('success', true);
    }

    public function test_batch_pin_status_does_not_403_when_non_admin_targets_other_user(): void
    {
        $viewer = User::factory()->create(['name' => 'Viewer User', 'level_id' => 2]);
        $recipient = User::factory()->create(['name' => 'Recipient User', 'level_id' => 2]);
        $this->actingAs($viewer);

        $resp = $this->postJson(route('inbox.batch.pin.status'), [
            'user_id' => $recipient->id,
        ]);
        $resp->assertOk();
        $resp->assertJsonPath('success', true);
        $resp->assertJsonStructure(['success', 'has_pin', 'failed_attempts', 'locked_until', 'locked_seconds']);
    }

    public function test_batch_pin_create_does_not_allow_non_admin_to_set_other_user_pin(): void
    {
        $viewer = User::factory()->create(['id' => 2, 'name' => 'Viewer User', 'level_id' => 2]);
        $recipient = User::factory()->create(['id' => 3, 'name' => 'Recipient User', 'level_id' => 2]);
        $this->actingAs($viewer);

        $resp = $this->postJson(route('inbox.batch.pin.create'), [
            'user_id' => $recipient->id,
            'pin' => '1234',
            'pin_confirm' => '1234',
        ]);
        $resp->assertStatus(422);
        $resp->assertJsonValidationErrors(['user_id']);
    }

    public function test_batch_pin_create_targets_batch_recipient_user_id(): void
    {
        $admin = User::factory()->create(['id' => 1, 'name' => 'Admin User']);
        $recipient = User::factory()->create(['name' => 'Recipient User']);
        $this->actingAs($admin);

        $batchId = (int) DB::table('batch_received')->insertGetId([
            'batch_staff_name' => $recipient->id.' - '.$recipient->name,
            'created_by' => $admin->id,
            'date_created' => now(),
            'status' => 0,
        ]);

        $doc = IncomingDocument::create([
            'created_by' => $admin->id,
            'received_by' => $admin->id,
            'document_reference_number' => 'BATCH-PIN-TARGET-001',
            'subject' => 'Target batch pin',
            'current_status' => 'FORWARDED',
            'date_forwarded' => now(),
        ]);

        DB::table('incoming_document_forward_recipients')->insert([
            'incoming_document_id' => $doc->id,
            'user_id' => $recipient->id,
            'date_received' => null,
            'received_by' => null,
            'received_in_behalf' => null,
            'batch_id' => $batchId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $resp = $this->postJson(route('inbox.batch.pin.create'), [
            'batch_id' => $batchId,
            'pin' => '1234',
            'pin_confirm' => '1234',
        ]);
        $resp->assertOk();
        $resp->assertJsonPath('success', true);
        $resp->assertJsonPath('user_id', $recipient->id);

        $recipient->refresh();
        $this->assertNotNull($recipient->pin_hash);
        $this->assertTrue(Hash::check('1234', (string) $recipient->pin_hash));
    }

    public function test_batch_receive_with_pin_does_not_403_when_non_admin_targets_other_user(): void
    {
        $viewer = User::factory()->create(['name' => 'Viewer User', 'level_id' => 2]);
        $recipient = User::factory()->create(['name' => 'Recipient User', 'level_id' => 2]);
        $this->actingAs($viewer);

        $recipient->pin_hash = Hash::make('1234', ['rounds' => 10]);
        $recipient->pin_failed_attempts = 0;
        $recipient->pin_locked_until = null;
        $recipient->save();

        $batchId = (int) DB::table('batch_received')->insertGetId([
            'batch_staff_name' => $recipient->id.' - '.$recipient->name,
            'created_by' => $viewer->id,
            'date_created' => now(),
            'status' => 0,
        ]);

        $doc = IncomingDocument::create([
            'created_by' => $viewer->id,
            'received_by' => $viewer->id,
            'document_reference_number' => 'BATCH-NONADMIN-001',
            'subject' => 'Non-admin batch receive',
            'current_status' => 'FORWARDED',
            'date_forwarded' => now(),
        ]);

        DB::table('incoming_document_forward_recipients')->insert([
            'incoming_document_id' => $doc->id,
            'user_id' => $recipient->id,
            'date_received' => null,
            'received_by' => null,
            'received_in_behalf' => null,
            'batch_id' => $batchId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $receive = $this->postJson(route('inbox.batch.receive', ['batch' => $batchId]), [
            'user_id' => $recipient->id,
            'pin' => '1234',
        ]);
        $receive->assertOk();
        $receive->assertJsonPath('success', true);
    }

    public function test_inbox_active_users_includes_logged_in_user_and_excludes_recipient(): void
    {
        $me = User::factory()->create(['name' => 'Me']);
        $other = User::factory()->create(['name' => 'Other']);
        $this->actingAs($me);

        $resp = $this->getJson(route('inbox.lookups.active-users', [
            'q' => '',
            'page' => 1,
            'exclude_user_id' => $other->id,
        ]));
        $resp->assertOk();

        $ids = collect($resp->json('results') ?: [])->pluck('id')->map(fn ($v) => (int) $v)->all();
        $this->assertContains($me->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    public function test_batch_receive_with_pin_sets_received_in_behalf_when_checked(): void
    {
        $actor = User::factory()->create(['name' => 'Actor User', 'level_id' => 2]);
        $recipient = User::factory()->create(['name' => 'Recipient User', 'level_id' => 2]);
        $behalf = User::factory()->create(['name' => 'Behalf User', 'level_id' => 2]);
        $this->actingAs($actor);

        $recipient->pin_hash = Hash::make('1234', ['rounds' => 10]);
        $recipient->pin_failed_attempts = 0;
        $recipient->pin_locked_until = null;
        $recipient->save();

        $behalf->pin_hash = Hash::make('1234', ['rounds' => 10]);
        $behalf->pin_failed_attempts = 0;
        $behalf->pin_locked_until = null;
        $behalf->save();

        $batchId = (int) DB::table('batch_received')->insertGetId([
            'batch_staff_name' => $recipient->id.' - '.$recipient->name,
            'created_by' => $actor->id,
            'date_created' => now(),
            'status' => 0,
        ]);

        $doc = IncomingDocument::create([
            'created_by' => $actor->id,
            'received_by' => $actor->id,
            'document_reference_number' => 'BATCH-BEHALF-001',
            'subject' => 'Behalf batch receive',
            'current_status' => 'FORWARDED',
            'date_forwarded' => now(),
        ]);

        DB::table('incoming_document_forward_recipients')->insert([
            'incoming_document_id' => $doc->id,
            'user_id' => $recipient->id,
            'date_received' => null,
            'received_by' => null,
            'received_in_behalf' => null,
            'batch_id' => $batchId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $receive = $this->postJson(route('inbox.batch.receive', ['batch' => $batchId]), [
            'pin' => '1234',
            'received_in_behalf' => 1,
            'received_in_behalf_user_id' => $behalf->id,
        ]);
        $receive->assertOk();
        $receive->assertJsonPath('success', true);

        $row = DB::table('incoming_document_forward_recipients')->where('batch_id', $batchId)->first();
        $this->assertSame($behalf->id, (int) ($row->received_in_behalf ?? 0));
    }

    public function test_batch_receive_with_pin_sets_received_in_behalf_null_when_unchecked(): void
    {
        $actor = User::factory()->create(['name' => 'Actor User', 'level_id' => 2]);
        $recipient = User::factory()->create(['name' => 'Recipient User', 'level_id' => 2]);
        $behalf = User::factory()->create(['name' => 'Behalf User', 'level_id' => 2]);
        $this->actingAs($actor);

        $recipient->pin_hash = Hash::make('1234', ['rounds' => 10]);
        $recipient->pin_failed_attempts = 0;
        $recipient->pin_locked_until = null;
        $recipient->save();

        $batchId = (int) DB::table('batch_received')->insertGetId([
            'batch_staff_name' => $recipient->id.' - '.$recipient->name,
            'created_by' => $actor->id,
            'date_created' => now(),
            'status' => 0,
        ]);

        $doc = IncomingDocument::create([
            'created_by' => $actor->id,
            'received_by' => $actor->id,
            'document_reference_number' => 'BATCH-BEHALF-002',
            'subject' => 'Behalf batch receive null',
            'current_status' => 'FORWARDED',
            'date_forwarded' => now(),
        ]);

        DB::table('incoming_document_forward_recipients')->insert([
            'incoming_document_id' => $doc->id,
            'user_id' => $recipient->id,
            'date_received' => null,
            'received_by' => null,
            'received_in_behalf' => null,
            'batch_id' => $batchId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $receive = $this->postJson(route('inbox.batch.receive', ['batch' => $batchId]), [
            'pin' => '1234',
            'received_in_behalf' => 0,
            'received_in_behalf_user_id' => $behalf->id,
        ]);
        $receive->assertOk();
        $receive->assertJsonPath('success', true);

        $row = DB::table('incoming_document_forward_recipients')->where('batch_id', $batchId)->first();
        $this->assertNull($row->received_in_behalf);
    }

    public function test_batch_receive_with_pin_requires_staff_when_received_in_behalf_checked(): void
    {
        $actor = User::factory()->create(['name' => 'Actor User', 'level_id' => 2]);
        $recipient = User::factory()->create(['name' => 'Recipient User', 'level_id' => 2]);
        $this->actingAs($actor);

        $recipient->pin_hash = Hash::make('1234', ['rounds' => 10]);
        $recipient->pin_failed_attempts = 0;
        $recipient->pin_locked_until = null;
        $recipient->save();

        $batchId = (int) DB::table('batch_received')->insertGetId([
            'batch_staff_name' => $recipient->id.' - '.$recipient->name,
            'created_by' => $actor->id,
            'date_created' => now(),
            'status' => 0,
        ]);

        $doc = IncomingDocument::create([
            'created_by' => $actor->id,
            'received_by' => $actor->id,
            'document_reference_number' => 'BATCH-BEHALF-003',
            'subject' => 'Behalf batch receive validation',
            'current_status' => 'FORWARDED',
            'date_forwarded' => now(),
        ]);

        DB::table('incoming_document_forward_recipients')->insert([
            'incoming_document_id' => $doc->id,
            'user_id' => $recipient->id,
            'date_received' => null,
            'received_by' => null,
            'received_in_behalf' => null,
            'batch_id' => $batchId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $receive = $this->postJson(route('inbox.batch.receive', ['batch' => $batchId]), [
            'pin' => '1234',
            'received_in_behalf' => 1,
        ]);
        $receive->assertStatus(422);
        $receive->assertJsonValidationErrors(['received_in_behalf_user_id']);
    }

    public function test_batch_receive_with_pin_rejects_selecting_recipient_as_received_in_behalf_staff(): void
    {
        $actor = User::factory()->create(['name' => 'Actor User', 'level_id' => 2]);
        $recipient = User::factory()->create(['name' => 'Recipient User', 'level_id' => 2]);
        $this->actingAs($actor);

        $recipient->pin_hash = Hash::make('1234', ['rounds' => 10]);
        $recipient->pin_failed_attempts = 0;
        $recipient->pin_locked_until = null;
        $recipient->save();

        $batchId = (int) DB::table('batch_received')->insertGetId([
            'batch_staff_name' => $recipient->id.' - '.$recipient->name,
            'created_by' => $actor->id,
            'date_created' => now(),
            'status' => 0,
        ]);

        $doc = IncomingDocument::create([
            'created_by' => $actor->id,
            'received_by' => $actor->id,
            'document_reference_number' => 'BATCH-BEHALF-RECIPIENT-001',
            'subject' => 'Behalf recipient validation',
            'current_status' => 'FORWARDED',
            'date_forwarded' => now(),
        ]);

        DB::table('incoming_document_forward_recipients')->insert([
            'incoming_document_id' => $doc->id,
            'user_id' => $recipient->id,
            'date_received' => null,
            'received_by' => null,
            'received_in_behalf' => null,
            'batch_id' => $batchId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $receive = $this->postJson(route('inbox.batch.receive', ['batch' => $batchId]), [
            'pin' => '1234',
            'received_in_behalf' => 1,
            'received_in_behalf_user_id' => $recipient->id,
        ]);
        $receive->assertStatus(422);
        $receive->assertJsonValidationErrors(['received_in_behalf_user_id']);
    }

    public function test_batch_receive_with_pin_uses_selected_staff_pin_when_received_in_behalf_checked(): void
    {
        $actor = User::factory()->create(['name' => 'Actor User', 'level_id' => 2]);
        $recipient = User::factory()->create(['name' => 'Recipient User', 'level_id' => 2]);
        $behalf = User::factory()->create(['name' => 'Behalf User', 'level_id' => 2]);
        $this->actingAs($actor);

        $recipient->pin_hash = Hash::make('1111', ['rounds' => 10]);
        $recipient->pin_failed_attempts = 0;
        $recipient->pin_locked_until = null;
        $recipient->save();

        $behalf->pin_hash = Hash::make('2222', ['rounds' => 10]);
        $behalf->pin_failed_attempts = 0;
        $behalf->pin_locked_until = null;
        $behalf->save();

        $batchId = (int) DB::table('batch_received')->insertGetId([
            'batch_staff_name' => $recipient->id.' - '.$recipient->name,
            'created_by' => $actor->id,
            'date_created' => now(),
            'status' => 0,
        ]);

        $doc = IncomingDocument::create([
            'created_by' => $actor->id,
            'received_by' => $actor->id,
            'document_reference_number' => 'BATCH-PINSRC-001',
            'subject' => 'PIN source behalf',
            'current_status' => 'FORWARDED',
            'date_forwarded' => now(),
        ]);

        DB::table('incoming_document_forward_recipients')->insert([
            'incoming_document_id' => $doc->id,
            'user_id' => $recipient->id,
            'date_received' => null,
            'received_by' => null,
            'received_in_behalf' => null,
            'batch_id' => $batchId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $receive = $this->postJson(route('inbox.batch.receive', ['batch' => $batchId]), [
            'pin' => '2222',
            'received_in_behalf' => 1,
            'received_in_behalf_user_id' => $behalf->id,
        ]);
        $receive->assertOk();
        $receive->assertJsonPath('success', true);
    }

    public function test_batch_receive_with_pin_uses_recipient_pin_when_received_in_behalf_unchecked(): void
    {
        $actor = User::factory()->create(['name' => 'Actor User', 'level_id' => 2]);
        $recipient = User::factory()->create(['name' => 'Recipient User', 'level_id' => 2]);
        $behalf = User::factory()->create(['name' => 'Behalf User', 'level_id' => 2]);
        $this->actingAs($actor);

        $recipient->pin_hash = Hash::make('1111', ['rounds' => 10]);
        $recipient->pin_failed_attempts = 0;
        $recipient->pin_locked_until = null;
        $recipient->save();

        $behalf->pin_hash = Hash::make('2222', ['rounds' => 10]);
        $behalf->pin_failed_attempts = 0;
        $behalf->pin_locked_until = null;
        $behalf->save();

        $batchId = (int) DB::table('batch_received')->insertGetId([
            'batch_staff_name' => $recipient->id.' - '.$recipient->name,
            'created_by' => $actor->id,
            'date_created' => now(),
            'status' => 0,
        ]);

        $doc = IncomingDocument::create([
            'created_by' => $actor->id,
            'received_by' => $actor->id,
            'document_reference_number' => 'BATCH-PINSRC-002',
            'subject' => 'PIN source recipient',
            'current_status' => 'FORWARDED',
            'date_forwarded' => now(),
        ]);

        DB::table('incoming_document_forward_recipients')->insert([
            'incoming_document_id' => $doc->id,
            'user_id' => $recipient->id,
            'date_received' => null,
            'received_by' => null,
            'received_in_behalf' => null,
            'batch_id' => $batchId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $receive = $this->postJson(route('inbox.batch.receive', ['batch' => $batchId]), [
            'pin' => '2222',
            'received_in_behalf' => 0,
            'received_in_behalf_user_id' => $behalf->id,
        ]);
        $receive->assertStatus(422);
        $receive->assertJsonPath('message', 'Incorrect PIN.');
    }

    public function test_batch_receive_with_pin_creates_pin_for_selected_staff_when_missing_and_admin(): void
    {
        $admin = User::factory()->create(['name' => 'Admin User', 'level_id' => 1]);
        $recipient = User::factory()->create(['name' => 'Recipient User', 'level_id' => 2]);
        $behalf = User::factory()->create(['name' => 'Behalf User', 'level_id' => 2]);
        $this->actingAs($admin);

        $recipient->pin_hash = Hash::make('1111', ['rounds' => 10]);
        $recipient->pin_failed_attempts = 0;
        $recipient->pin_locked_until = null;
        $recipient->save();

        $behalf->pin_hash = null;
        $behalf->pin_failed_attempts = 0;
        $behalf->pin_locked_until = null;
        $behalf->save();

        $batchId = (int) DB::table('batch_received')->insertGetId([
            'batch_staff_name' => $recipient->id.' - '.$recipient->name,
            'created_by' => $admin->id,
            'date_created' => now(),
            'status' => 0,
        ]);

        $doc = IncomingDocument::create([
            'created_by' => $admin->id,
            'received_by' => $admin->id,
            'document_reference_number' => 'BATCH-PINSRC-003',
            'subject' => 'PIN create behalf',
            'current_status' => 'FORWARDED',
            'date_forwarded' => now(),
        ]);

        DB::table('incoming_document_forward_recipients')->insert([
            'incoming_document_id' => $doc->id,
            'user_id' => $recipient->id,
            'date_received' => null,
            'received_by' => null,
            'received_in_behalf' => null,
            'batch_id' => $batchId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $receive = $this->postJson(route('inbox.batch.receive', ['batch' => $batchId]), [
            'pin' => '9999',
            'received_in_behalf' => 1,
            'received_in_behalf_user_id' => $behalf->id,
        ]);
        $receive->assertOk();
        $receive->assertJsonPath('success', true);

        $behalf->refresh();
        $this->assertNotNull($behalf->pin_hash);
        $this->assertTrue(Hash::check('9999', (string) $behalf->pin_hash));
    }

    public function test_batch_receive_with_pin_rejects_selected_staff_without_pin_when_not_admin(): void
    {
        $actor = User::factory()->create(['name' => 'Actor User', 'level_id' => 2]);
        $recipient = User::factory()->create(['name' => 'Recipient User', 'level_id' => 2]);
        $behalf = User::factory()->create(['name' => 'Behalf User', 'level_id' => 2]);
        $this->actingAs($actor);

        $recipient->pin_hash = Hash::make('1111', ['rounds' => 10]);
        $recipient->pin_failed_attempts = 0;
        $recipient->pin_locked_until = null;
        $recipient->save();

        $behalf->pin_hash = null;
        $behalf->pin_failed_attempts = 0;
        $behalf->pin_locked_until = null;
        $behalf->save();

        $batchId = (int) DB::table('batch_received')->insertGetId([
            'batch_staff_name' => $recipient->id.' - '.$recipient->name,
            'created_by' => $actor->id,
            'date_created' => now(),
            'status' => 0,
        ]);

        $doc = IncomingDocument::create([
            'created_by' => $actor->id,
            'received_by' => $actor->id,
            'document_reference_number' => 'BATCH-PINSRC-004',
            'subject' => 'PIN reject behalf',
            'current_status' => 'FORWARDED',
            'date_forwarded' => now(),
        ]);

        DB::table('incoming_document_forward_recipients')->insert([
            'incoming_document_id' => $doc->id,
            'user_id' => $recipient->id,
            'date_received' => null,
            'received_by' => null,
            'received_in_behalf' => null,
            'batch_id' => $batchId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $receive = $this->postJson(route('inbox.batch.receive', ['batch' => $batchId]), [
            'pin' => '9999',
            'received_in_behalf' => 1,
            'received_in_behalf_user_id' => $behalf->id,
        ]);
        $receive->assertStatus(422);
        $receive->assertJsonValidationErrors(['received_in_behalf_user_id']);
    }

    public function test_inbox_pin_create_allows_admin_to_set_other_user_pin(): void
    {
        $admin = User::factory()->create(['name' => 'Admin User', 'level_id' => 1]);
        $target = User::factory()->create(['name' => 'Target User', 'level_id' => 2]);
        $this->actingAs($admin);

        $resp = $this->postJson(route('inbox.pin.create'), [
            'user_id' => $target->id,
            'pin' => '1234',
            'pin_confirm' => '1234',
        ]);
        $resp->assertOk();
        $resp->assertJsonPath('success', true);

        $target->refresh();
        $this->assertNotNull($target->pin_hash);
        $this->assertTrue(Hash::check('1234', (string) $target->pin_hash));
    }

    public function test_inbox_pin_create_rejects_non_admin_setting_other_user_pin(): void
    {
        $actor = User::factory()->create(['name' => 'Actor User', 'level_id' => 2]);
        $target = User::factory()->create(['name' => 'Target User', 'level_id' => 2]);
        $this->actingAs($actor);

        $resp = $this->postJson(route('inbox.pin.create'), [
            'user_id' => $target->id,
            'pin' => '1234',
            'pin_confirm' => '1234',
        ]);
        $resp->assertStatus(422);
        $resp->assertJsonValidationErrors(['user_id']);
    }

    public function test_inbox_batch_pin_reset_resets_selected_staff_when_checked(): void
    {
        $admin = User::factory()->create(['name' => 'Admin User', 'level_id' => 1]);
        $recipient = User::factory()->create(['name' => 'Recipient User', 'level_id' => 2]);
        $behalf = User::factory()->create(['name' => 'Behalf User', 'level_id' => 2]);
        $this->actingAs($admin);

        $recipient->pin_hash = Hash::make('1111', ['rounds' => 10]);
        $recipient->pin_failed_attempts = 0;
        $recipient->pin_locked_until = null;
        $recipient->save();

        $behalf->pin_hash = Hash::make('2222', ['rounds' => 10]);
        $behalf->pin_failed_attempts = 0;
        $behalf->pin_locked_until = null;
        $behalf->save();

        $batchId = (int) DB::table('batch_received')->insertGetId([
            'batch_staff_name' => $recipient->id.' - '.$recipient->name,
            'created_by' => $admin->id,
            'date_created' => now(),
            'status' => 0,
        ]);

        $resp = $this->postJson(route('inbox.batch.pin.reset'), [
            'batch_id' => $batchId,
            'received_in_behalf' => 1,
            'received_in_behalf_user_id' => $behalf->id,
        ]);
        $resp->assertOk();
        $resp->assertJsonPath('success', true);

        $behalf->refresh();
        $this->assertNull($behalf->pin_hash);
    }

    public function test_inbox_batch_pin_reset_resets_recipient_when_unchecked(): void
    {
        $admin = User::factory()->create(['name' => 'Admin User', 'level_id' => 1]);
        $recipient = User::factory()->create(['name' => 'Recipient User', 'level_id' => 2]);
        $this->actingAs($admin);

        $recipient->pin_hash = Hash::make('1111', ['rounds' => 10]);
        $recipient->pin_failed_attempts = 0;
        $recipient->pin_locked_until = null;
        $recipient->save();

        $batchId = (int) DB::table('batch_received')->insertGetId([
            'batch_staff_name' => $recipient->id.' - '.$recipient->name,
            'created_by' => $admin->id,
            'date_created' => now(),
            'status' => 0,
        ]);

        $resp = $this->postJson(route('inbox.batch.pin.reset'), [
            'batch_id' => $batchId,
            'received_in_behalf' => 0,
        ]);
        $resp->assertOk();
        $resp->assertJsonPath('success', true);

        $recipient->refresh();
        $this->assertNull($recipient->pin_hash);
    }

    public function test_inbox_batch_pin_reset_rejects_non_admin_resetting_other_user(): void
    {
        $actor = User::factory()->create(['name' => 'Actor User', 'level_id' => 2]);
        $recipient = User::factory()->create(['name' => 'Recipient User', 'level_id' => 2]);
        $this->actingAs($actor);

        $recipient->pin_hash = Hash::make('1111', ['rounds' => 10]);
        $recipient->pin_failed_attempts = 0;
        $recipient->pin_locked_until = null;
        $recipient->save();

        $batchId = (int) DB::table('batch_received')->insertGetId([
            'batch_staff_name' => $recipient->id.' - '.$recipient->name,
            'created_by' => $actor->id,
            'date_created' => now(),
            'status' => 0,
        ]);

        $resp = $this->postJson(route('inbox.batch.pin.reset'), [
            'batch_id' => $batchId,
            'received_in_behalf' => 0,
        ]);
        $resp->assertStatus(422);
        $resp->assertJsonValidationErrors(['user_id']);
    }

    public function test_inbox_batch_pin_reset_uses_user_id_fallback_when_batch_recipient_ambiguous(): void
    {
        $admin = User::factory()->create(['name' => 'Admin User', 'level_id' => 1]);
        $u1 = User::factory()->create(['name' => 'Recipient A', 'level_id' => 2]);
        $u2 = User::factory()->create(['name' => 'Recipient B', 'level_id' => 2]);
        $this->actingAs($admin);

        $u1->pin_hash = Hash::make('1111', ['rounds' => 10]);
        $u1->pin_failed_attempts = 0;
        $u1->pin_locked_until = null;
        $u1->save();

        $u2->pin_hash = Hash::make('2222', ['rounds' => 10]);
        $u2->pin_failed_attempts = 0;
        $u2->pin_locked_until = null;
        $u2->save();

        $batchId = (int) DB::table('batch_received')->insertGetId([
            'batch_staff_name' => $u1->id.' - '.$u1->name,
            'created_by' => $admin->id,
            'date_created' => now(),
            'status' => 0,
        ]);

        $doc = IncomingDocument::create([
            'created_by' => $admin->id,
            'received_by' => $admin->id,
            'document_reference_number' => 'BATCH-AMBIG-001',
            'subject' => 'Ambiguous batch',
            'current_status' => 'FORWARDED',
            'date_forwarded' => now(),
        ]);

        DB::table('incoming_document_forward_recipients')->insert([
            [
                'incoming_document_id' => $doc->id,
                'user_id' => $u1->id,
                'date_received' => null,
                'received_by' => null,
                'received_in_behalf' => null,
                'batch_id' => $batchId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'incoming_document_id' => $doc->id,
                'user_id' => $u2->id,
                'date_received' => null,
                'received_by' => null,
                'received_in_behalf' => null,
                'batch_id' => $batchId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $resp = $this->postJson(route('inbox.batch.pin.reset'), [
            'batch_id' => $batchId,
            'user_id' => $u2->id,
            'received_in_behalf' => 0,
        ]);
        $resp->assertOk();
        $resp->assertJsonPath('success', true);

        $u2->refresh();
        $this->assertNull($u2->pin_hash);
        $u1->refresh();
        $this->assertNotNull($u1->pin_hash);
    }
}
