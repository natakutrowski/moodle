<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\education\access\CommerceStudentAccessProfile;
use local_subscriptions\commerce\education\access\CommerceStudentCourseAccess;
use local_subscriptions\commerce\education\access\CommerceStudentCourseAccessRepository;
use local_subscriptions\commerce\education\access\CommercePedagogicalSectionLockPresentation;
use local_subscriptions\commerce\education\calendar\CommercePedagogicalCalendarItem;
use local_subscriptions\commerce\education\calendar\CommercePedagogicalCalendarRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;

final class commerce_797j16_lock_presentation_test extends advanced_testcase {
    public function test_helper_returns_future_unlock_for_progressive_section(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();

        $course = $this->getDataGenerator()->create_course(['numsections' => 1]);
        $user = $this->getDataGenerator()->create_user();
        $section = $DB->get_record(
            'course_sections',
            ['course' => (int)$course->id, 'section' => 1],
            '*',
            MUST_EXIST
        );

        $promotion = CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                null,
                'j16-lock',
                'J16 lock',
                (int)$course->id,
                CommercePedagogicalPromotionStatus::OPEN,
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

        CommerceStudentCourseAccessRepository::create($DB)->save(
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

        $unlockat = $now + 3600;
        CommercePedagogicalCalendarRepository::create($DB)->save(
            new CommercePedagogicalCalendarItem(
                null,
                (int)$promotion->get_id(),
                CommercePedagogicalCalendarItem::TYPE_COURSE_SECTION,
                (int)$section->id,
                0,
                $unlockat,
                null,
                null,
                $now,
                $now
            )
        );

        self::assertSame(
            $unlockat,
            CommercePedagogicalSectionLockPresentation::create($DB)
                ->unlock_at(
                    (int)$course->id,
                    (int)$user->id,
                    (int)$section->id,
                    $now
                )
        );
    }

    public function test_availability_condition_emits_rich_lock_marker(): void {
        $moodleroot = dirname(__DIR__, 5);
        $source = file_get_contents(
            $moodleroot . '/availability/condition/campusfr/classes/condition.php'
        );

        self::assertStringContainsString(
            'campusfr-pedagogical-lock',
            $source
        );
        self::assertStringContainsString(
            "'data-unlockat'",
            $source
        );
        self::assertStringContainsString(
            'section_cover.php',
            $source
        );
        self::assertStringContainsString(
            "'unlockat'",
            $source
        );
    }
}
