<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_ux_certification_j13h2_test extends \advanced_testcase {
    public function test_showroom_admin_entrypoints_load_root_config(): void {
        global $CFG;

        $directory = $CFG->dirroot . '/local/subscriptions/admin/commerce/showrooms';
        foreach (['index.php', 'edit.php', 'ajax.php', 'history.php', 'import.php', 'export.php'] as $filename) {
            $source = file_get_contents($directory . '/' . $filename);
            self::assertIsString($source);
            self::assertStringContainsString("'/../../../../../config.php'", $source, $filename);
            self::assertStringNotContainsString("'/../../../../config.php'", $source, $filename);
        }
    }

    public function test_showroom_amd_contains_no_dead_tilt_binding(): void {
        global $CFG;

        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/amd/src/showroom.js');
        self::assertIsString($source);
        self::assertStringNotContainsString('bindProductTilt', $source);
        self::assertStringNotContainsString('SELECTOR_PRODUCT_VISUAL', $source);
    }

    public function test_offer_visual_does_not_zoom_on_hover(): void {
        global $CFG;

        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        self::assertIsString($css);
        self::assertStringContainsString('transform: none !important;', $css);
        self::assertStringContainsString('scroll-snap-type: x mandatory;', $css);
        self::assertStringContainsString('--showroom-focus:', $css);
    }
}
