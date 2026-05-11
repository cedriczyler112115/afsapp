<?php

namespace Tests\Feature;

use App\Models\IncomingDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TrackingDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_tracking_dashboard_requires_authentication(): void
    {
        $resp = $this->get(route('tracking-dashboard.index'));
        $resp->assertStatus(302);
    }

    public function test_tracking_dashboard_index_renders(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $resp = $this->get(route('tracking-dashboard.index'));
        $resp->assertOk();
        $resp->assertSee('Tracking Dashboard');
    }

    public function test_tracking_dashboard_data_returns_json_and_rows(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $typeId = (int) DB::table('document_types')->insertGetId([
            'name' => 'Memo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        IncomingDocument::create([
            'created_by' => $user->id,
            'received_by' => $user->id,
            'document_type_id' => $typeId,
            'document_reference_number' => 'TD-001',
            'drn' => 'DRN-TD-001',
            'subject' => 'Tracking Dashboard Subject',
            'transaction_type' => 1,
            'current_status' => 'RECEIVED',
            'date_received' => now()->toDateString(),
        ]);

        $resp = $this->getJson(route('tracking-dashboard.data'));
        $resp->assertOk();
        $resp->assertJsonPath('success', true);
        $this->assertSame(1, (int) $resp->json('meta.total'));
        $this->assertCount(1, $resp->json('rows'));
        $this->assertSame('TD-001', $resp->json('rows.0.document_reference_number'));
    }

    public function test_tracking_dashboard_rbac_limits_non_admin_to_related_documents(): void
    {
        User::factory()->create(['id' => 1, 'name' => 'Admin User']);
        $viewer = User::factory()->create(['id' => 2, 'level_id' => 2]);
        $other = User::factory()->create(['id' => 3, 'level_id' => 2]);
        $this->actingAs($viewer);

        IncomingDocument::create([
            'created_by' => $viewer->id,
            'received_by' => $viewer->id,
            'document_reference_number' => 'TD-VIEW-001',
            'subject' => 'Viewer Doc',
            'transaction_type' => 1,
            'current_status' => 'RECEIVED',
        ]);

        IncomingDocument::create([
            'created_by' => $other->id,
            'received_by' => $other->id,
            'document_reference_number' => 'TD-OTHER-001',
            'subject' => 'Other Doc',
            'transaction_type' => 1,
            'current_status' => 'RECEIVED',
        ]);

        $resp = $this->getJson(route('tracking-dashboard.data'));
        $resp->assertOk();
        $resp->assertJsonPath('success', true);
        $this->assertSame(1, (int) $resp->json('meta.total'));
        $this->assertSame('TD-VIEW-001', $resp->json('rows.0.document_reference_number'));
    }

    public function test_tracking_dashboard_csv_export_downloads(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        IncomingDocument::create([
            'created_by' => $user->id,
            'received_by' => $user->id,
            'document_reference_number' => 'TD-CSV-001',
            'subject' => 'CSV Doc',
            'transaction_type' => 1,
            'current_status' => 'RECEIVED',
        ]);

        $resp = $this->get(route('tracking-dashboard.export.csv'));
        $resp->assertOk();
        $resp->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $csv = $resp->streamedContent();
        $this->assertStringContainsString('Document Reference Number', $csv);
        $this->assertStringContainsString('TD-CSV-001', $csv);
    }
}
