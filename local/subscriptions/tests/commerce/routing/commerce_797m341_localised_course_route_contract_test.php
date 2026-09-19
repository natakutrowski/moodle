<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** Contract for M3.4.1 localized direct course routes. */
final class commerce_797m341_localised_course_route_contract_test extends \advanced_testcase {
    public function test_course_route_slugs_are_localised_for_supported_public_languages(): void {
        $this->assertSame('/cours', subscription_config::public_route_path('course', 'fr'));
        $this->assertSame('/courses', subscription_config::public_route_path('course', 'en'));
        $this->assertSame('/kurs', subscription_config::public_route_path('course', 'ru'));
    }

    public function test_root_htaccess_routes_all_localised_course_paths(): void {
        global $CFG;

        $path = $CFG->dirroot . '/.htaccess';
        $this->assertFileExists($path);

        $source = file_get_contents($path);
        $this->assertIsString($source);
        $this->assertStringContainsString(
            'RewriteRule ^(cours|courses|kurs)/([0-9]+)/?$',
            $source
        );
        $this->assertStringContainsString(
            'public_router.php?route=course&id=$2',
            $source
        );
    }
}
