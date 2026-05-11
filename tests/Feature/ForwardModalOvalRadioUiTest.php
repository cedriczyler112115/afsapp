<?php

namespace Tests\Feature;

use App\Models\IncomingDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForwardModalOvalRadioUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_forward_modal_uses_oval_radio_groups_for_forward_target_and_group_routing(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $doc = IncomingDocument::create([
            'created_by' => $user->id,
            'subject' => 'Test subject',
        ]);

        $resp = $this->get(route('incoming-documents.show', $doc));
        $resp->assertOk();

        $resp->assertSee('name="forward_to"', false);
        $resp->assertSee('name="group_target_mode"', false);
        $resp->assertSee('oval-radio-group', false);
        $resp->assertSee('oval-radio-input', false);
        $resp->assertSee('oval-card', false);
    }
}

