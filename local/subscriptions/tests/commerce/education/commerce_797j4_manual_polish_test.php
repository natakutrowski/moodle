<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\education\access\CommerceCourseAccessConfigurationRepository;
use local_subscriptions\commerce\education\access\CommerceCourseAccessMode;

final class commerce_797j4_manual_polish_test extends advanced_testcase {
    public function test_promotion_course_picker_source_is_filtered_to_promotion_courses(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/education/promotion_edit.php'
        );

        self::assertStringContainsString(
            'promotion_course_ids()',
            $source
        );
        self::assertStringContainsString(
            'CommerceCourseAccessMode::PROMOTION',
            $source
        );
        self::assertStringNotContainsString(
            "'id <> :siteid'",
            $source
        );
    }

    public function test_published_checkbox_is_cast_to_strict_bool_for_domain_constructor(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/education/promotion_edit.php'
        );

        self::assertStringContainsString(
            "(bool)optional_param(\n            'published'",
            $source
        );
    }

    public function test_course_mode_page_uses_one_global_save_form(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/education/courses.php'
        );

        self::assertStringContainsString(
            "optional_param_array(\n        'accessmode'",
            $source
        );
        self::assertStringContainsString(
            <<<'PHP'
'accessmode[' . (int)$course->id . ']'
PHP,
            $source
        );
        self::assertSame(
            1,
            substr_count($source, "'type' => 'submit'")
        );
    }

    public function test_repository_lists_only_explicit_promotion_courses(): void {
        global $DB;

        $this->resetAfterTest(true);

        $classic = $this->getDataGenerator()->create_course();
        $promotion = $this->getDataGenerator()->create_course();

        $repository =
            CommerceCourseAccessConfigurationRepository::create($DB);

        $repository->set_mode(
            (int)$promotion->id,
            CommerceCourseAccessMode::PROMOTION
        );

        self::assertSame(
            [(int)$promotion->id],
            $repository->promotion_course_ids()
        );
        self::assertSame(
            CommerceCourseAccessMode::CLASSIC_IMMEDIATE,
            $repository->mode_for_course((int)$classic->id)
        );
    }

    public function test_obsolete_next_phase_copy_is_gone(): void {
        $root = dirname(__DIR__, 3);

        foreach (['fr', 'en', 'ru'] as $lang) {
            $source = file_get_contents(
                $root . '/lang/' . $lang . '/local_subscriptions.php'
            );

            self::assertStringNotContainsString(
                'next 7.97 phases',
                $source
            );
            self::assertStringNotContainsString(
                'prochaines phases 7.97',
                $source
            );
            self::assertStringNotContainsString(
                'следующих этапах 7.97',
                $source
            );
        }
    }
}
