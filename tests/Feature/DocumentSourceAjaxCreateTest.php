<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentSourceAjaxCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_source_store_returns_created_payload(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $resp = $this->postJson(route('document-sources.store'), [
            'source_type' => 'section',
            'name' => 'Records',
            'is_active' => '1',
        ]);

        $resp->assertOk();
        $resp->assertJsonStructure([
            'success',
            'data' => ['id', 'source_type', 'name', 'is_active'],
        ]);
        $this->assertSame('section', (string) $resp->json('data.source_type'));
        $this->assertSame('Records', (string) $resp->json('data.name'));
        $this->assertSame(1, (int) $resp->json('data.is_active'));
    }
}
