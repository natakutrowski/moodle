<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\education\access\CommerceCourseAccessConfigurationRepository;
use local_subscriptions\commerce\education\access\CommerceCourseAccessMode;

final class commerce_797b_course_access_mode_test extends advanced_testcase {
    public function test_unconfigured_existing_course_defaults_to_classic_immediate(): void {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $repository =
            CommerceCourseAccessConfigurationRepository::create($DB);

        self::assertSame(
            CommerceCourseAccessMode::CLASSIC_IMMEDIATE,
            $repository->mode_for_course((int)$course->id)
        );
        self::assertFalse(
            $DB->record_exists(
                'local_subs_commerce_course_cfg',
                ['courseid' => (int)$course->id]
            )
        );
    }

    public function test_course_can_be_switched_to_promotion_and_back_to_classic(): void {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $repository =
            CommerceCourseAccessConfigurationRepository::create($DB);

        $repository->set_mode(
            (int)$course->id,
            CommerceCourseAccessMode::PROMOTION,
            (int)$user->id
        );

        self::assertSame(
            CommerceCourseAccessMode::PROMOTION,
            $repository->mode_for_course((int)$course->id)
        );

        $record = $DB->get_record(
            'local_subs_commerce_course_cfg',
            ['courseid' => (int)$course->id],
            '*',
            MUST_EXIST
        );

        self::assertSame(
            CommerceCourseAccessMode::PROMOTION,
            (string)$record->accessmode
        );
        self::assertSame(
            (int)$user->id,
            (int)$record->modifiedby
        );

        $repository->set_mode(
            (int)$course->id,
            CommerceCourseAccessMode::CLASSIC_IMMEDIATE,
            (int)$user->id
        );

        self::assertSame(
            CommerceCourseAccessMode::CLASSIC_IMMEDIATE,
            $repository->mode_for_course((int)$course->id)
        );
        self::assertSame(
            1,
            $DB->count_records(
                'local_subs_commerce_course_cfg',
                ['courseid' => (int)$course->id]
            )
        );
    }

    public function test_unknown_course_cannot_be_configured(): void {
        global $DB;

        $this->resetAfterTest(true);

        $repository =
            CommerceCourseAccessConfigurationRepository::create($DB);

        $this->expectException(\coding_exception::class);

        $repository->set_mode(
            999999999,
            CommerceCourseAccessMode::PROMOTION
        );
    }

    public function test_admin_page_exposes_explicit_classic_and_promotion_modes(): void {
        $root = dirname(__DIR__, 3);

        $page = file_get_contents(
            $root . '/admin/commerce/education/courses.php'
        );
        $navigation = file_get_contents(
            $root
            . '/classes/crm/commerce/navigation/'
            . 'CommerceSectionNavigationRegistry.php'
        );

        self::assertIsString($page);
        self::assertIsString($navigation);

        self::assertStringContainsString(
            'CommerceCourseAccessConfigurationRepository',
            $page
        );
        self::assertStringContainsString(
            'CommerceCourseAccessMode::CLASSIC_IMMEDIATE',
            $page
        );
        self::assertStringContainsString(
            'CommerceCourseAccessMode::PROMOTION',
            $page
        );
        self::assertStringContainsString(
            'Capabilities::MANAGE_CONFIGURATION',
            $page
        );
        self::assertStringContainsString(
            "public const EDUCATION = 'education';",
            $navigation
        );
    }

    public function test_schema_is_additive_and_requires_no_legacy_course_backfill(): void {
        $root = dirname(__DIR__, 3);

        $install = file_get_contents($root . '/db/install.xml');
        $upgrade = file_get_contents($root . '/db/upgrade.php');

        self::assertIsString($install);
        self::assertIsString($upgrade);
        self::assertStringContainsString(
            'NAME="local_subs_commerce_course_cfg"',
            $install
        );
        self::assertStringContainsString(
            'DEFAULT="classic_immediate"',
            $install
        );
        self::assertStringContainsString(
            "'local_subs_commerce_course_cfg'",
            $upgrade
        );
        self::assertStringNotContainsString(
            "get_records('course'",
            $upgrade
        );
        self::assertStringNotContainsString(
            "get_records_select('course'",
            $upgrade
        );
    }
}
