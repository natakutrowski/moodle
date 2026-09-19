<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796f71_paypal_operational_admin_bool_test extends advanced_testcase {
    public function test_admin_page_casts_param_bool_before_typed_service_call(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/configuration/provider.php'
        );

        $this->assertStringContainsString(
            'optional_param(',
            $contents
        );
        $this->assertStringContainsString(
            'PARAM_BOOL',
            $contents
        );
        $this->assertStringContainsString(
            'if ((bool)$checkremote)',
            $contents
        );
    }
}
