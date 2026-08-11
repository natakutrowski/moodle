<?php
// This file is part of Moodle - http://moodle.org/.

defined('MOODLE_INTERNAL') || die();

/**
 * Certification checks for the shared Commerce UI language.
 *
 * @package    local_subscriptions
 * @category   test
 */
final class commerce_ui_uniformisation_j15h4_test extends advanced_testcase {
    /**
     * The shared stylesheet must expose the certified UI tokens and components.
     */
    public function test_shared_commerce_ui_contract_is_present(): void {
        global $CFG;

        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles.css');

        $this->assertIsString($css);
        $this->assertStringContainsString('CampusFR J15H.4 — unified Commerce UI language', $css);
        $this->assertStringContainsString('--commerce-ui-radius-card', $css);
        $this->assertStringContainsString('--commerce-ui-shadow-card', $css);
        $this->assertStringContainsString('.commerce-account-dialog', $css);
        $this->assertStringContainsString('.commerce-provider-experience', $css);
        $this->assertStringContainsString('.commerce-product-card', $css);
        $this->assertStringContainsString('.commerce-checkout__summary', $css);
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $css);
    }
}
