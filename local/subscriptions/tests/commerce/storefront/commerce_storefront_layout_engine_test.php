<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\storefront\page\CommerceStorefrontLayoutContract;

defined('MOODLE_INTERNAL') || die();

final class commerce_storefront_layout_engine_test
        extends \advanced_testcase {

    public function test_six_safe_layouts_and_six_positions_are_supported(): void {
        $this->assertSame(
            ['standard', 'editorial', 'immersive', 'course', 'digital', 'bundle'],
            CommerceStorefrontLayoutContract::layouts()
        );
        $this->assertSame(
            ['hero_integrated', 'sidebar_sticky', 'below_hero', 'after_intro', 'page_bottom', 'none'],
            CommerceStorefrontLayoutContract::commerce_positions()
        );
        $this->assertSame(
            'standard',
            CommerceStorefrontLayoutContract::normalise_layout('default')
        );
    }

    public function test_each_layout_has_a_controlled_template(): void {
        global $CFG;
        foreach (CommerceStorefrontLayoutContract::layouts() as $layout) {
            $this->assertFileExists(
                $CFG->dirroot
                . '/local/subscriptions/templates/storefront/'
                . 'product_templates/' . $layout . '.mustache'
            );
        }
    }

    public function test_commerce_panel_is_only_reused_as_a_partial(): void {
        global $CFG;
        foreach (CommerceStorefrontLayoutContract::layouts() as $layout) {
            $template = file_get_contents(
                $CFG->dirroot
                . '/local/subscriptions/templates/storefront/'
                . 'product_templates/' . $layout . '.mustache'
            );
            $this->assertStringContainsString(
                'local_subscriptions/storefront/product_commerce_panel',
                $template
            );
            $this->assertStringNotContainsString(
                'name="action" value="buynow"',
                $template
            );
        }
    }

    public function test_placeholder_ratios_follow_layout_and_product_type(): void {
        $this->assertSame(
            'wide',
            CommerceStorefrontLayoutContract::placeholder_ratio(
                'immersive',
                'course_access'
            )
        );
        $this->assertSame(
            'portrait',
            CommerceStorefrontLayoutContract::placeholder_ratio(
                'digital',
                'digital_download'
            )
        );
        $this->assertSame(
            'landscape',
            CommerceStorefrontLayoutContract::placeholder_ratio(
                'bundle',
                'bundle'
            )
        );
    }
}
