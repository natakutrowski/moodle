<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_storefront_course_light_layout_test extends \advanced_testcase {
    public function test_course_layout_is_compact_and_reuses_shared_commerce_panel(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/storefront/'
            . 'product_templates/course.mustache'
        );
        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/storefront.css'
        );

        $this->assertStringContainsString('commerce-product-course-hero__grid', $template);
        $this->assertStringContainsString('{{#hasquickfacts}}', $template);
        $this->assertStringContainsString('commerce-product-course-fact', $template);
        $this->assertStringContainsString(
            'local_subscriptions/storefront/product_commerce_panel',
            $template
        );
        $this->assertStringNotContainsString('name="action" value="buynow"', $template);
        $this->assertStringContainsString('.commerce-product-page--course', $css);
        $this->assertStringContainsString('.commerce-product-course-hero__grid', $css);
        $this->assertStringContainsString('.commerce-product-showroom-link', $css);
    }

    public function test_sidebar_commerce_remains_available_without_automatic_hero(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/storefront/'
            . 'product_templates/course.mustache'
        );

        $this->assertStringContainsString('{{^showproducthero}}', $template);
        $this->assertStringContainsString('{{#commerceissidebar}}', $template);
        $this->assertStringContainsString('commerce-product-course-commerce--standalone', $template);
    }

    public function test_standard_course_access_product_resolves_to_course_layout(): void {
        $product = new \local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontProduct(
            'course:verbs',
            'Trainer',
            'Short description',
            '<p>Description</p>',
            'course_access',
            [new \local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontPrice('EUR', 3900)],
            [],
            true,
            null,
            [],
            [
                'storefront' => [
                    'template' => 'default',
                    'commerce_position' => 'sidebar_sticky',
                ],
            ]
        );

        $definition = (new \local_subscriptions\commerce\storefront\page\CommerceStorefrontPageResolver())
            ->resolve($product);

        $this->assertSame('course', $definition->get_layout());
        $this->assertSame(
            'local_subscriptions/storefront/product_templates/course',
            $definition->get_template()
        );
        $this->assertSame('sidebar_sticky', $definition->get_commerce_position());
    }

    public function test_editorial_course_access_product_keeps_explicit_editorial_layout(): void {
        $product = new \local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontProduct(
            'course:verbs',
            'Trainer',
            'Short description',
            '<p>Description</p>',
            'course_access',
            [new \local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontPrice('EUR', 3900)],
            [],
            true,
            null,
            [],
            [
                'storefront' => [
                    'template' => 'editorial',
                    'commerce_position' => 'sidebar_sticky',
                ],
            ]
        );

        $definition = (new \local_subscriptions\commerce\storefront\page\CommerceStorefrontPageResolver())
            ->resolve($product);

        $this->assertSame('editorial', $definition->get_layout());
        $this->assertSame(
            'local_subscriptions/storefront/product_templates/editorial',
            $definition->get_template()
        );
    }
}
