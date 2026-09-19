<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\education\access\CommerceCourseAccessConfigurationRepository;
use local_subscriptions\commerce\education\access\CommerceCourseAccessMode;
use local_subscriptions\commerce\education\access\CommerceStudentAccessProfile;
use local_subscriptions\commerce\education\access\CommerceStudentCourseAccess;
use local_subscriptions\commerce\education\access\CommerceStudentCourseAccessRepository;
use local_subscriptions\commerce\education\access\CommerceStudentCourseAccessResolver;
use local_subscriptions\commerce\education\calendar\CommercePedagogicalCalendarItem;
use local_subscriptions\commerce\education\calendar\CommercePedagogicalCalendarRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;

final class commerce_797e_student_access_resolver_test extends advanced_testcase {
    private function create_promotion(
        int $courseid
    ): CommercePedagogicalPromotion {
        global $DB;

        $now = time();

        return CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                null,
                'access-' . $courseid,
                'Access promotion',
                $courseid,
                CommercePedagogicalPromotionStatus::STARTED,
                true,
                null,
                null,
                $now,
                null,
                null,
                null,
                null,
                $now,
                $now
            )
        );
    }

    public function test_classic_course_remains_full_access_without_individual_row(): void {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course(
            ['numsections' => 2]
        );
        $user = $this->getDataGenerator()->create_user();

        $decision =
            CommerceStudentCourseAccessResolver::create($DB)
                ->resolve(
                    (int)$course->id,
                    (int)$user->id,
                    time()
                );

        self::assertTrue(
            $decision->has_full_course_access()
        );
        self::assertSame(
            CommerceStudentAccessProfile::LIFETIME_FULL,
            $decision->get_profile()
        );
    }

    public function test_promotion_course_without_797_relation_is_legacy_full(): void {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course(
            ['numsections' => 2]
        );
        $user = $this->getDataGenerator()->create_user();

        CommerceCourseAccessConfigurationRepository::create($DB)
            ->set_mode(
                (int)$course->id,
                CommerceCourseAccessMode::PROMOTION
            );

        $decision =
            CommerceStudentCourseAccessResolver::create($DB)
                ->resolve(
                    (int)$course->id,
                    (int)$user->id,
                    time()
                );

        self::assertTrue(
            $decision->has_full_course_access()
        );
        self::assertSame(
            CommerceStudentAccessProfile::LEGACY_FULL,
            $decision->get_profile()
        );
        self::assertNull(
            CommerceStudentCourseAccessRepository::create($DB)
                ->find(
                    (int)$course->id,
                    (int)$user->id
                )
        );
    }

    public function test_progressive_student_only_gets_unlocked_sections(): void {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course(
            ['numsections' => 3]
        );
        $user = $this->getDataGenerator()->create_user();

        CommerceCourseAccessConfigurationRepository::create($DB)
            ->set_mode(
                (int)$course->id,
                CommerceCourseAccessMode::PROMOTION
            );

        $promotion = $this->create_promotion(
            (int)$course->id
        );

        $sections = array_values(
            $DB->get_records_select(
                'course_sections',
                'course = :course AND section > 0',
                ['course' => (int)$course->id],
                'section ASC'
            )
        );

        $now = time();
        $calendar =
            CommercePedagogicalCalendarRepository::create($DB);

        foreach (
            [$now - 10, $now + 100, $now + 200]
            as $index => $unlocksat
        ) {
            $calendar->save(
                new CommercePedagogicalCalendarItem(
                    null,
                    (int)$promotion->get_id(),
                    CommercePedagogicalCalendarItem::TYPE_COURSE_SECTION,
                    (int)$sections[$index]->id,
                    $index,
                    $unlocksat,
                    null,
                    null,
                    $now,
                    $now
                )
            );
        }

        CommerceStudentCourseAccessRepository::create($DB)
            ->save(
                new CommerceStudentCourseAccess(
                    null,
                    (int)$course->id,
                    (int)$user->id,
                    (int)$promotion->get_id(),
                    CommerceStudentAccessProfile::PROMOTION_PROGRESSIVE,
                    null,
                    null,
                    $now,
                    $now
                )
            );

        $decision =
            CommerceStudentCourseAccessResolver::create($DB)
                ->resolve(
                    (int)$course->id,
                    (int)$user->id,
                    $now
                );

        self::assertFalse(
            $decision->has_full_course_access()
        );
        self::assertSame(
            CommerceStudentAccessProfile::PROMOTION_PROGRESSIVE,
            $decision->get_profile()
        );
        self::assertSame(
            [(int)$sections[0]->id],
            $decision->get_unlocked_section_ids()
        );
        self::assertTrue(
            $decision->can_access_section(
                (int)$sections[0]->id,
                1
            )
        );
        self::assertFalse(
            $decision->can_access_section(
                (int)$sections[1]->id,
                2
            )
        );
        self::assertTrue(
            $decision->can_access_section(
                0,
                0
            )
        );
    }

    public function test_completed_calendar_promotes_student_to_lifetime_full(): void {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course(
            ['numsections' => 2]
        );
        $user = $this->getDataGenerator()->create_user();

        CommerceCourseAccessConfigurationRepository::create($DB)
            ->set_mode(
                (int)$course->id,
                CommerceCourseAccessMode::PROMOTION
            );

        $promotion = $this->create_promotion(
            (int)$course->id
        );

        $sections = array_values(
            $DB->get_records_select(
                'course_sections',
                'course = :course AND section > 0',
                ['course' => (int)$course->id],
                'section ASC'
            )
        );

        $now = time();
        $calendar =
            CommercePedagogicalCalendarRepository::create($DB);

        foreach ($sections as $index => $section) {
            $calendar->save(
                new CommercePedagogicalCalendarItem(
                    null,
                    (int)$promotion->get_id(),
                    CommercePedagogicalCalendarItem::TYPE_COURSE_SECTION,
                    (int)$section->id,
                    $index,
                    $now - 100,
                    null,
                    null,
                    $now,
                    $now
                )
            );
        }

        CommerceStudentCourseAccessRepository::create($DB)
            ->save(
                new CommerceStudentCourseAccess(
                    null,
                    (int)$course->id,
                    (int)$user->id,
                    (int)$promotion->get_id(),
                    CommerceStudentAccessProfile::PROMOTION_PROGRESSIVE,
                    null,
                    null,
                    $now,
                    $now
                )
            );

        $decision =
            CommerceStudentCourseAccessResolver::create($DB)
                ->resolve(
                    (int)$course->id,
                    (int)$user->id,
                    $now
                );

        self::assertTrue(
            $decision->has_full_course_access()
        );
        self::assertSame(
            CommerceStudentAccessProfile::LIFETIME_FULL,
            $decision->get_profile()
        );

        $persisted =
            CommerceStudentCourseAccessRepository::create($DB)
                ->find(
                    (int)$course->id,
                    (int)$user->id
                );

        self::assertNotNull($persisted);
        self::assertSame(
            CommerceStudentAccessProfile::LIFETIME_FULL,
            $persisted->get_profile()
        );
    }

    public function test_progressive_relation_rejects_promotion_from_another_course(): void {
        global $DB;

        $this->resetAfterTest(true);

        $coursea = $this->getDataGenerator()->create_course();
        $courseb = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $promotion = $this->create_promotion(
            (int)$coursea->id
        );

        $this->expectException(
            \coding_exception::class
        );

        CommerceStudentCourseAccessRepository::create($DB)
            ->save(
                new CommerceStudentCourseAccess(
                    null,
                    (int)$courseb->id,
                    (int)$user->id,
                    (int)$promotion->get_id(),
                    CommerceStudentAccessProfile::PROMOTION_PROGRESSIVE,
                    null,
                    null,
                    time(),
                    time()
                )
            );
    }

    public function test_797e_keeps_decision_individual_and_does_not_write_global_course_availability(): void {
        $root = dirname(__DIR__, 3);

        $resolver = file_get_contents(
            $root
            . '/classes/commerce/education/access/'
            . 'CommerceStudentCourseAccessResolver.php'
        );
        $upgrade = file_get_contents(
            $root . '/db/upgrade.php'
        );

        self::assertStringContainsString(
            'CommerceStudentAccessProfile::LEGACY_FULL',
            $resolver
        );
        self::assertStringContainsString(
            'CommerceStudentAccessProfile::PROMOTION_PROGRESSIVE',
            $resolver
        );
        self::assertStringContainsString(
            'CommerceStudentAccessProfile::LIFETIME_FULL',
            $resolver
        );
        self::assertStringNotContainsString(
            "update_record('course_sections'",
            $resolver
        );
        self::assertStringNotContainsString(
            "update_record('course'",
            $resolver
        );
        self::assertStringContainsString(
            "'local_subs_commerce_ped_access'",
            $upgrade
        );
    }
}
