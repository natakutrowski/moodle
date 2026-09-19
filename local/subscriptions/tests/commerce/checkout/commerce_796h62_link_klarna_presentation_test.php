<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h62_link_klarna_presentation_test extends \advanced_testcase {

    public function test_link_and_klarna_assets_are_exposed_to_checkout(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        self::assertIsString($checkout);

        self::assertStringContainsString("'linklogourl' =>", $checkout);
        self::assertStringContainsString('link_logo.png', $checkout);
        self::assertStringContainsString("'klarnalogourl' =>", $checkout);
        self::assertStringContainsString('klarna_logo.png', $checkout);
    }

}
