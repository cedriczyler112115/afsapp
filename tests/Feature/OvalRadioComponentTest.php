<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\View;
use Tests\TestCase;

class OvalRadioComponentTest extends TestCase
{
    public function test_component_renders_checked_and_aria_attributes(): void
    {
        $html = View::file(base_path('resources/views/components/oval-radio-group.blade.php'), [
            'name' => 'transaction_type',
            'options' => [
                ['value' => '', 'label' => 'All'],
                ['value' => 'INCOMING', 'label' => 'Incoming'],
                ['value' => 'OUTGOING', 'label' => 'Outgoing'],
            ],
            'value' => 'OUTGOING',
            'ariaLabel' => 'Transaction Type',
            'idPrefix' => 'test-oval',
        ])->render();

        $this->assertStringContainsString('role="radiogroup"', $html);
        $this->assertStringContainsString('aria-label="Transaction Type"', $html);
        $this->assertStringContainsString('name="transaction_type"', $html);

        // Checked radio should be OUTGOING
        $this->assertStringContainsString('id="test-oval-outgoing"', $html);
        $this->assertStringContainsString('value="OUTGOING"', $html);
        $this->assertStringContainsString('checked', $html);

        // aria-checked true on the corresponding label
        $this->assertStringContainsString('for="test-oval-outgoing"', $html);
        $this->assertStringContainsString('role="radio"', $html);
        $this->assertStringContainsString('aria-checked="true"', $html);
    }

    public function test_component_includes_styles_and_focus_checked_rules(): void
    {
        $html = View::file(base_path('resources/views/components/oval-radio-group.blade.php'), [
            'name' => 'tx',
            'options' => [['value' => '', 'label' => 'All']],
            'value' => '',
            'ariaLabel' => 'Tx',
            'idPrefix' => 'test-oval2',
        ])->render();

        // Ensure key CSS for the oval cards is present
        $this->assertStringContainsString('border-radius: 9999px', $html);
        $this->assertStringContainsString('box-shadow', $html);
        $this->assertStringContainsString('transition:', $html);

        // Ensure checked and focus selectors exist
        $this->assertStringContainsString('.oval-radio-input:checked + .oval-card', $html);
        $this->assertStringContainsString('.oval-radio-input:focus + .oval-card', $html);
    }

    public function test_component_responsive_rules_present(): void
    {
        $html = View::file(base_path('resources/views/components/oval-radio-group.blade.php'), [
            'name' => 'tx',
            'options' => [['value' => '', 'label' => 'All']],
            'value' => '',
            'ariaLabel' => 'Tx',
            'idPrefix' => 'test-oval3',
        ])->render();

        $this->assertStringContainsString('@media (max-width: 576px)', $html);
    }
}
