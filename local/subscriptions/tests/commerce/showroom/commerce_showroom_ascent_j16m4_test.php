<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_ascent_j16m4_test extends \advanced_testcase {
    public function test_ascent_css_is_consolidated_and_dynamic_progress_is_supported(): void {
        global $CFG;

        $root = $CFG->dirroot . '/local/subscriptions/';
        $css = file_get_contents($root . 'styles/showroom.css');
        $js = file_get_contents($root . 'amd/src/showroom.js');
        $template = file_get_contents($root . 'templates/showroom/third_group_verbs.mustache');

        self::assertStringNotContainsString('/* J16M1 — premium 30-stage ascent. */', $css);
        self::assertStringNotContainsString('/* J16M2 — ascent polish:', $css);
        self::assertStringNotContainsString('/* J16M3 — ascent mountain/profile precision', $css);
        self::assertSame(1, substr_count($css, '/* J16M4 — consolidated premium ascent.'));

        self::assertStringContainsString(
            'transform: scaleY(var(--showroom-ascent-progress-ratio, 0));',
            $css
        );
        self::assertStringContainsString(
            'background: rgba(255, 255, 255, .58);',
            $css
        );
        self::assertStringContainsString(
            'aspect-ratio: 1 / 1;',
            $css
        );

        self::assertStringContainsString(
            "const firstPointOffset = rect.height * (152 / 190);",
            $js
        );
        self::assertStringContainsString(
            "ascent.style.setProperty('--showroom-ascent-progress-ratio'",
            $js
        );
        self::assertStringContainsString(
            "card.style.setProperty('--showroom-ascent-icon-color', color);",
            $js
        );

        self::assertStringContainsString(
            'L1448 36 L1518 204 L1600 0',
            $template
        );
    }
}
