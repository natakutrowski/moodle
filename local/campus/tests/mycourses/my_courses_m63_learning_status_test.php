<?php

declare(strict_types=1);

namespace local_campus;

defined('MOODLE_INTERNAL') || die();

use local_campus\mycourses\MyCoursePresentation;
use local_campus\mycourses\MyCoursesCollection;
use local_campus\output\mycourses\MyCoursesPage;
use local_subscriptions\commerce\course\library\CommerceCourseAccessPeriod;
use local_subscriptions\commerce\course\library\CommerceCourseAccessPresentation;

final class my_courses_m63_learning_status_test extends \advanced_testcase {
    public function test_course_card_exposes_promotion_start_next_lesson_and_progressive_access(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course([
            'fullname' => 'CampusFR A1',
            'summary' => 'A1',
        ]);
        $now = time();
        $access = new CommerceCourseAccessPresentation(
            (int)$course->id,
            'purchase',
            new CommerceCourseAccessPeriod(null, null, true),
            null,
            null,
            null,
            null,
            'native',
            []
        );
        $learning = [
            'haspromotion' => true,
            'promotionname' => 'Octobre 2026',
            'hasfullaccess' => false,
            'isprogressive' => true,
            'promotionstartsat' => $now + DAYSECS,
            'hasnextlesson' => true,
            'nextunlocksat' => $now + (2 * DAYSECS),
            'nextsectionname' => 'Nice — Leçon 1',
            'nextsectionnumber' => 1,
        ];
        $item = new MyCoursePresentation(
            $course,
            10.0,
            3,
            30,
            false,
            false,
            $access,
            $learning
        );
        $page = new MyCoursesPage(new MyCoursesCollection([$item]));
        $context = $page->export_for_template($this->renderer());
        $card = $context['categories'][0]['courses'][0];

        self::assertSame(get_string('mycourses_learning_access_progressive', 'local_campus'), $card['accesslabel']);
        self::assertSame('progressive', $card['accessstate']);
        self::assertTrue($card['learning']['hasdetails']);
        $labels = array_column($card['learning']['items'], 'label');
        self::assertContains(get_string('mycourses_learning_promotion', 'local_campus', 'Octobre 2026'), $labels);
        self::assertContains(get_string('mycourses_learning_access_progressive', 'local_campus'), $labels);
        self::assertTrue((bool)array_filter($labels, static fn(string $label): bool => str_contains($label, 'Nice — Leçon 1')));
    }

    public function test_owner_promotion_context_does_not_downgrade_lifetime_access_badge(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['fullname' => 'CampusFR Owner']);
        $access = new CommerceCourseAccessPresentation(
            (int)$course->id,
            'purchase',
            new CommerceCourseAccessPeriod(null, null, true)
        );
        $item = new MyCoursePresentation(
            $course,
            0.0,
            0,
            10,
            false,
            false,
            $access,
            [
                'haspromotion' => true,
                'promotionname' => 'Owner cohort',
                'hasfullaccess' => true,
                'isprogressive' => false,
                'promotionstartsat' => time() + DAYSECS,
                'hasnextlesson' => false,
            ]
        );
        $page = new MyCoursesPage(new MyCoursesCollection([$item]));
        $context = $page->export_for_template($this->renderer());
        $card = $context['categories'][0]['courses'][0];

        self::assertSame(get_string('course_access_lifetime', 'local_campus'), $card['accesslabel']);
        self::assertSame('lifetime', $card['accessstate']);
        self::assertTrue($card['learning']['hasdetails']);
    }

    public function test_template_has_learning_status_surface(): void {
        global $CFG;
        $template = file_get_contents($CFG->dirroot . '/local/campus/templates/mycourses/components/course_card.mustache');
        self::assertIsString($template);
        self::assertStringContainsString('campus-mycourse-card__learning-status', $template);
        self::assertStringContainsString('{{#learning.items}}', $template);
    }

    private function renderer(): \renderer_base {
        global $PAGE;
        $PAGE->set_context(\context_system::instance());
        return $PAGE->get_renderer('core');
    }
}
