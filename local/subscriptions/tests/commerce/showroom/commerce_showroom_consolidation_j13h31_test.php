<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_consolidation_j13h31_test extends \advanced_testcase {
    public function test_builder_uses_existing_plain_js_runtime_without_blocking_crm_amd(): void {
        global $CFG;
        $page = file_get_contents($CFG->dirroot . '/local/subscriptions/admin/commerce/showrooms/edit.php');
        $runtime = file_get_contents($CFG->dirroot . '/local/subscriptions/js/showroom_builder.js');
        self::assertStringContainsString('/local/subscriptions/js/showroom_builder.js', $page);
        self::assertStringContainsString('(function()', $runtime);
        self::assertStringNotContainsString('require([', $runtime);

    }

    public function test_h25_bundle_merchandising_and_h3_showroom_visual_are_both_present(): void {
        global $CFG;
        $resolver = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/CommerceShowroomProductResolver.php'
        );
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        self::assertIsString($resolver);
        self::assertIsString($css);
        self::assertStringContainsString('apply_bundle_merchandising_price', $resolver);
        self::assertStringContainsString("get_cover_url('showroom')", $resolver);
        self::assertStringContainsString('aspect-ratio: 16 / 9;', $css);
        self::assertStringContainsString('CampusFR J13H2.5', $css);
    }
}
