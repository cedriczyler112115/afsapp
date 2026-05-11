<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForwardModalGroupSelect2Test extends TestCase
{
    use RefreshDatabase;

    public function test_group_store_returns_data_payload_for_ajax(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $resp = $this->postJson(route('groups.store'), [
            'group_name' => 'QA Team',
            'status' => 1,
        ]);

        $resp->assertOk();
        $resp->assertJsonPath('success', 'Group created successfully.');
        $this->assertSame('QA Team', (string) $resp->json('data.group_name'));
        $this->assertNotEmpty($resp->json('data.id'));

        $this->assertDatabaseHas('group', ['group_name' => 'QA Team']);
    }

    public function test_group_store_validation_error_for_ajax(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $resp = $this->postJson(route('groups.store'), [
            'group_name' => '',
            'status' => 1,
        ]);

        $resp->assertStatus(422);
        $resp->assertJsonValidationErrors(['group_name']);
    }

    public function test_group_lookup_endpoint_includes_active_groups(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Group::create([
            'group_name' => 'Operations',
            'status' => 1,
            'created_by' => $user->id,
            'date_created' => now(),
        ]);

        $resp = $this->getJson(route('incoming-documents.lookups.groups'));

        $resp->assertOk();
        $resp->assertJsonPath('success', true);
        $this->assertTrue(collect($resp->json('groups'))->contains(fn ($g) => ($g['name'] ?? null) === 'Operations'));
    }
}

