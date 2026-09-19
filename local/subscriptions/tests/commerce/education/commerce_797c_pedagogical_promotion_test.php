<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;

final class commerce_797c_pedagogical_promotion_test extends advanced_testcase {
    public function test_repository_persists_course_lifecycle_dates_and_capacity(): void {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $now = time();

        $promotion = new CommercePedagogicalPromotion(
            null,
            'a1-oct-2026',
            'A1 — Octobre 2026',
            (int)$course->id,
            CommercePedagogicalPromotionStatus::SCHEDULED,
            true,
            $now + 3600,
            $now + 86400,
            $now + 172800,
            null,
            120,
            (int)$user->id,
            (int)$user->id,
            $now,
            $now
        );

        $saved =
            CommercePedagogicalPromotionRepository::create($DB)
                ->save($promotion);

        self::assertNotNull($saved->get_id());
        self::assertSame(
            'a1-oct-2026',
            $saved->get_promotion_key()
        );
        self::assertSame(
            (int)$course->id,
            $saved->get_course_id()
        );
        self::assertSame(
            CommercePedagogicalPromotionStatus::SCHEDULED,
            $saved->get_status()
        );
        self::assertTrue($saved->is_published());
        self::assertSame(
            120,
            $saved->get_capacity_total()
        );
    }

    public function test_invalid_windows_and_capacity_are_rejected(): void {
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $now = time();

        $this->expectException(\coding_exception::class);

        new CommercePedagogicalPromotion(
            null,
            'invalid-window',
            'Invalid',
            (int)$course->id,
            CommercePedagogicalPromotionStatus::DRAFT,
            false,
            $now + 7200,
            $now + 3600,
            null,
            null,
            10,
            null,
            null,
            $now,
            $now
        );
    }

    public function test_sales_window_requires_publication_and_compatible_status(): void {
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $now = time();

        $open = new CommercePedagogicalPromotion(
            null,
            'open',
            'Open',
            (int)$course->id,
            CommercePedagogicalPromotionStatus::OPEN,
            true,
            $now - 100,
            $now + 100,
            $now + 200,
            null,
            null,
            null,
            null,
            $now,
            $now
        );

        self::assertTrue(
            $open->sales_are_open($now)
        );

        $draft = new CommercePedagogicalPromotion(
            null,
            'draft',
            'Draft',
            (int)$course->id,
            CommercePedagogicalPromotionStatus::DRAFT,
            true,
            $now - 100,
            $now + 100,
            null,
            null,
            null,
            null,
            null,
            $now,
            $now
        );

        self::assertFalse(
            $draft->sales_are_open($now)
        );
    }

    public function test_admin_course_names_use_moodle_format_string_for_multilang_content(): void {
        $root = dirname(__DIR__, 3);

        $courses = file_get_contents(
            $root . '/admin/commerce/education/courses.php'
        );
        $promotions = file_get_contents(
            $root . '/admin/commerce/education/promotions.php'
        );
        $edit = file_get_contents(
            $root . '/admin/commerce/education/promotion_edit.php'
        );

        foreach (
            [$courses, $promotions, $edit]
            as $source
        ) {
            self::assertIsString($source);
            self::assertStringContainsString(
                'format_string(',
                $source
            );
            self::assertStringContainsString(
                'context_course::instance(',
                $source
            );
        }

        self::assertStringNotContainsString(
            's((string)$course->fullname)',
            $courses
        );
    }

    public function test_pedagogical_promotion_stays_separate_from_commercial_discount_promotion(): void {
        self::assertTrue(
            class_exists(
                \local_subscriptions\commerce\promotion\domain\CommercePromotion::class
            )
        );
        self::assertTrue(
            class_exists(
                CommercePedagogicalPromotion::class
            )
        );
        self::assertNotSame(
            \local_subscriptions\commerce\promotion\domain\CommercePromotion::class,
            CommercePedagogicalPromotion::class
        );
    }
}
