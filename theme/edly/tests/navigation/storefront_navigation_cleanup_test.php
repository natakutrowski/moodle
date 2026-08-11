<?php

declare(strict_types=1);

namespace theme_edly;

defined('MOODLE_INTERNAL') || die();

/**
 * Guards the customer-facing theme navigation against Legacy subscription URLs.
 *
 * @coversNothing
 */
final class storefront_navigation_cleanup_test extends \advanced_testcase {
    public function test_theme_customer_ctas_use_storefront_without_legacy_modal(): void {
        global $CFG;

        $files = [
            $CFG->dirroot . '/theme/edly/inc/edly_themehandler.php',
            $CFG->dirroot . '/theme/edly/inc/campus_topbar.php',
            $CFG->dirroot . '/theme/edly/templates/theme_boost/navbar.mustache',
            $CFG->dirroot . '/theme/edly/templates/core_courseformat/local/content/availability.mustache',
        ];

        foreach ($files as $file) {
            $this->assertFileExists($file);
            $source = (string)file_get_contents($file);
            $this->assertStringNotContainsString(
                '/local/subscriptions/subscribe.php',
                $source,
                basename($file) . ' must not expose the Legacy subscription page.'
            );
            $this->assertStringNotContainsString(
                'data-subs-modal="1"',
                $source,
                basename($file) . ' must not trigger the Legacy embedded modal.'
            );
        }

        $handler = (string)file_get_contents($files[0]);
        $this->assertStringContainsString(
            'subscription_config::storefront_page()',
            $handler
        );
    }
}
