<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\storefront\page\CommerceStorefrontPageResolver;
use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontProduct;
use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontPrice;

final class commerce_795f3_product_page_composer_test extends \advanced_testcase {
    public function test_custom_template_and_sections_are_resolved_safely(): void {
        $product = new CommerceStorefrontProduct(
            'course:a1',
            'A1',
            'Short',
            '<p>Description</p>',
            'course_access',
            [new CommerceStorefrontPrice('EUR', 12000)],
            [],
            true,
            null,
            [],
            [
                'storefront' => [
                    'template' => 'editorial',
                    'theme' => 'a1-premium',
                    'sections' => [
                        ['type' => 'features', 'items' => [['title' => 'One']]],
                        ['type' => 'unknown'],
                    ],
                ],
            ]
        );

        $definition = (new CommerceStorefrontPageResolver())->resolve($product);

        $this->assertSame('local_subscriptions/storefront/product_templates/editorial', $definition->get_template());
        $this->assertSame('a1-premium', $definition->get_theme());
        $this->assertCount(1, $definition->get_sections());
        $this->assertSame('features', $definition->get_sections()[0]['type']);
    }

    public function test_unknown_template_falls_back_and_description_is_automatic(): void {
        $product = new CommerceStorefrontProduct(
            'digital:test',
            'Test',
            '',
            '<p>Long description</p>',
            'digital_download',
            [new CommerceStorefrontPrice('EUR', 1000)],
            [],
            true,
            null,
            [],
            ['storefront_template' => '../../unsafe']
        );

        $definition = (new CommerceStorefrontPageResolver())->resolve($product);

        $this->assertSame('local_subscriptions/storefront/product_templates/standard', $definition->get_template());
        $this->assertSame('rich_text', $definition->get_sections()[0]['type']);
    }
}
