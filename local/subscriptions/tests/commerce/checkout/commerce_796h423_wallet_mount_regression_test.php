<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h423_wallet_mount_regression_test extends advanced_testcase {
    public function test_express_section_is_present_and_measurable_before_mount(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $this->assertIsString($template);
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/checkout_express_wallets.css'
        );
        $this->assertIsString($css);

        $this->assertStringContainsString('data-checkout-express-wallet-section', $template);
        $this->assertStringContainsString('data-wallet-state="probing"', $template);
        $this->assertStringContainsString('visibility: visible;', $css);
        $this->assertStringContainsString('min-height: 72px;', $css);
    }

    public function test_current_module_mounts_before_bounded_probe_fallback(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );
        $this->assertIsString($js);

        $mount = strpos($js, 'express.mount(mount);');
        $fallback = strpos($js, '12000');
        $this->assertNotFalse($mount);
        $this->assertNotFalse($fallback);
        $this->assertLessThan($fallback, $mount);
    }
}
