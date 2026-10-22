<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

/** 7.98E8.4 — A1 adventure overview visual contract. */
final class commerce_showroom_a1_adventure_overview_798e84_test extends \advanced_testcase {
    public function test_public_markup_and_css_match_e84_contract(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/showroom/block_adventure_overview.mustache'
        );
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');

        self::assertIsString($template);
        self::assertStringContainsString('commerce-showroom-adventure-overview__badge', $template);
        self::assertStringContainsString('fa-solid fa-route', $template);
        self::assertStringContainsString('{{adventuretitleaccent}}', $template);
        self::assertStringContainsString('{{adventurecenterimageurl}}', $template);
        self::assertStringContainsString('{{adventurecenterlabel}}', $template);

        self::assertIsString($css);
        self::assertStringContainsString('CampusFR 7.98E.8.4', $css);
        self::assertStringContainsString('#showroom-adventure .commerce-showroom-adventure-overview__badge', $css);
        self::assertStringContainsString('grid-template-columns: minmax(0, 1fr) minmax(310px, 430px) minmax(0, 1fr);', $css);
        self::assertStringContainsString('transform: rotate(.8deg)', $css);
        self::assertStringContainsString('font-family: "Caveat", "Comic Sans MS", cursive;', $css);
        self::assertStringContainsString('@media (hover: hover) and (pointer: fine)', $css);
        self::assertStringContainsString('@media (prefers-reduced-motion: reduce)', $css);
    }
}
