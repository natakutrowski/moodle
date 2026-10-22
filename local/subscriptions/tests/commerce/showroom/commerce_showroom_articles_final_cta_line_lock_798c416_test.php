<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

/**
 * 7.98C.4.16 — Final CTA line lock, accent halo and CTA spacing.
 *
 * @coversNothing
 */
final class commerce_showroom_articles_final_cta_line_lock_798c416_test extends \advanced_testcase {
    public function test_article_heart_has_two_line_desktop_lock_and_mobile_escape_hatch(): void {
        global $CFG;

        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');

        self::assertIsString($css);
        self::assertStringContainsString(
            '7.98C.4.16 — Articles / Alf Final CTA two-line lock + pink halo + CTA air.',
            $css
        );
        self::assertStringContainsString(
            'max-width: min(78rem, calc(100vw - 4rem));',
            $css
        );
        self::assertStringContainsString(
            '.commerce-showroom-final--article_heart h2.commerce-showroom-final__title--compact {',
            $css
        );
        self::assertStringContainsString('width: max-content;', $css);
        self::assertStringContainsString('white-space: nowrap;', $css);
        self::assertStringContainsString(
            '.commerce-showroom-final--article_heart h2.commerce-showroom-final__title--compact > span {',
            $css
        );
        self::assertStringContainsString('display: block;', $css);
        self::assertStringContainsString('0 0 8px rgba(255,255,255,.72)', $css);
        self::assertStringContainsString(
            'margin-top: clamp(1.75rem, 3.2vh, 2.6rem);',
            $css
        );
        self::assertStringContainsString('@media (max-width: 767.98px)', $css);
        self::assertStringContainsString('white-space: normal;', $css);
    }
}
