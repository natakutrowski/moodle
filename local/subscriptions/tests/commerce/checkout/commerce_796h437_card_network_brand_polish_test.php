<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h437_card_network_brand_polish_test extends \advanced_testcase {

    public function test_card_network_assets_include_third_network_by_currency(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        self::assertIsString($checkout);
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        self::assertIsString($template);

        self::assertStringContainsString("'cardnetworkthirdiconurl' =>", $checkout);
        self::assertStringContainsString('$currency === \'RUB\'', $checkout);
        self::assertStringContainsString("? '/local/subscriptions/pix/providers/mir.svg'", $checkout);
        self::assertStringContainsString('{{cardnetworkthirdiconurl}}', $template);
        self::assertStringContainsString('{{cardnetworkthirdlabel}}', $template);
    }

}
