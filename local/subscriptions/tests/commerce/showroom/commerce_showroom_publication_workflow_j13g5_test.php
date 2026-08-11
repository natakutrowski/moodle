<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPublicationService;

final class commerce_showroom_publication_workflow_j13g5_test extends \advanced_testcase {
    public function test_publication_creates_revision_and_restore_returns_to_draft(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $repository = new CommerceShowroomCmsRepository($DB);
        $showroomid = $repository->save([
            'showroomkey' => 'workflow-test',
            'status' => 'draft',
            'name' => 'Workflow test',
            'template' => 'local_subscriptions/showroom/third_group_verbs',
            'productsjson' => '{}',
            'settingsjson' => '{}',
        ], (int)$user->id);
        $repository->save_block($showroomid, [
            'blocktype' => 'hero',
            'enabled' => true,
            'configjson' => '{"title":"Version one"}',
        ], (int)$user->id);

        $service = new CommerceShowroomPublicationService($DB, $repository);
        $revisionid = $service->publish($showroomid, (int)$user->id, 'First publication');
        $this->assertGreaterThan(0, $revisionid);
        $this->assertSame('published', $repository->get($showroomid)->status);
        $this->assertCount(1, $service->revisions($showroomid));

        $service->restore($showroomid, $revisionid, (int)$user->id);
        $this->assertSame('draft', $repository->get($showroomid)->status);
        $this->assertCount(2, $service->revisions($showroomid));
    }
}
