<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_product_cover_context_strings_j75b1_test
        extends \advanced_testcase {

    public function test_preview_uses_existing_canonical_strings(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/products/assets.php'
        );
        $english = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/lang/en/local_subscriptions.php'
        );

        foreach ([
            'commerce_storefront_price_standard',
            'commerce_cart_add',
            'commerce_cart_view_product',
            'commerce_cart_total',
            'digital_library_download',
        ] as $identifier) {
            $this->assertStringContainsString(
                "'" . $identifier . "'",
                $source
            );
            $this->assertStringContainsString(
                "\$string['" . $identifier . "']",
                $english
            );
        }

        foreach ([
            'commerce_storefront_price_standard_label',
            'commerce_add_to_cart',
            'commerce_storefront_view_product',
            'commerce_checkout_total',
            'commerce_download',
        ] as $obsoleteidentifier) {
            $this->assertStringNotContainsString(
                "'" . $obsoleteidentifier . "'",
                $source
            );
        }
    }
}
