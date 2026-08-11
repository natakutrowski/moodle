<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\storefront\seo\CommerceStorefrontSeoHeadRegistry;

defined('MOODLE_INTERNAL') || die();

final class commerce_storefront_seo_head_hook_test
        extends \advanced_testcase {

    protected function tearDown(): void {
        CommerceStorefrontSeoHeadRegistry::clear();
        parent::tearDown();
    }

    public function test_storefront_page_does_not_use_unknown_page_property(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/storefront_product.php'
        );

        $this->assertStringNotContainsString(
            'additionalhtmlhead',
            $source
        );
        $this->assertStringContainsString(
            'CommerceStorefrontSeoHeadRegistry::set',
            $source
        );
    }

    public function test_head_hook_is_registered(): void {
        global $CFG;

        $hooks = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/db/hooks.php'
        );
        $listener = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/hook_listener.php'
        );

        $this->assertStringContainsString(
            'before_standard_head_html_generation',
            $hooks
        );
        $this->assertStringContainsString(
            'add_storefront_seo_head',
            $listener
        );
        $this->assertStringContainsString(
            '$hook->add_html($html)',
            $listener
        );
    }

    public function test_registry_is_request_local_and_clearable(): void {
        CommerceStorefrontSeoHeadRegistry::set(
            '<meta property="og:title" content="A1">'
        );

        $this->assertStringContainsString(
            'og:title',
            CommerceStorefrontSeoHeadRegistry::get()
        );

        CommerceStorefrontSeoHeadRegistry::clear();

        $this->assertSame(
            '',
            CommerceStorefrontSeoHeadRegistry::get()
        );
    }
}
