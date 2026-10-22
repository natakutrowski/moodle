<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomOfferConfig;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomProductConfig;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPublishedDefinitionResolver;

final class commerce_showroom_generic_contract_798a1_test extends \advanced_testcase {
    public function test_generic_product_role_survives_cms_hydration(): void {
        global $DB;

        $this->resetAfterTest(true);

        $repository = new CommerceShowroomCmsRepository($DB);
        $showroomid = $repository->save([
            'showroomkey' => 'third-group-verbs',
            'status' => 'published',
            'name' => 'Verbes du 3e groupe',
            'template' => 'local_subscriptions/showroom/third_group_verbs',
            'titlekey' => 'commerce_showroom_third_group_verbs_title',
            'descriptionkey' => 'commerce_showroom_third_group_verbs_description',
            'productsjson' => json_encode([
                'course' => 'COURSE_ACCESS.THIRD_GROUP_VERBS_COURSE',
                'pdf' => 'DIGITAL.VERBES-3E-GROUPE',
                'bundle' => 'BUNDLE.THIRD_GROUP_VERBS_BUNDLE',
                'support' => 'SERVICE.ALF-SUPPORT',
            ], JSON_THROW_ON_ERROR),
            'settingsjson' => '{}',
        ], 2);
        $repository->save_block($showroomid, [
            'blocktype' => 'hero',
            'enabled' => true,
            'configjson' => '{}',
        ], 2);

        $definition = (new CommerceShowroomPublishedDefinitionResolver($DB))
            ->require('third-group-verbs');

        self::assertSame(
            'SERVICE.ALF-SUPPORT',
            $definition->get_products()['support']
        );
        self::assertSame(
            'COURSE_ACCESS.THIRD_GROUP_VERBS_COURSE',
            $definition->get_products()['course']
        );
    }

    public function test_product_updates_do_not_drop_unknown_roles(): void {
        $json = CommerceShowroomProductConfig::merge_into_json(
            '{"course":"COURSE.OLD","support":"SERVICE.ALF-SUPPORT"}',
            [
                'course' => 'course.new',
                'pdf' => 'digital.new',
                'bundle' => '',
            ]
        );

        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('COURSE.NEW', $decoded['course']);
        self::assertSame('DIGITAL.NEW', $decoded['pdf']);
        self::assertSame('', $decoded['bundle']);
        self::assertSame('SERVICE.ALF-SUPPORT', $decoded['support']);
    }

    public function test_generic_offer_role_survives_legacy_offer_update(): void {
        $json = CommerceShowroomOfferConfig::merge_into_settings_json(
            '{"offers":{"support":{"detailsenabled":false,"future":"keep"}},"custom":"keep"}',
            [
                'course' => ['detailsenabled' => false],
                'pdf' => ['detailsenabled' => true],
                'bundle' => ['detailsenabled' => false],
            ]
        );

        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('keep', $decoded['custom']);
        self::assertArrayHasKey('support', $decoded['offers']);
        self::assertFalse($decoded['offers']['support']['detailsenabled']);
        self::assertSame('keep', $decoded['offers']['support']['future']);

        $config = CommerceShowroomOfferConfig::from_settings_json($json);
        self::assertArrayHasKey('support', $config);
        self::assertFalse($config['support']['detailsenabled']);
    }

    public function test_empty_generic_role_removes_fallback_relationship(): void {
        $resolved = CommerceShowroomProductConfig::from_json(
            '{"support":""}',
            [
                'course' => 'COURSE.KEEP',
                'support' => 'SERVICE.REMOVE',
            ]
        );

        self::assertSame('COURSE.KEEP', $resolved['course']);
        self::assertArrayNotHasKey('support', $resolved);
    }
}
