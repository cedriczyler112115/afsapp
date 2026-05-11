<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddNewDocumentTypeAndSourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_type_store_returns_data_payload(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $resp = $this->postJson(route('document-types.store'), [
            'name' => 'Memo',
            'is_active' => '1',
        ]);

        $resp->assertOk();
        $resp->assertJsonPath('success', 'Document Type created successfully.');
        $this->assertSame('Memo', (string) $resp->json('data.name'));
        $this->assertNotEmpty($resp->json('data.id'));

        $this->assertDatabaseHas('document_types', ['name' => 'Memo']);
    }

    public function test_document_type_store_validation_error(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $resp = $this->postJson(route('document-types.store'), [
            'name' => '',
            'is_active' => '1',
        ]);

        $resp->assertStatus(422);
        $resp->assertJsonValidationErrors(['name']);
    }

    public function test_document_source_store_returns_data_payload(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $resp = $this->postJson(route('document-sources.store'), [
            'source_type' => 'section',
            'name' => 'Records',
            'is_active' => '1',
        ]);

        $resp->assertOk();
        $resp->assertJsonPath('success', 'Document Source created successfully.');
        $this->assertSame('section', (string) $resp->json('data.source_type'));
        $this->assertSame('Records', (string) $resp->json('data.name'));
        $this->assertNotEmpty($resp->json('data.id'));

        $this->assertDatabaseHas('document_sources', [
            'source_type' => 'section',
            'name' => 'Records',
        ]);
    }

    public function test_document_source_store_validation_error(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $resp = $this->postJson(route('document-sources.store'), [
            'source_type' => 'section',
            'name' => '',
            'is_active' => '1',
        ]);

        $resp->assertStatus(422);
        $resp->assertJsonValidationErrors(['name']);
    }

    public function test_incoming_documents_create_contains_add_new_hooks(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $resp = $this->get(route('incoming-documents.create'));
        $resp->assertOk();

        $resp->assertSee('select2-add-new-type', false);
        $resp->assertSee('select2-add-new-source', false);
        $resp->assertSee('bi bi-plus-circle', false);
    }

    public function test_document_sources_empty_table_contains_add_new_button(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $resp = $this->get(route('document-sources.index', ['search' => 'zzz', 'per_page' => 10]), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $resp->assertOk();
        $resp->assertSee('js-add-new-document-source', false);
        $resp->assertSee('Add New', false);
        $resp->assertSee('bi bi-plus-circle', false);
    }
}
