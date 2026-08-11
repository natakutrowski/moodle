<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockMediaManager;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomSlugService;

final class commerce_showroom_cleanup_j16s12_test extends \advanced_testcase {
    public function test_deleting_showroom_removes_all_block_media_and_revisions(): void {
        global $DB;
        $this->resetAfterTest(true);

        $context = \context_system::instance();
        $repository = new CommerceShowroomCmsRepository($DB);

        $showroomid = $repository->save([
            'showroomkey' => 'delete-media-test',
            'status' => 'draft',
            'name' => 'Delete test',
            'template' => 'local_subscriptions/showroom/third_group_verbs',
            'productsjson' => '{}',
            'settingsjson' => '{}',
        ], 2);

        $blockids = [];
        foreach (['hero', 'final_cta'] as $index => $type) {
            $blockids[] = $repository->save_block($showroomid, [
                'blocktype' => $type,
                'sortorder' => ($index + 1) * 10,
                'enabled' => true,
                'configjson' => '{}',
            ], 2);
        }

        foreach ($blockids as $blockid) {
            get_file_storage()->create_file_from_string([
                'contextid' => $context->id,
                'component' => CommerceShowroomBlockMediaManager::COMPONENT,
                'filearea' => CommerceShowroomBlockMediaManager::FILEAREA,
                'itemid' => $blockid,
                'filepath' => '/backgroundurl/',
                'filename' => 'backgroundurl.png',
            ], 'test');
        }

        $DB->insert_record('local_subs_showroom_rev', (object)[
            'showroomid' => $showroomid,
            'revisionno' => 1,
            'action' => 'test',
            'note' => '',
            'snapshotjson' => '{}',
            'timecreated' => time(),
            'usercreated' => 2,
        ]);

        $repository->delete($showroomid);

        self::assertNull($repository->get($showroomid));
        self::assertSame([], $repository->blocks($showroomid));
        self::assertSame(
            0,
            $DB->count_records(
                'local_subs_showroom_rev',
                ['showroomid' => $showroomid]
            )
        );

        foreach ($blockids as $blockid) {
            self::assertSame(
                [],
                get_file_storage()->get_area_files(
                    $context->id,
                    CommerceShowroomBlockMediaManager::COMPONENT,
                    CommerceShowroomBlockMediaManager::FILEAREA,
                    $blockid,
                    'id ASC',
                    false
                )
            );
        }
    }

    public function test_product_slug_overlap_is_not_a_showroom_publication_conflict(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/showroom/cms/'
            . 'CommerceShowroomSlugService.php'
        );

        self::assertStringNotContainsString(
            '->find_sku(',
            $source
        );
        self::assertStringContainsString(
            'used_by_other_published_showroom',
            $source
        );
    }

    public function test_top_level_showroom_has_priority_but_category_product_routes_do_not(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/public_router.php'
        );

        $showroom = strpos(
            $source,
            '// J16S12 — dynamic top-level Showroom routing'
        );
        $product = strpos(
            $source,
            '$slugservice = new CommerceProductSlugService($DB);'
        );

        self::assertNotFalse($showroom);
        self::assertNotFalse($product);
        self::assertLessThan($product, $showroom);
        self::assertStringContainsString(
            "if (\$category === '') {",
            $source
        );
    }
}
