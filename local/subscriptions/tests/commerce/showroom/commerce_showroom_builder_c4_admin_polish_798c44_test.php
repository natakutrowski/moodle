<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_builder_c4_admin_polish_798c44_test extends \advanced_testcase {
    public function test_dark2_has_localised_builder_badge_and_disabled_menu_stays_above_cards(): void {
        global $CFG;

        $root = $CFG->dirroot . '/local/subscriptions/';
        $edit = file_get_contents($root . 'admin/commerce/showrooms/edit.php');
        $css = file_get_contents($root . 'styles/showroom_builder.css');
        $fr = file_get_contents($root . 'lang/fr/local_subscriptions.php');
        $en = file_get_contents($root . 'lang/en/local_subscriptions.php');
        $ru = file_get_contents($root . 'lang/ru/local_subscriptions.php');

        self::assertStringContainsString("'dark2' => get_string(", $edit);
        self::assertStringContainsString('commerce_showroom_n95_background_dark2', $edit);

        self::assertStringContainsString("\$string['commerce_showroom_n95_background_dark2'] = 'Sombre 2';", $fr);
        self::assertStringContainsString("\$string['commerce_showroom_n95_background_dark2'] = 'Dark 2';", $en);
        self::assertStringContainsString("\$string['commerce_showroom_n95_background_dark2'] = 'Тёмный 2';", $ru);

        self::assertStringContainsString('.commerce-showroom-block.is-disabled {', $css);
        self::assertStringContainsString('opacity: 1;', $css);
        self::assertStringContainsString('.commerce-showroom-block.is-disabled .commerce-showroom-block__more > summary {', $css);
        self::assertStringContainsString('opacity: 1;', $css);
        self::assertStringContainsString('.commerce-showroom-block.is-disabled:has(.commerce-showroom-block__more[open])', $css);
        self::assertStringContainsString('z-index: 1000;', $css);
        self::assertStringContainsString('.commerce-showroom-block__more[open] .commerce-showroom-block__more-panel', $css);
        self::assertStringContainsString('z-index: 1010;', $css);
    }
}
