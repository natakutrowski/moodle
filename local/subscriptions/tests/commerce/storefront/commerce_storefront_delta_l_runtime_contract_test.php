<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\storefront\localisation\CommerceStorefrontLocaleTransferService;
use local_subscriptions\commerce\storefront\page\CommerceStorefrontLayoutContract;
use local_subscriptions\commerce\storefront\transfer\CommerceStorefrontPackageService;

/**
 * Final stable API contract for the Storefront work completed in delta L.
 */
final class commerce_storefront_delta_l_runtime_contract_test extends advanced_testcase {
    public function test_supported_locales_and_portable_package_contract_are_stable(): void {
        self::assertSame(
            ['fr', 'en', 'ru'],
            CommerceStorefrontLocaleTransferService::LANGUAGES
        );
        self::assertSame(
            'campusfr-commerce-product',
            CommerceStorefrontPackageService::FORMAT
        );
        self::assertSame('cfrproduct', CommerceStorefrontPackageService::EXTENSION);
    }

    public function test_product_specific_layouts_are_part_of_the_stable_layout_contract(): void {
        self::assertContains(
            CommerceStorefrontLayoutContract::COURSE,
            CommerceStorefrontLayoutContract::layouts()
        );
        self::assertContains(
            CommerceStorefrontLayoutContract::DIGITAL,
            CommerceStorefrontLayoutContract::layouts()
        );
        self::assertContains(
            CommerceStorefrontLayoutContract::BUNDLE,
            CommerceStorefrontLayoutContract::layouts()
        );
    }

    public function test_standard_editorial_and_immersive_remain_valid_builder_modes(): void {
        self::assertSame(
            CommerceStorefrontLayoutContract::STANDARD,
            CommerceStorefrontLayoutContract::normalise_layout('standard')
        );
        self::assertSame(
            CommerceStorefrontLayoutContract::EDITORIAL,
            CommerceStorefrontLayoutContract::normalise_layout('editorial')
        );
        self::assertSame(
            CommerceStorefrontLayoutContract::IMMERSIVE,
            CommerceStorefrontLayoutContract::normalise_layout('immersive')
        );
    }
}
