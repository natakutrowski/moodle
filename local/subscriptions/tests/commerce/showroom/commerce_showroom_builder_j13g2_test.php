<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;

final class commerce_showroom_builder_j13g2_test extends \advanced_testcase {
    public function test_repository_supports_builder_block_lifecycle(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $repository = new CommerceShowroomCmsRepository($DB);
        $showroomid = $repository->save([
            'showroomkey' => 'builder-test',
            'status' => 'draft',
            'name' => 'Builder test',
            'template' => 'local_subscriptions/showroom/third_group_verbs',
        ], (int)$user->id);

        $hero = $repository->save_block($showroomid, [
            'blocktype' => 'hero',
            'enabled' => true,
            'configjson' => '{"title":"Hero"}',
        ], (int)$user->id);
        $faq = $repository->save_block($showroomid, [
            'blocktype' => 'faq',
            'enabled' => true,
            'configjson' => '{}',
        ], (int)$user->id);
        $copy = $repository->duplicate_block($showroomid, $hero, (int)$user->id);

        self::assertCount(3, $repository->blocks($showroomid));
        self::assertNotSame($hero, $copy);

        $repository->set_block_enabled($showroomid, $faq, false, (int)$user->id);
        self::assertSame(0, (int)$repository->get_block($faq)->enabled);

        $repository->reorder_blocks($showroomid, [$faq, $copy, $hero], (int)$user->id);
        self::assertSame([$faq, $copy, $hero], array_map(
            static fn(\stdClass $block): int => (int)$block->id,
            $repository->blocks($showroomid)
        ));

        $repository->delete_block($showroomid, $copy);
        self::assertNull($repository->get_block($copy));
    }

    public function test_builder_uses_amd_and_a_secured_ajax_endpoint(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions';
        $page = file_get_contents($root . '/admin/commerce/showrooms/edit.php');
        $runtime = file_get_contents($root . '/js/showroom_builder.js');
        self::assertStringContainsString("/local/subscriptions/js/showroom_builder.js", $page);
        self::assertStringContainsString("'id' => 'commerce-showroom-builder'", $page);
        self::assertStringContainsString('sesskey', $page);
        self::assertStringContainsString('fetch(config.endpoint', $runtime);


    }
}
