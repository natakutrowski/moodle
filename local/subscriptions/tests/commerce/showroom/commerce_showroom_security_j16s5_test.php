<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_security_j16s5_test extends \advanced_testcase {
    public function test_manage_showrooms_capability_has_language_strings(): void {
        global $CFG;

        foreach (['en', 'fr', 'ru'] as $lang) {
            $source = file_get_contents(
                $CFG->dirroot
                . '/local/subscriptions/lang/'
                . $lang
                . '/local_subscriptions.php'
            );

            self::assertStringContainsString(
                "\$string['subscriptions:manage_showrooms']",
                $source
            );
        }
    }

    public function test_preview_requires_the_expected_capability(): void {
        global $CFG;

        $preview = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/showrooms/preview.php'
        );

        self::assertStringContainsString(
            "require_capability('local/subscriptions:manage_showrooms'",
            $preview
        );
    }
}
