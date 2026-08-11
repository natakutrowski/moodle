<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\storefront\page\CommerceStorefrontLayoutContract;
use local_subscriptions\commerce\storefront\page\CommerceStorefrontPageResolver;
use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontProduct;

/**
 * @covers \local_subscriptions\commerce\storefront\page\CommerceStorefrontPageResolver
 */
final class commerce_storefront_bundle_light_layout_test extends advanced_testcase {
    public function test_standard_bundle_resolves_to_bundle_layout(): void {
        $product = $this->product('bundle', ['storefront' => ['layout' => 'standard']]);

        $definition = (new CommerceStorefrontPageResolver())->resolve($product);

        self::assertSame(CommerceStorefrontLayoutContract::BUNDLE, $definition->get_layout());
        self::assertSame(
            'local_subscriptions/storefront/product_templates/bundle',
            $definition->get_template()
        );
    }

    public function test_editorial_bundle_keeps_explicit_editorial_layout(): void {
        $product = $this->product('bundle', ['storefront' => ['layout' => 'editorial']]);

        $definition = (new CommerceStorefrontPageResolver())->resolve($product);

        self::assertSame(CommerceStorefrontLayoutContract::EDITORIAL, $definition->get_layout());
    }

    public function test_bundle_with_no_builder_sections_does_not_generate_default_sections(): void {
        $product = $this->product(
            'bundle',
            ['storefront' => ['layout' => 'standard']],
            'Everything needed to master third-group verbs.'
        );

        $definition = (new CommerceStorefrontPageResolver())->resolve($product);

        self::assertSame(CommerceStorefrontLayoutContract::BUNDLE, $definition->get_layout());
        self::assertSame([], $definition->get_sections());
    }

    private function product(
        string $type,
        array $metadata,
        string $description = ''
    ): CommerceStorefrontProduct {
        return new CommerceStorefrontProduct(
            'BUNDLE.TEST',
            'Bundle test',
            '',
            $description,
            $type,
            [],
            [
                ['id' => 10, 'sku' => 'COURSE.TEST', 'name' => 'Course', 'type' => 'course_access', 'quantity' => 1],
                ['id' => 11, 'sku' => 'PDF.TEST', 'name' => 'PDF', 'type' => 'digital_download', 'quantity' => 1],
            ],
            false,
            null,
            [],
            $metadata
        );
    }
}
