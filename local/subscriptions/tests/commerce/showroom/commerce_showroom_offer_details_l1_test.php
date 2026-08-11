<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomOfferConfig;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPublishedDefinitionResolver;

final class commerce_showroom_offer_details_l1_test extends \advanced_testcase {
    public function test_existing_configuration_keeps_details_links_enabled(): void {
        $config = CommerceShowroomOfferConfig::from_settings_json('{}');
        self::assertTrue($config['course']['detailsenabled']);
        self::assertTrue($config['pdf']['detailsenabled']);
        self::assertTrue($config['bundle']['detailsenabled']);
    }

    public function test_offer_flags_are_merged_without_losing_other_settings(): void {
        $json = CommerceShowroomOfferConfig::merge_into_settings_json(
            '{"seo":{"fr":{"title":"Titre"}},"custom":"keep"}',
            [
                'course' => ['detailsenabled' => false],
                'pdf' => ['detailsenabled' => true],
                'bundle' => ['detailsenabled' => false],
            ]
        );
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('keep', $decoded['custom']);
        self::assertSame('Titre', $decoded['seo']['fr']['title']);
        self::assertFalse($decoded['offers']['course']['detailsenabled']);
        self::assertTrue($decoded['offers']['pdf']['detailsenabled']);
        self::assertFalse($decoded['offers']['bundle']['detailsenabled']);
    }

    public function test_published_showroom_exposes_offer_details_flags(): void {
        global $DB;
        $this->resetAfterTest(true);

        (new CommerceShowroomCmsRepository($DB))->save([
            'showroomkey' => 'third-group-verbs',
            'status' => 'published',
            'name' => 'Verbes du 3e groupe',
            'template' => 'local_subscriptions/showroom/third_group_verbs',
            'titlekey' => 'commerce_showroom_third_group_verbs_title',
            'descriptionkey' => 'commerce_showroom_third_group_verbs_description',
            'productsjson' => '{"course":"COURSE_ACCESS.THIRD_GROUP_VERBS_COURSE","pdf":"DIGITAL.VERBES-3E-GROUPE","bundle":"BUNDLE.THIRD_GROUP_VERBS_BUNDLE"}',
            'settingsjson' => '{"offers":{"course":{"detailsenabled":false},"pdf":{"detailsenabled":false},"bundle":{"detailsenabled":true}}}',
        ], 2);

        $definition = (new CommerceShowroomPublishedDefinitionResolver($DB))->require('third-group-verbs');
        self::assertFalse($definition->is_offer_details_enabled('course'));
        self::assertFalse($definition->is_offer_details_enabled('pdf'));
        self::assertTrue($definition->is_offer_details_enabled('bundle'));
    }
}
