<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockTypeRegistry;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;

final class commerce_showroom_cms_foundation_j13g1_test extends \advanced_testcase {
    public function test_block_registry_contains_marketing_blocks(): void {
        self::assertTrue(CommerceShowroomBlockTypeRegistry::exists('hero'));
        self::assertTrue(CommerceShowroomBlockTypeRegistry::exists('offers'));
        self::assertTrue(CommerceShowroomBlockTypeRegistry::exists('faq'));
        self::assertTrue(CommerceShowroomBlockTypeRegistry::exists('html'));
    }

    public function test_repository_persists_showroom_and_blocks(): void {
        global $DB;
        $this->resetAfterTest();
        $repository = new CommerceShowroomCmsRepository($DB);
        $id = $repository->save([
            'showroomkey' => 'test-showroom',
            'status' => 'draft',
            'name' => 'Test showroom',
            'template' => 'local_subscriptions/showroom/third_group_verbs',
            'slugfr' => 'test-showroom',
            'productsjson' => '{"course":"SKU.TEST"}',
            'settingsjson' => '{}',
        ], 2);
        self::assertGreaterThan(0, $id);
        self::assertSame('test-showroom', $repository->get($id)->showroomkey);

        $repository->save_block($id, [
            'blockkey' => 'hero',
            'blocktype' => 'hero',
            'sortorder' => 10,
            'enabled' => 1,
            'configjson' => '{}',
        ], 2);
        self::assertCount(1, $repository->blocks($id));
    }
}
