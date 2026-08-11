<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_mail_placeholders_k11b21_test extends advanced_testcase {
    public function test_bundle_placeholder_closure_captures_cfg(): void {
        $root = dirname(__DIR__, 3);
        $source = (string)file_get_contents(
            $root . '/classes/commerce/mail/presentation/CommerceMailPurchasePresentation.php'
        );

        $this->assertStringContainsString(
            'static function(array $group) use ($CFG): array',
            $source
        );
        $this->assertStringContainsString(
            "placeholder-'",
            $source
        );
    }
}
