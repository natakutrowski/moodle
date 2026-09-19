<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\education\calendar\CommercePedagogicalCalendarItem;
use local_subscriptions\commerce\education\calendar\CommercePedagogicalCalendarRepository;
use local_subscriptions\commerce\education\calendar\CommercePedagogicalCalendarResolver;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;

final class commerce_797d_pedagogical_calendar_test extends advanced_testcase {
    private function promotion_for_course(
        int $courseid
    ): CommercePedagogicalPromotion {
        global $DB;

        $now = time();

        return CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                null,
                'dev-calendar-' . $courseid,
                'DEV calendar',
                $courseid,
                CommercePedagogicalPromotionStatus::DRAFT,
                false,
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

    public function test_three_sections_can_have_independent_release_dates(): void {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course(
            ['numsections' => 3]
        );
        $promotion =
            $this->promotion_for_course(
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

        self::assertCount(3, $sections);

        $repository =
            CommercePedagogicalCalendarRepository::create($DB);
        $now = time();

        foreach ($sections as $index => $section) {
            $repository->save(
                new CommercePedagogicalCalendarItem(
                    null,
                    (int)$promotion->get_id(),
                    CommercePedagogicalCalendarItem::TYPE_COURSE_SECTION,
                    (int)$section->id,
                    $index,
                    $now + ($index * 86400),
                    null,
                    null,
                    $now,
                    $now
                )
            );
        }

        $items = $repository->for_promotion(
            (int)$promotion->get_id()
        );

        self::assertCount(3, $items);
        self::assertSame(
            (int)$sections[0]->id,
            $items[0]->get_item_id()
        );
        self::assertSame(
            $now + 172800,
            $items[2]->get_unlocks_at()
        );
    }

    public function test_resolver_distinguishes_past_present_and_future_items(): void {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course(
            ['numsections' => 3]
        );
        $promotion =
            $this->promotion_for_course(
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

        $repository =
            CommercePedagogicalCalendarRepository::create($DB);
        $now = time();

        foreach (
            [
                $now - 3600,
                $now,
                $now + 3600,
            ]
            as $index => $unlocksat
        ) {
            $repository->save(
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

        $resolver =
            CommercePedagogicalCalendarResolver::create($DB);

        self::assertCount(
            2,
            $resolver->unlocked(
                (int)$promotion->get_id(),
                $now
            )
        );
        self::assertCount(
            1,
            $resolver->locked(
                (int)$promotion->get_id(),
                $now
            )
        );
        self::assertFalse(
            $resolver->is_complete_at(
                (int)$promotion->get_id(),
                $now
            )
        );
        self::assertTrue(
            $resolver->is_complete_at(
                (int)$promotion->get_id(),
                $now + 7200
            )
        );
    }

    public function test_section_from_another_course_is_rejected(): void {
        global $DB;

        $this->resetAfterTest(true);

        $coursea = $this->getDataGenerator()->create_course(
            ['numsections' => 1]
        );
        $courseb = $this->getDataGenerator()->create_course(
            ['numsections' => 1]
        );
        $promotion =
            $this->promotion_for_course(
                (int)$coursea->id
            );

        $foreignsection = $DB->get_record(
            'course_sections',
            [
                'course' => (int)$courseb->id,
                'section' => 1,
            ],
            '*',
            MUST_EXIST
        );

        $this->expectException(
            \coding_exception::class
        );

        CommercePedagogicalCalendarRepository::create($DB)->save(
            new CommercePedagogicalCalendarItem(
                null,
                (int)$promotion->get_id(),
                CommercePedagogicalCalendarItem::TYPE_COURSE_SECTION,
                (int)$foreignsection->id,
                0,
                time(),
                null,
                null,
                time(),
                time()
            )
        );
    }

    public function test_calendar_admin_handles_multilang_section_names_and_has_no_fixed_rhythm(): void {
        $root = dirname(__DIR__, 3);

        $page = file_get_contents(
            $root . '/admin/commerce/education/calendar.php'
        );

        self::assertIsString($page);
        self::assertStringContainsString(
            'format_string(',
            $page
        );
        self::assertStringContainsString(
            "'course_sections'",
            $page
        );
        self::assertStringContainsString(
            "'unlocksat'",
            $page
        );
        self::assertStringNotContainsString(
            '2 lessons',
            strtolower($page)
        );
        self::assertStringNotContainsString(
            '2 leçons',
            strtolower($page)
        );
    }

    public function test_schema_is_explicit_per_item_and_not_global_course_restriction(): void {
        $root = dirname(__DIR__, 3);

        $install = file_get_contents(
            $root . '/db/install.xml'
        );
        $upgrade = file_get_contents(
            $root . '/db/upgrade.php'
        );

        self::assertStringContainsString(
            'NAME="local_subs_commerce_ped_cal"',
            $install
        );
        self::assertStringContainsString(
            'FIELDS="promotionid,itemtype,itemid"',
            $install
        );
        self::assertStringContainsString(
            "'local_subs_commerce_ped_cal'",
            $upgrade
        );

        $architecture = file_get_contents(
            $root . '/docs/commerce/7.97A-architecture.md'
        );
        self::assertStringContainsString(
            'ne doit pas être une restriction globale du cours Moodle',
            $architecture
        );
    }
}
