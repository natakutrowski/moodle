<?php

declare(strict_types=1);

namespace block_edly_banner_campus;

defined('MOODLE_INTERNAL') || die();

final class landing_navigation_j11_test extends \advanced_testcase {
    public function test_landing_uses_campus_routes_and_native_language_menu(): void {
        global $CFG;

        $root = $CFG->dirroot . '/blocks/edly_banner_campus';
        $source = file_get_contents(
            $root . '/block_edly_banner_campus.php'
        );
        $css = file_get_contents($root . '/styles.css');

        self::assertIsString($source);
        self::assertIsString($css);
        self::assertStringContainsString(
            "is_callable([\$urlfactory, 'my_campus'])",
            $source
        );
        self::assertStringContainsString(
            "\$guestnavigation['shopurl']",
            $source
        );
        self::assertStringContainsString(
            "get_string('hero_my_campus'",
            $source
        );
        self::assertStringContainsString(
            "get_string('hero_cta_my_space'",
            $source
        );
        self::assertStringContainsString(
            'local_campus/guest_navigation',
            $source
        );
        self::assertStringNotContainsString(
            '<select name="lang"',
            $source
        );
        self::assertStringContainsString(
            '.campus-landing-language__toggle',
            $css
        );
        self::assertStringContainsString(
            '.hero-btn-primary',
            $css
        );
    }
}
