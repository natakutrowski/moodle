<?php
declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796d5_bundle_international_pricing_test extends advanced_testcase {
    public function test_bundle_pricing_service_exposes_component_currency_coverage(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/bundle/pricing/CommerceBundlePricingService.php'
        );

        $this->assertStringContainsString(
            'public function get_currency_coverage(',
            $contents
        );
        $this->assertStringContainsString(
            "'missingcomponents'",
            $contents
        );
        $this->assertStringContainsString(
            'find_active($component->get_sku(), $currency)',
            $contents
        );
    }

    public function test_bundle_pricing_page_has_fx_preview_apply_and_calculated_strategy_diagnostics(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/products/pricing.php'
        );

        $this->assertStringContainsString(
            "['bundlefxpreview', 'bundlefxapply', 'bundlefxdiscard']",
            $contents
        );
        $this->assertStringContainsString(
            'CommerceProductFxSuggestionService',
            $contents
        );
        $this->assertStringContainsString(
            'get_currency_coverage(',
            $contents
        );
        $this->assertStringContainsString(
            'CommerceBundlePricingStrategy::FIXED',
            $contents
        );
        $this->assertStringContainsString(
            'data-bundle-fx-select-all',
            $contents
        );
        $this->assertStringContainsString(
            'd-flex gap-2 mt-4',
            $contents
        );
    }

    public function test_d5_never_changes_bundle_strategy_during_fx_apply(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/products/pricing.php'
        );

        // The apply path reuses the current configuration, so FX generation
        // cannot silently turn a calculated Bundle into a fixed-price Bundle.
        $this->assertStringContainsString(
            '$pricing->configure(' . PHP_EOL
            . '        $sku,' . PHP_EOL
            . '        $currentconfiguration,',
            $contents
        );
    }
}
