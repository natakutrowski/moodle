<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_storefront_placeholder_library_j741_test
        extends \advanced_testcase {

    public function test_storefront_uses_public_digital_library_api(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/storefront_product.php'
        );

        $this->assertStringContainsString(
            '$library->get_resources()',
            $source
        );
        $this->assertStringNotContainsString(
            '$library->resources',
            $source
        );
    }

    public function test_placeholders_use_neutral_grey_gradient(): void {
        global $CFG;

        $styles = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/styles/storefront.css'
        );

        $this->assertStringContainsString(
            '#f7f8fa',
            $styles
        );
        $this->assertStringContainsString(
            '#eceff3',
            $styles
        );
        $this->assertStringContainsString(
            'color: #7a8496',
            $styles
        );
        $this->assertStringNotContainsString(
            'rgba(247, 37, 133, .12)',
            $styles
        );
    }
}
