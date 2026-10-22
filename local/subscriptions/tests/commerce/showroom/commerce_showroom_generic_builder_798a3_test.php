<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomProductConfig;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomProductLinkOptions;

final class commerce_showroom_generic_builder_798a3_test extends \advanced_testcase {
    public function test_offer_schema_is_generated_from_showroom_roles(): void {
        $schema = CommerceShowroomBlockEditorRegistry::schema(
            'offers',
            ['course', 'donation']
        );
        $fields = [];
        foreach ($schema['fields'] as $field) {
            $fields[(string)$field['name']] = $field;
        }

        $this->assertArrayHasKey('coursetitle', $fields);
        $this->assertArrayHasKey('donationtitle', $fields);
        $this->assertArrayHasKey('donationfeatures', $fields);
        $this->assertArrayHasKey('donationfeaturedlabel', $fields);
        $this->assertArrayNotHasKey('pdftitle', $fields);
        $this->assertSame(
            ['course' => 'Cours', 'donation' => 'Donation'],
            $fields['featuredrole']['options']
        );
    }

    public function test_generic_offer_role_survives_normalisation(): void {
        $config = CommerceShowroomBlockEditorRegistry::normalise(
            'offers',
            [
                'translations' => [
                    'fr' => [
                        'donationtitle' => 'Soutenir Alf',
                        'donationfeatures' => "Montant libre\nSans accès au cours",
                    ],
                ],
                'featuredrole' => 'donation',
                'order' => 'donation,course',
            ],
            ['course', 'donation']
        );

        $this->assertSame('Soutenir Alf', $config['donationtitle']);
        $this->assertSame('donation', $config['featuredrole']);
        $this->assertSame('donation,course', $config['order']);
    }

    public function test_runtime_customises_and_reorders_arbitrary_roles(): void {
        $reflection = new \ReflectionClass(CommerceShowroomBlockConfigurationPresenter::class);
        $presenter = $reflection->newInstance();
        $method = $reflection->getMethod('apply_offers');
        $method->setAccessible(true);

        $data = [
            'offersheading' => 'Offres',
            'offersdescription' => '',
            'offers' => [
                [
                    'role' => 'course',
                    'sku' => 'COURSE.TEST',
                    'name' => 'Cours catalogue',
                    'shortdescription' => 'Description catalogue',
                    'haspromotion' => false,
                    'hascompareprice' => false,
                ],
                [
                    'role' => 'donation',
                    'sku' => 'SERVICE.DONATION',
                    'name' => 'Donation catalogue',
                    'shortdescription' => 'Description catalogue',
                    'haspromotion' => false,
                    'hascompareprice' => false,
                ],
            ],
            'comparisonoffers' => [],
        ];

        $result = $method->invoke($presenter, $data, [
            'donationtitle' => 'Soutenir Alf',
            'donationsubtitle' => 'Un petit geste pour son cœur',
            'donationfeatures' => "Montant libre\nMerci !",
            'donationfeaturedlabel' => 'Soutenir',
            'featuredrole' => 'donation',
            'order' => 'donation,course',
            'showbadges' => true,
            'showsavings' => true,
        ]);

        $this->assertSame('donation', $result['offers'][0]['role']);
        $this->assertSame('Soutenir Alf', $result['offers'][0]['name']);
        $this->assertSame('Un petit geste pour son cœur', $result['offers'][0]['subtitle']);
        $this->assertSame('Soutenir', $result['offers'][0]['featuredlabel']);
        $this->assertTrue($result['offers'][0]['isfeatured']);
        $this->assertCount(2, $result['offers'][0]['features']);
    }

    public function test_comparison_parser_uses_dynamic_role_order(): void {
        $reflection = new \ReflectionClass(CommerceShowroomBlockConfigurationPresenter::class);
        $presenter = $reflection->newInstance();
        $method = $reflection->getMethod('comparison_rows_from_config');
        $method->setAccessible(true);

        $rows = $method->invoke(
            $presenter,
            "Accès au cours|✓|—\nSoutien à Alf|✓|✓",
            ['course', 'donation']
        );

        $this->assertCount(2, $rows);
        $this->assertTrue($rows[0]['course']['included']);
        $this->assertTrue($rows[0]['donation']['notincluded']);
        $this->assertTrue($rows[1]['donation']['included']);
    }

    public function test_products_json_keeps_empty_custom_role_for_builder_contract(): void {
        $json = CommerceShowroomProductConfig::to_json([
            'course' => 'COURSE.TEST',
            'donation' => '',
        ]);

        $this->assertSame(['course', 'donation'], CommerceShowroomProductConfig::roles($json));
    }
    public function test_generic_role_accepts_existing_product_without_legacy_type_lock(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => 'SERVICE.DONATION',
            'type' => 'service',
            'status' => 'active',
            'name' => 'Donation',
            'description' => '',
            'metadatajson' => '{}',
            'availablefrom' => null,
            'availableuntil' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $links = new CommerceShowroomProductLinkOptions($DB);
        $this->assertSame(
            'SERVICE.DONATION',
            $links->normalise_role_sku('donation', 'service.donation')
        );

        $this->expectException(\invalid_parameter_exception::class);
        $links->normalise_role_sku('course', 'SERVICE.DONATION');
    }

}
