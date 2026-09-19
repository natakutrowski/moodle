<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_797j13_calendar_edit_test extends advanced_testcase {
    public function test_calendar_unlock_date_can_be_edited(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/education/calendar.php'
        );

        self::assertStringContainsString(
            "optional_param('update', 0, PARAM_INT)",
            $source
        );
        self::assertStringContainsString(
            "'type' => 'datetime-local'",
            $source
        );
        self::assertStringContainsString(
            "'name' => 'update'",
            $source
        );
        self::assertStringContainsString(
            '$existingitem->get_position()',
            $source
        );
        self::assertStringContainsString(
            '$existingitem->get_time_created()',
            $source
        );
        self::assertStringContainsString(
            'commerce_education_calendar_updated',
            $source
        );
    }
}
