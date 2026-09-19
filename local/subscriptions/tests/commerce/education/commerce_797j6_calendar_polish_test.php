<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_797j6_calendar_polish_test extends advanced_testcase {
    public function test_calendar_creation_does_not_ask_for_manual_position(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/education/calendar.php'
        );

        self::assertStringNotContainsString(
            "required_param('position'",
            $source
        );
        self::assertStringNotContainsString(
            "'name' => 'position'",
            $source
        );
        self::assertStringContainsString(
            'for_promotion($promotionid)',
            $source
        );
        self::assertStringContainsString(
            'get_position()',
            $source
        );
    }
}
