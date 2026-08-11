<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\storefront;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\storefront\experience\CommerceStorefrontExperienceResolver;
use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontPrice;
use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontProduct;

final class commerce_795f6b_customer_experience_test extends advanced_testcase {
    public function test_experience_resolver_applies_native_type_defaults(): void {
        $resolver = new CommerceStorefrontExperienceResolver();

        $course = $resolver->resolve([], 'course_access');
        self::assertSame('courses', $course->get_group());
        self::assertContains('secure_payment', $course->get_trust_items());
        self::assertContains('lifetime_access', $course->get_trust_items());

        $digital = $resolver->resolve([], 'digital_download');
        self::assertSame('resources', $digital->get_group());

        $bundle = $resolver->resolve([], 'bundle');
        self::assertSame('bundles', $bundle->get_group());
    }

    public function test_experience_resolver_normalises_trust_and_quick_facts(): void {
        $resolver = new CommerceStorefrontExperienceResolver();
        $experience = $resolver->resolve([
            'storefront' => [
                'experience' => [
                    'group' => 'resources',
                    'trust' => ['support', 'support', 'unknown'],
                    'quickfacts' => [
                        ['value' => '82', 'label' => 'vidéos'],
                        ['value' => '', 'label' => 'ignored'],
                        ['value' => '430', 'label' => 'exercices'],
                    ],
                ],
            ],
        ], 'course_access');

        self::assertSame('resources', $experience->get_group());
        self::assertSame(['support'], $experience->get_trust_items());
        self::assertSame([
            ['value' => '82', 'label' => 'vidéos'],
            ['value' => '430', 'label' => 'exercices'],
        ], $experience->get_quick_facts());
    }

    public function test_storefront_product_exposes_f6b_projection(): void {
        $product = new CommerceStorefrontProduct(
            'COURSE-A1', 'A1', '', '', 'course_access',
            [new CommerceStorefrontPrice('EUR', 14900)], [], true,
            null, [], [], false, 10, [], 'courses',
            ['secure_payment'], [['value' => '82', 'label' => 'vidéos']], true
        );

        self::assertSame('courses', $product->get_group());
        self::assertTrue($product->is_owned());
        self::assertSame(['secure_payment'], $product->get_trust_items());
        self::assertSame('82', $product->get_quick_facts()[0]['value']);
    }
}
