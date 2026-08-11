<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_product_visual_audit_contract_test
        extends \advanced_testcase {

    public function test_audit_distinguishes_master_from_legacy_fallback(): void {
        global $CFG;
        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/catalog/visual/'
            . 'CommerceProductVisualAuditService.php'
        );
        $this->assertStringContainsString(
            "'fallback_available'",
            $source
        );
        $this->assertStringContainsString(
            "'missing_master_formats'",
            $source
        );
        $this->assertStringContainsString(
            "status = :activestatus",
            $source
        );
    }
}
