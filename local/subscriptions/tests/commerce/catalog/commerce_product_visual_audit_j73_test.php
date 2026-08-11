<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_product_visual_audit_j73_test
        extends \advanced_testcase {

    public function test_cli_uses_moodle_root_and_mutually_exclusive_statuses(): void {
        global $CFG;

        $cli = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/cli/commerce/catalog/'
            . 'audit_product_visuals.php'
        );
        $service = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/catalog/visual/'
            . 'CommerceProductVisualAuditService.php'
        );

        $this->assertStringContainsString(
            "../../../../../config.php",
            $cli
        );
        $this->assertStringContainsString(
            'FALLBACK ONLY',
            $cli
        );
        $this->assertStringContainsString(
            'fallback_available',
            $service
        );
    }
}
