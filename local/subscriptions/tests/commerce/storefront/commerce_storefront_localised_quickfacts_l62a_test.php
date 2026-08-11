<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\storefront;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\storefront\admin\CommerceStorefrontPageEditor;
use local_subscriptions\commerce\storefront\experience\CommerceStorefrontExperienceResolver;

final class commerce_storefront_localised_quickfacts_l62a_test extends advanced_testcase {
    public function test_experience_resolver_prefers_requested_locale_and_falls_back_to_global(): void {
        $metadata = [
            'storefront' => [
                'experience' => [
                    'group' => 'courses',
                    'trust' => ['secure_payment'],
                    'quickfacts' => [
                        ['value' => '30 étapes', 'label' => 'Parcours progressif'],
                    ],
                ],
                'locales' => [
                    'ru' => [
                        'experience' => [
                            'quickfacts' => [
                                ['value' => '30 этапов', 'label' => 'Пошаговый маршрут'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $resolver = new CommerceStorefrontExperienceResolver();

        $ru = $resolver->resolve($metadata, 'course_access', 'ru');
        $en = $resolver->resolve($metadata, 'course_access', 'en');

        self::assertSame('30 этапов', $ru->get_quick_facts()[0]['value']);
        self::assertSame('Пошаговый маршрут', $ru->get_quick_facts()[0]['label']);
        self::assertSame('30 étapes', $en->get_quick_facts()[0]['value']);
        self::assertSame('Parcours progressif', $en->get_quick_facts()[0]['label']);
        self::assertSame(['secure_payment'], $ru->get_trust_items());
    }

    public function test_editor_save_reload_keeps_locale_quickfacts_and_accepts_single_pipe_compatibility(): void {
        $editor = new CommerceStorefrontPageEditor();
        $metadata = [
            'storefront' => [
                'experience' => [
                    'group' => 'courses',
                    'trust' => ['secure_payment'],
                    'quickfacts' => [
                        ['value' => '30 étapes', 'label' => 'Parcours progressif'],
                    ],
                ],
                'locales' => [
                    'fr' => [
                        'experience' => [
                            'quickfacts' => [
                                ['value' => '30 étapes', 'label' => 'Parcours progressif'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = $editor->merge_submission($metadata, [
            'group' => 'courses',
            'trust' => ['secure_payment'],
            'quickfacts' => "30 этапов|Пошаговый маршрут до вершины\n180 глаголов ||| A1–B1+",
        ], 'ru');

        self::assertSame('30 этапов', $result['storefront']['locales']['ru']['experience']['quickfacts'][0]['value']);
        self::assertSame('Пошаговый маршрут до вершины', $result['storefront']['locales']['ru']['experience']['quickfacts'][0]['label']);
        self::assertSame('180 глаголов', $result['storefront']['locales']['ru']['experience']['quickfacts'][1]['value']);
        self::assertSame('30 étapes', $result['storefront']['experience']['quickfacts'][0]['value']);

        $product = new CommerceProduct(
            'COURSE-VERBS',
            CommerceProductType::COURSE_ACCESS,
            CommerceProductStatus::ACTIVE,
            'Trainer',
            '',
            $result
        );

        $definition = $editor->definition_from_product($product, 'ru');
        self::assertStringContainsString('30 этапов ||| Пошаговый маршрут до вершины', $definition['quickfacts']);
        self::assertStringContainsString('180 глаголов ||| A1–B1+', $definition['quickfacts']);
    }
}
