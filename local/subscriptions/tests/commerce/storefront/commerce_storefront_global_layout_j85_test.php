<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\storefront\page\CommerceStorefrontLayoutContract;

defined('MOODLE_INTERNAL') || die();

final class commerce_storefront_global_layout_j85_test extends \advanced_testcase {
    public function test_shell_modes_are_controlled(): void {
        $this->assertSame(
            ['standard', 'fullwidth', 'landing', 'immersive'],
            CommerceStorefrontLayoutContract::shell_modes()
        );
        $this->assertSame('standard', CommerceStorefrontLayoutContract::normalise_shell_mode('invalid'));
        $this->assertSame('storefront_landing', CommerceStorefrontLayoutContract::moodle_page_layout('landing'));
    }

    public function test_global_zones_are_complete_and_safe(): void {
        $this->assertSame(
            ['hero', 'commerce', 'content', 'recommendations'],
            CommerceStorefrontLayoutContract::normalise_global_zones('hero,commerce,content,unknown')
        );
        $this->assertSame(
            'after_intro',
            CommerceStorefrontLayoutContract::commerce_position_from_zones(
                ['hero', 'content', 'commerce', 'recommendations']
            )
        );
        $this->assertSame(
            'page_bottom',
            CommerceStorefrontLayoutContract::commerce_position_from_zones(
                ['hero', 'content', 'recommendations', 'commerce']
            )
        );
    }

    public function test_editor_and_public_page_expose_shell_controls(): void {
        global $CFG;
        $editor = file_get_contents($CFG->dirroot . '/local/subscriptions/admin/commerce/products/storefront_builder.php');
        $public = file_get_contents($CFG->dirroot . '/local/subscriptions/storefront_product.php');
        $this->assertStringContainsString('storefront_global_zones', $editor);
        $this->assertStringContainsString('storefront_shell_mode', $editor);
        $this->assertStringContainsString('moodle_page_layout', $public);
        $this->assertStringContainsString('commerce-storefront-hide-header', $public);
    }
}
