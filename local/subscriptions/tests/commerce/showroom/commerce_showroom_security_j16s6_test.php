<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPublicationService;

final class commerce_showroom_security_j16s6_test extends \advanced_testcase {
    public function test_publish_requires_review_and_enabled_block(): void {
        global $DB;
        $this->resetAfterTest(true);

        $repository = new CommerceShowroomCmsRepository($DB);
        $id = $repository->save([
            'showroomkey' => 'third-group-verbs',
            'status' => 'draft',
            'name' => 'Security',
            'template' => 'local_subscriptions/showroom/third_group_verbs',
            'slugfr' => 'security-showroom',
            'productsjson' => '{}',
            'settingsjson' => '{}',
        ], 2);

        $publication = new CommerceShowroomPublicationService($DB, $repository);

        try {
            $publication->publish($id, 2);
            self::fail('Draft must not be directly publishable.');
        } catch (\moodle_exception $exception) {
            self::assertSame(
                'commerce_showroom_invalid_transition',
                $exception->errorcode
            );
        }

        $publication->submit_for_review($id, 2);

        try {
            $publication->publish($id, 2);
            self::fail('Empty showroom must not be publishable.');
        } catch (\moodle_exception $exception) {
            self::assertSame(
                'commerce_showroom_publish_requires_block',
                $exception->errorcode
            );
        }

        $repository->save_block($id, [
            'blocktype' => 'hero',
            'enabled' => true,
            'configjson' => '{"title":"Security hero"}',
        ], 2);

        $publication->publish($id, 2);

        self::assertSame(
            'published',
            (string)$repository->get($id)->status
        );
    }

    public function test_general_builder_form_cannot_write_status_directly(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/showrooms/edit.php'
        );

        self::assertIsString($source);
        self::assertStringNotContainsString(
            "required_param('status', PARAM_ALPHA)",
            $source
        );
        self::assertStringNotContainsString(
            "html_writer::select(\n"
            . "    CommerceShowroomStatus::options(),\n"
            . "    'status'",
            $source
        );
        self::assertStringContainsString(
            "'status' => \$record !== null",
            $source
        );
        self::assertStringContainsString(
            ': CommerceShowroomStatus::DRAFT',
            $source
        );
        self::assertStringContainsString(
            "\$workflowaction === 'review'",
            $source
        );
        self::assertStringContainsString(
            "\$workflowaction === 'publish'",
            $source
        );
    }

    public function test_invalid_workflow_transitions_are_rejected(): void {
        global $DB;
        $this->resetAfterTest(true);

        $repository = new CommerceShowroomCmsRepository($DB);
        $id = $repository->save([
            'showroomkey' => 'third-group-verbs',
            'status' => 'draft',
            'name' => 'Security',
            'template' => 'local_subscriptions/showroom/third_group_verbs',
            'productsjson' => '{}',
            'settingsjson' => '{}',
        ], 2);

        $publication = new CommerceShowroomPublicationService($DB, $repository);

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(
            get_string(
                'commerce_showroom_invalid_transition',
                'local_subscriptions'
            )
        );
        $publication->return_to_draft($id, 2);
    }
}
