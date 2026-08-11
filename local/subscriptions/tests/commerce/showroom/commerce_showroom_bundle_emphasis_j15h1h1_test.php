<?php

declare(strict_types=1);

namespace local_subscriptions;

/** Static certification checks for the final J15H.1H.1 Bundle polish. */
final class commerce_showroom_bundle_emphasis_j15h1h1_test extends \advanced_testcase {
    public function test_bundle_emphasis_and_mobile_spacing_are_present(): void {
        $root = dirname(__DIR__, 3);
        $css = file_get_contents($root . '/styles/showroom.css');

        self::assertStringContainsString('J15H.1H.1', $css);
        self::assertStringContainsString('background: #fffafa;', $css);
        self::assertStringContainsString('scale(1.085)', $css);
        self::assertStringContainsString('rotate(-.18deg)', $css);
        self::assertStringContainsString('padding-right: .8rem !important;', $css);
        self::assertStringContainsString('padding-left: .8rem !important;', $css);
    }
}
