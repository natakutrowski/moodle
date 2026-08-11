<?php

declare(strict_types=1);

namespace theme_edly;

use advanced_testcase;

final class customer_topbar_j12f_test extends advanced_testcase {
    public function test_desktop_logo_is_compacted(): void {
        $css = file_get_contents(
            dirname(__DIR__, 2) . '/style/customer-navigation.css'
        );

        self::assertIsString($css);
        self::assertStringContainsString(
            'J12F — compact desktop navbar',
            $css
        );
        self::assertStringContainsString(
            '.main-navbar .navbar-brand img',
            $css
        );
        self::assertStringContainsString(
            'height: 3.5rem !important;',
            $css
        );
    }
}
