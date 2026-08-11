<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_payment_support_link_test extends advanced_testcase {

    public function test_failed_and_cancelled_defaults_use_clickable_support_email(): void {
        $root = dirname(__DIR__, 3);
        $source = (string)file_get_contents(
            $root . '/classes/commerce/mail/template/studio/CommerceMailTemplateDefaults.php'
        );

        $this->assertGreaterThanOrEqual(6, substr_count($source, 'mailto:{support_email}'));
    }

    public function test_persisted_failed_and_cancelled_templates_are_linkified_at_render_time(): void {
        $root = dirname(__DIR__, 3);
        $source = (string)file_get_contents(
            $root . '/classes/commerce/mail/template/AbstractCommerceMailTemplate.php'
        );

        $this->assertStringContainsString("'mailto:' . \$supportemail", $source);
    }
}
