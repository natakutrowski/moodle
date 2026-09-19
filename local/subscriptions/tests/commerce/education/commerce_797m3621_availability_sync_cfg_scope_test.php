<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\education\access\CommerceCourseAccessConfigurationRepository;
use local_subscriptions\commerce\education\access\CommerceCourseAccessMode;
use local_subscriptions\commerce\education\access\CommercePedagogicalAvailabilitySynchronizer;

final class commerce_797m3621_availability_sync_cfg_scope_test extends advanced_testcase {
    public function test_sync_course_can_rebuild_cache_after_mode_change(): void {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course([
            'format' => 'topics',
            'numsections' => 1,
        ]);

        $repository = CommerceCourseAccessConfigurationRepository::create($DB);
        $repository->set_mode(
            (int)$course->id,
            CommerceCourseAccessMode::PROMOTION
        );

        $synchronizer = CommercePedagogicalAvailabilitySynchronizer::create($DB);
        $synchronizer->sync_course((int)$course->id);

        $section = $DB->get_record(
            'course_sections',
            [
                'course' => (int)$course->id,
                'section' => 1,
            ],
            '*',
            MUST_EXIST
        );

        self::assertNotNull($section->availability);
        self::assertStringContainsString(
            '"type":"campusfr"',
            (string)$section->availability
        );
    }

    public function test_core_course_library_is_required_with_cfg_in_method_scope(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root
            . '/classes/commerce/education/access/'
            . 'CommercePedagogicalAvailabilitySynchronizer.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString('global $CFG;', $source);
        self::assertStringContainsString(
            "require_once(\$CFG->dirroot . '/course/lib.php');",
            $source
        );
        self::assertStringNotContainsString(
            "require_once(__DIR__ . '/../../../../../../course/lib.php');",
            $source
        );
    }
}
