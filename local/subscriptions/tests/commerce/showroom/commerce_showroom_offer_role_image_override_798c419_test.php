<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry;

/**
 * 7.98C.4.19 — per-offer-role image overrides in the Showroom offers block.
 *
 * @coversNothing
 */
final class commerce_showroom_offer_role_image_override_798c419_test extends \advanced_testcase {
    public function test_offer_schema_exposes_non_translatable_image_for_each_role(): void {
        $schema = CommerceShowroomBlockEditorRegistry::schema(
            'offers',
            ['course', 'course_support', 'support']
        );

        $fields = [];
        foreach ($schema['fields'] as $field) {
            $fields[(string)$field['name']] = $field;
        }

        foreach (['course', 'course_support', 'support'] as $role) {
            $name = $role . 'imageurl';
            self::assertArrayHasKey($name, $fields);
            self::assertSame('media', $fields[$name]['type']);
            self::assertSame('image', $fields[$name]['kind']);
            self::assertFalse($fields[$name]['translatable']);
        }
    }

    public function test_dynamic_offer_role_image_is_accepted_by_media_allowlist(): void {
        $roles = ['course', 'course_support', 'support'];

        self::assertTrue(
            CommerceShowroomBlockEditorRegistry::is_media_field(
                'offers',
                'course_supportimageurl',
                $roles
            )
        );
        self::assertTrue(
            CommerceShowroomBlockEditorRegistry::is_media_field(
                'offers',
                'supportimageurl',
                $roles
            )
        );
        self::assertFalse(
            CommerceShowroomBlockEditorRegistry::is_media_field(
                'offers',
                'bundleimageurl',
                $roles
            )
        );
    }

    public function test_offer_image_survives_normalisation(): void {
        $config = CommerceShowroomBlockEditorRegistry::normalise(
            'offers',
            [
                'course_supportimageurl' => '/pluginfile.php/1/custom-course-support.jpg',
            ],
            ['course', 'course_support', 'support']
        );

        self::assertSame(
            '/pluginfile.php/1/custom-course-support.jpg',
            $config['course_supportimageurl']
        );
    }

    public function test_role_image_overrides_only_selected_offer_and_keeps_product_fallbacks(): void {
        $reflection = new \ReflectionClass(CommerceShowroomBlockConfigurationPresenter::class);
        $presenter = $reflection->newInstance();
        $method = $reflection->getMethod('apply_offers');
        $method->setAccessible(true);

        $data = $method->invoke($presenter, [
            'offersheading' => 'Offres',
            'offersdescription' => '',
            'offersbadgeicon' => 'fa-solid fa-heart',
            'offers' => [
                [
                    'role' => 'course',
                    'sku' => 'COURSE_ACCESS.ARTICLES',
                    'name' => 'Cours seul',
                    'shortdescription' => '',
                    'hascover' => true,
                    'coverurl' => '/product/course-showroom.jpg',
                    'coverresponsive' => true,
                    'coversrcset' => '/product/course-480.jpg 480w, /product/course-960.jpg 960w',
                    'coveralt' => 'Cours Articles',
                    'haspromotion' => false,
                    'hascompareprice' => false,
                ],
                [
                    'role' => 'course_support',
                    'sku' => 'COURSE_ACCESS.ARTICLES',
                    'name' => 'Cours + Don',
                    'shortdescription' => '',
                    'hascover' => true,
                    'coverurl' => '/product/course-showroom.jpg',
                    'coverresponsive' => true,
                    'coversrcset' => '/product/course-480.jpg 480w, /product/course-960.jpg 960w',
                    'coveralt' => 'Cours Articles',
                    'haspromotion' => false,
                    'hascompareprice' => false,
                ],
                [
                    'role' => 'support',
                    'sku' => 'DONATION.ALF',
                    'name' => 'Don seul',
                    'shortdescription' => '',
                    'hascover' => true,
                    'coverurl' => '/product/alf-showroom.jpg',
                    'coverresponsive' => false,
                    'coversrcset' => '',
                    'coveralt' => 'Alf',
                    'haspromotion' => false,
                    'hascompareprice' => false,
                ],
            ],
            'comparisonoffers' => [],
        ], [
            'presentation' => 'alf_support',
            'course_supporttitle' => 'Le cours + un coup de cœur',
            'course_supportimageurl' => '/pluginfile.php/1/showroom/course-plus-don.jpg',
            'order' => 'course,course_support,support',
            'showsavings' => true,
            'showbadges' => true,
        ]);

        self::assertSame('/product/course-showroom.jpg', $data['offers'][0]['coverurl']);
        self::assertTrue($data['offers'][0]['coverresponsive']);
        self::assertStringContainsString('480w', $data['offers'][0]['coversrcset']);

        self::assertSame('course_support', $data['offers'][1]['role']);
        self::assertSame('Le cours + un coup de cœur', $data['offers'][1]['name']);
        self::assertTrue($data['offers'][1]['hascover']);
        self::assertSame(
            '/pluginfile.php/1/showroom/course-plus-don.jpg',
            $data['offers'][1]['coverurl']
        );
        self::assertFalse($data['offers'][1]['coverresponsive']);
        self::assertSame('', $data['offers'][1]['coversrcset']);
        self::assertSame('Le cours + un coup de cœur', $data['offers'][1]['coveralt']);

        self::assertSame('/product/alf-showroom.jpg', $data['offers'][2]['coverurl']);
    }
}
