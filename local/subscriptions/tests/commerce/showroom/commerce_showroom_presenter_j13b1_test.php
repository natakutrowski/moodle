<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_presenter_j13b1_test extends \advanced_testcase {
    public function test_presenter_uses_existing_currency_label_string(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/showroom/'
            . 'CommerceShowroomPresenter.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString(
            "get_string('currency_selector_label', 'local_subscriptions')",
            $source
        );
        self::assertStringNotContainsString(
            "get_string('commerce_storefront_currency_select', 'local_subscriptions')",
            $source
        );
    }
}
