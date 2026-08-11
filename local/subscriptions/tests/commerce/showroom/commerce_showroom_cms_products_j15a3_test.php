<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPublishedDefinitionResolver;

final class commerce_showroom_cms_products_j15a3_test extends \advanced_testcase {
    public function test_published_products_json_overrides_registry_constants(): void {
        global $DB;

        $this->resetAfterTest(true);

        $repository = new CommerceShowroomCmsRepository($DB);
        $repository->save([
            'showroomkey' => 'third-group-verbs',
            'status' => 'published',
            'name' => 'Verbes du 3e groupe',
            'template' => 'local_subscriptions/showroom/third_group_verbs',
            'titlekey' => 'commerce_showroom_third_group_verbs_title',
            'descriptionkey' => 'commerce_showroom_third_group_verbs_description',
            'productsjson' => json_encode([
                'pdf' => 'DIGITAL.CMS-PDF',
                'bundle' => 'BUNDLE.CMS',
                'course' => 'SUB.CMS.COURSE',
            ], JSON_THROW_ON_ERROR),
            'settingsjson' => '{}',
        ], 2);

        $definition = (
            new CommerceShowroomPublishedDefinitionResolver($DB)
        )->require('third-group-verbs');

        $this->assertSame([
            'course' => 'SUB.CMS.COURSE',
            'pdf' => 'DIGITAL.CMS-PDF',
            'bundle' => 'BUNDLE.CMS',
        ], $definition->get_products());
    }

    public function test_draft_cms_products_do_not_override_public_registry(): void {
        $definition = \local_subscriptions\commerce\showroom\CommerceShowroomRegistry::require(
            \local_subscriptions\commerce\showroom\CommerceShowroomRegistry::THIRD_GROUP_VERBS
        );
        $products = $definition->get_products();
        self::assertSame('COURSE_ACCESS.THIRD_GROUP_VERBS_COURSE', $products['course']);
        self::assertSame('DIGITAL.VERBES-3E-GROUPE', $products['pdf']);
        self::assertSame('BUNDLE.THIRD_GROUP_VERBS_BUNDLE', $products['bundle']);


    }

    public function test_empty_published_role_can_remove_an_offer(): void {
        global $DB;

        $this->resetAfterTest(true);

        $repository = new CommerceShowroomCmsRepository($DB);
        $repository->save([
            'showroomkey' => 'third-group-verbs',
            'status' => 'published',
            'name' => 'Verbes du 3e groupe',
            'template' => 'local_subscriptions/showroom/third_group_verbs',
            'titlekey' => 'commerce_showroom_third_group_verbs_title',
            'descriptionkey' => 'commerce_showroom_third_group_verbs_description',
            'productsjson' => json_encode([
                'course' => '',
                'pdf' => 'DIGITAL.VERBES-3E-GROUPE',
                'bundle' => 'BUNDLEA1VERBES',
            ], JSON_THROW_ON_ERROR),
            'settingsjson' => '{}',
        ], 2);

        $definition = (
            new CommerceShowroomPublishedDefinitionResolver($DB)
        )->require('third-group-verbs');

        $this->assertArrayNotHasKey('course', $definition->get_products());
        $this->assertArrayHasKey('pdf', $definition->get_products());
        $this->assertArrayHasKey('bundle', $definition->get_products());
    }
}
