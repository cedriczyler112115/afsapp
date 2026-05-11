<?php

namespace Tests\Feature;

use App\Models\IncomingDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncomingDocumentsMonthlyReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_report_respects_filters(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        IncomingDocument::create([
            'created_by' => $user->id,
            'received_by' => $user->id,
            'document_reference_number' => 'REF-IN-001',
            'transaction_type' => 1,
            'current_status' => 'RECEIVED',
            'date_received' => '2026-03-05',
            'subject' => 'Incoming A',
        ]);

        IncomingDocument::create([
            'created_by' => $user->id,
            'received_by' => $user->id,
            'document_reference_number' => 'REF-OUT-001',
            'transaction_type' => 2,
            'current_status' => 'RECEIVED',
            'date_received' => '2026-03-05',
            'subject' => 'Outgoing B',
        ]);

        IncomingDocument::create([
            'created_by' => $user->id,
            'received_by' => $user->id,
            'document_reference_number' => 'REF-IN-ARCH-001',
            'transaction_type' => 1,
            'current_status' => 'ARCHIVED',
            'date_received' => '2026-03-05',
            'subject' => 'Incoming C',
        ]);

        $resp = $this->get(route('incoming-documents.monthly-report', [
            'date_from' => '2026-03-01',
            'date_to' => '2026-03-31',
            'transaction_type' => 1,
            'status' => 'RECEIVED',
        ]));

        $resp->assertOk();
        $resp->assertSee('Monthly Report');
        $resp->assertSee('Document Type');
        $resp->assertSee('Count');
        $resp->assertSee('Unspecified');
        $resp->assertDontSee('REF-OUT-001');
        $resp->assertDontSee('REF-IN-ARCH-001');
    }

    public function test_monthly_report_includes_print_stylesheet_rules(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $resp = $this->get(route('incoming-documents.monthly-report'));

        $resp->assertOk();
        $resp->assertSee('@media print', false);
        $resp->assertSee('.screen-only', false);
        $resp->assertSee('display: none', false);
    }
}
