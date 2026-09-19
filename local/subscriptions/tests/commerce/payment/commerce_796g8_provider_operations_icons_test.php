<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796g8_provider_operations_icons_test extends advanced_testcase {
    public function test_provider_renderer_uses_existing_provider_svg_directory(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/crm/commerce/rendering/'
            . 'CommercePaymentProviderOperationalRenderer.php'
        );

        $this->assertStringContainsString(
            '/pix/providers/',
            $contents
        );
        $this->assertStringContainsString(
            'commerce-provider-ops-logo',
            $contents
        );
    }
}
