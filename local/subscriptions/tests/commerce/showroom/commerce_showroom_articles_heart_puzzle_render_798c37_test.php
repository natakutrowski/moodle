<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_articles_heart_puzzle_render_798c37_test extends \advanced_testcase {
    public function test_heart_uses_true_interlocking_piece_paths_and_svg_arrows(): void {
        global $CFG;

        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/default.mustache');

        self::assertStringContainsString('C130 147 165 147 165 170', $template);
        self::assertStringContainsString('C290 193 255 193 255 170', $template);
        self::assertStringContainsString('C145 292 180 292 180 268', $template);
        self::assertStringContainsString('C240 244 275 244 275 268', $template);
        self::assertStringContainsString('<svg class="commerce-showroom-heart-puzzle__card-arrow', $template);
        self::assertStringContainsString('data-heart-segment="1"', $template);
        self::assertStringContainsString('data-heart-segment="5"', $template);
        self::assertStringNotContainsString('commerce-showroom-heart-puzzle__seams', $template);
    }

    public function test_heart_segments_have_direct_hover_glow_and_bidirectional_card_link(): void {
        global $CFG;

        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');

        self::assertStringContainsString('.commerce-showroom-heart-puzzle__heart .commerce-showroom-heart-puzzle__segment:hover', $css);
        self::assertStringContainsString('drop-shadow(0 0 .72rem rgba(246, 31, 130, .32))', $css);
        self::assertStringContainsString(':has(.commerce-showroom-heart-puzzle__segment--3:hover) .commerce-showroom-heart-puzzle__card--3', $css);
        self::assertStringContainsString(':has(.commerce-showroom-heart-puzzle__card--5:hover) .commerce-showroom-heart-puzzle__segment--5', $css);
        self::assertStringContainsString('.commerce-showroom-heart-puzzle__card--3 .commerce-showroom-heart-puzzle__card-arrow', $css);
        self::assertStringContainsString('top: 12%;', $css);
        self::assertStringContainsString('rotate(-13deg)', $css);
        self::assertStringContainsString('pointer-events: visiblePainted;', $css);
        self::assertStringContainsString('pointer-events: none;', $css);
    }
}
