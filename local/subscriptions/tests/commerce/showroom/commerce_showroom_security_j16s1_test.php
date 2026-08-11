<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPublishedDefinitionResolver;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomRuntimeBlockSet;

final class commerce_showroom_security_j16s1_test extends \advanced_testcase {
    /**
     * @dataProvider non_public_status_provider
     */
    public function test_public_resolution_fails_closed(?string $status): void {
        global $DB;
        $this->resetAfterTest(true);

        if ($status !== null) {
            $repository = new CommerceShowroomCmsRepository($DB);
            $showroomid = $repository->save([
                'showroomkey' => 'third-group-verbs',
                'status' => $status,
                'name' => 'Security test',
                'template' => 'local_subscriptions/showroom/third_group_verbs',
                'productsjson' => '{}',
                'settingsjson' => '{}',
            ], 2);
            $repository->save_block($showroomid, [
                'blocktype' => 'hero',
                'enabled' => true,
                'configjson' => '{}',
            ], 2);
        }

        $this->expectException(\moodle_exception::class);
        (new CommerceShowroomPublishedDefinitionResolver($DB))
            ->require('third-group-verbs');
    }

    public static function non_public_status_provider(): array {
        return [
            'missing CMS record' => [null],
            'draft' => ['draft'],
            'review' => ['review'],
            'archived' => ['archived'],
        ];
    }

    public function test_published_showroom_without_enabled_blocks_fails_closed(): void {
        global $DB;
        $this->resetAfterTest(true);

        $repository = new CommerceShowroomCmsRepository($DB);
        $showroomid = $repository->save([
            'showroomkey' => 'third-group-verbs',
            'status' => 'published',
            'name' => 'Security test',
            'template' => 'local_subscriptions/showroom/third_group_verbs',
            'productsjson' => '{}',
            'settingsjson' => '{}',
        ], 2);
        $repository->save_block($showroomid, [
            'blocktype' => 'hero',
            'enabled' => false,
            'configjson' => '{}',
        ], 2);

        $this->expectException(\moodle_exception::class);
        (new CommerceShowroomPublishedDefinitionResolver($DB))
            ->require('third-group-verbs');
    }

    public function test_published_showroom_with_enabled_block_is_public(): void {
        global $DB;
        $this->resetAfterTest(true);

        $repository = new CommerceShowroomCmsRepository($DB);
        $showroomid = $repository->save([
            'showroomkey' => 'third-group-verbs',
            'status' => 'published',
            'name' => 'Security test',
            'template' => 'local_subscriptions/showroom/third_group_verbs',
            'productsjson' => '{}',
            'settingsjson' => '{}',
        ], 2);
        $repository->save_block($showroomid, [
            'blocktype' => 'hero',
            'enabled' => true,
            'configjson' => '{}',
        ], 2);

        self::assertSame(
            'third-group-verbs',
            (new CommerceShowroomPublishedDefinitionResolver($DB))
                ->require('third-group-verbs')
                ->get_key()
        );
    }

    public function test_runtime_missing_state_never_falls_back_to_full_legacy_page(): void {
        global $DB;
        $this->resetAfterTest(true);

        $runtime = CommerceShowroomRuntimeBlockSet::load($DB, 'third-group-verbs');

        self::assertTrue($runtime->is_managed());
        self::assertSame([], $runtime->sequence());
        self::assertFalse($runtime->is_enabled('hero'));
        self::assertFalse($runtime->is_enabled('offers'));
        self::assertFalse($runtime->is_enabled('final_cta'));
    }

    public function test_archiving_immediately_revokes_public_resolution(): void {
        global $DB;
        $this->resetAfterTest(true);

        $repository = new CommerceShowroomCmsRepository($DB);
        $showroomid = $repository->save([
            'showroomkey' => 'third-group-verbs',
            'status' => 'published',
            'name' => 'Security test',
            'template' => 'local_subscriptions/showroom/third_group_verbs',
            'productsjson' => '{}',
            'settingsjson' => '{}',
        ], 2);
        $repository->save_block($showroomid, [
            'blocktype' => 'hero',
            'enabled' => true,
            'configjson' => '{}',
        ], 2);

        $resolver = new CommerceShowroomPublishedDefinitionResolver($DB);
        self::assertSame('third-group-verbs', $resolver->require('third-group-verbs')->get_key());

        $repository->save([
            'id' => $showroomid,
            'showroomkey' => 'third-group-verbs',
            'status' => 'archived',
            'name' => 'Security test',
            'template' => 'local_subscriptions/showroom/third_group_verbs',
            'productsjson' => '{}',
            'settingsjson' => '{}',
        ], 2);

        $this->expectException(\moodle_exception::class);
        $resolver->require('third-group-verbs');
    }
}
