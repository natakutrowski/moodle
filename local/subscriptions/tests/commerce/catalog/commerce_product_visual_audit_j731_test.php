<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_product_visual_audit_j731_test
        extends \advanced_testcase {

    public function test_cli_defaults_to_active_products_and_supports_history(): void {
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
            "'include-inactive' => false",
            $cli
        );
        $this->assertStringContainsString(
            "!empty(\$options['include-inactive'])",
            $cli
        );
        $this->assertStringContainsString(
            "'status = :activestatus'",
            $service
        );
        $this->assertStringContainsString(
            "'fallback_only_formats'",
            $service
        );
        $this->assertStringContainsString(
            "'includes_inactive'",
            $service
        );
    }

    public function test_legacy_fallback_is_not_counted_as_missing_master(): void {
        global $CFG;

        $service = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/catalog/visual/'
            . 'CommerceProductVisualAuditService.php'
        );

        $this->assertStringContainsString(
            "&& !\$format['fallback_available']",
            $service
        );
        $this->assertStringContainsString(
            "&& \$format['fallback_available']",
            $service
        );
    }
}
