<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPublicationService;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomSlugService;

final class commerce_showroom_dynamic_routing_j16s11_test extends \advanced_testcase {
    public function test_published_showroom_slug_resolves_in_any_language(): void {
        global $DB;
        $this->resetAfterTest(true);

        $repository = new CommerceShowroomCmsRepository($DB);
        $id = $repository->save([
            'showroomkey' => 'second-group-verbs',
            'status' => 'published',
            'name' => 'Second group',
            'template' => 'local_subscriptions/showroom/third_group_verbs',
            'slugfr' => 'verbes-2e-groupe',
            'slugen' => 'second-group-verbs',
            'slugru' => 'glagoly-vtoroy-gruppy',
            'productsjson' => '{}',
            'settingsjson' => '{}',
        ], 2);

        $service = new CommerceShowroomSlugService($DB);

        self::assertSame(
            'second-group-verbs',
            $service->find_published_showroom_key('verbes-2e-groupe')
        );
        self::assertSame(
            'second-group-verbs',
            $service->find_published_showroom_key('second-group-verbs')
        );
        self::assertSame(
            'second-group-verbs',
            $service->find_published_showroom_key('glagoly-vtoroy-gruppy')
        );

        self::assertSame($id, (int)$repository->get_by_key(
            'second-group-verbs'
        )->id);
    }

    public function test_draft_and_review_slugs_do_not_resolve_publicly(): void {
        global $DB;
        $this->resetAfterTest(true);

        $repository = new CommerceShowroomCmsRepository($DB);

        foreach (['draft', 'review'] as $index => $status) {
            $repository->save([
                'showroomkey' => 'hidden-' . $status,
                'status' => $status,
                'name' => ucfirst($status),
                'template' => 'local_subscriptions/showroom/third_group_verbs',
                'slugfr' => 'hidden-' . $status,
                'slugen' => '',
                'slugru' => '',
                'productsjson' => '{}',
                'settingsjson' => '{}',
            ], 2 + $index);
        }

        $service = new CommerceShowroomSlugService($DB);

        self::assertNull(
            $service->find_published_showroom_key('hidden-draft')
        );
        self::assertNull(
            $service->find_published_showroom_key('hidden-review')
        );
    }

    public function test_publication_rejects_slug_used_by_another_published_showroom(): void {
        global $DB;
        $this->resetAfterTest(true);

        $repository = new CommerceShowroomCmsRepository($DB);

        $first = $repository->save([
            'showroomkey' => 'first-showroom',
            'status' => 'published',
            'name' => 'First',
            'template' => 'local_subscriptions/showroom/third_group_verbs',
            'slugfr' => 'shared-showroom-slug',
            'slugen' => '',
            'slugru' => '',
            'productsjson' => '{}',
            'settingsjson' => '{}',
        ], 2);
        $repository->save_block($first, [
            'blocktype' => 'hero',
            'enabled' => true,
            'configjson' => '{}',
        ], 2);

        $second = $repository->save([
            'showroomkey' => 'second-showroom',
            'status' => 'draft',
            'name' => 'Second',
            'template' => 'local_subscriptions/showroom/third_group_verbs',
            'slugfr' => 'shared-showroom-slug',
            'slugen' => '',
            'slugru' => '',
            'productsjson' => '{}',
            'settingsjson' => '{}',
        ], 2);
        $repository->save_block($second, [
            'blocktype' => 'hero',
            'enabled' => true,
            'configjson' => '{}',
        ], 2);

        $publication = new CommerceShowroomPublicationService(
            $DB,
            $repository
        );
        $publication->submit_for_review($second, 2);

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(
            get_string(
                'commerce_showroom_publish_slug_conflict',
                'local_subscriptions',
                'shared-showroom-slug'
            )
        );
        $publication->publish($second, 2);
    }

    public function test_publication_rejects_reserved_commerce_slug(): void {
        global $DB;
        $this->resetAfterTest(true);

        $repository = new CommerceShowroomCmsRepository($DB);
        $id = $repository->save([
            'showroomkey' => 'reserved-route-showroom',
            'status' => 'draft',
            'name' => 'Reserved',
            'template' => 'local_subscriptions/showroom/third_group_verbs',
            'slugfr' => 'boutique',
            'slugen' => '',
            'slugru' => '',
            'productsjson' => '{}',
            'settingsjson' => '{}',
        ], 2);
        $repository->save_block($id, [
            'blocktype' => 'hero',
            'enabled' => true,
            'configjson' => '{}',
        ], 2);

        $publication = new CommerceShowroomPublicationService(
            $DB,
            $repository
        );
        $publication->submit_for_review($id, 2);

        try {
            $publication->publish($id, 2);
            self::fail('Reserved public route must not be publishable.');
        } catch (\moodle_exception $exception) {
            self::assertSame(
                'commerce_showroom_publish_slug_conflict',
                $exception->errorcode
            );
        }
    }

    public function test_router_uses_existing_generic_slug_path_and_showroom_precedence(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/public_router.php'
        );

        self::assertStringContainsString(
            'new CommerceShowroomSlugService($DB)',
            $source
        );
        self::assertStringContainsString(
            'find_published_showroom_key($slug)',
            $source
        );

        $showroompos = strpos(
            $source,
            '// J16S12 — dynamic top-level Showroom routing'
        );
        $productpos = strpos(
            $source,
            '$slugservice = new CommerceProductSlugService($DB);'
        );

        self::assertNotFalse($showroompos);
        self::assertNotFalse($productpos);
        self::assertLessThan($productpos, $showroompos);
    }
}
