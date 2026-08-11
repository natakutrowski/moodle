<?php

declare(strict_types=1);

namespace local_subscriptions;

/** Static certification checks for adaptive 4K/5K Showroom spacing. */
final class commerce_showroom_large_display_j15h1j_test extends \advanced_testcase {
    public function test_large_display_rules_are_scoped_and_preserve_compact_strips(): void {
        $root = dirname(__DIR__, 3);
        $css = file_get_contents($root . '/styles/showroom.css');

        self::assertIsString($css);
        self::assertStringContainsString('J15H.1J — adaptive spacing for very large and high-density displays', $css);
        self::assertStringContainsString('(min-resolution: 1.5dppx)', $css);
        self::assertStringContainsString('--showroom-large-screen-min-section-height', $css);
        self::assertStringContainsString('calc(100svh - var(--showroom-large-screen-topbar))', $css);
        self::assertStringContainsString('section:not(.commerce-showroom-hero):not(.commerce-showroom-trust)', $css);
        self::assertStringContainsString('.commerce-showroom > .commerce-showroom-spacing--compact', $css);
        self::assertStringContainsString('.commerce-showroom > .commerce-showroom-trust', $css);
        self::assertStringContainsString('width: min(1440px, calc(100% - 8rem))', $css);
    }
}
