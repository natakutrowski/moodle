<?php

declare(strict_types=1);

namespace local_campus;

defined('MOODLE_INTERNAL') || die();

use local_campus\mycourses\MyCoursePresentation;
use local_campus\mycourses\MyCoursesCollection;
use local_campus\output\mycourses\MyCoursesPage;
use local_subscriptions\commerce\course\library\CommerceCourseAccessPeriod;
use local_subscriptions\commerce\course\library\CommerceCourseAccessPresentation;

final class my_courses_m634_learning_datetime_test extends \advanced_testcase {
    public function test_learning_metadata_contains_hours_for_start_and_next_lesson(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course([
            'fullname' => 'M6.3.4 Date and time',
        ]);

        // Use non-zero minutes so a date-only rendering cannot satisfy the assertions.
        $startsat = make_timestamp(2026, 9, 18, 18, 37, 0);
        $nextat = make_timestamp(2026, 9, 19, 9, 42, 0);

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

        $item = new MyCoursePresentation(
            $course,
            0.0,
            0,
            3,
            false,
            false,
            $access,
            [
                'haspromotion' => true,
                'promotionname' => 'M6.3.4',
                'hasfullaccess' => false,
                'isprogressive' => true,
                'promotionstartsat' => $startsat,
                'hasnextlesson' => true,
                'nextunlocksat' => $nextat,
                'nextsectionname' => 'Leçon 2 - Cannes',
                'nextsectionnumber' => 2,
            ]
        );

        $page = new MyCoursesPage(new MyCoursesCollection([$item]));
        $context = $page->export_for_template($this->renderer());
        $labels = array_column(
            $context['categories'][0]['courses'][0]['learning']['items'],
            'label'
        );

        $startformatted = userdate(
            $startsat,
            get_string('strftimedatetimeshort', 'langconfig')
        );
        $nextformatted = userdate(
            $nextat,
            get_string('strftimedatetimeshort', 'langconfig')
        );

        self::assertContains(
            get_string('mycourses_learning_start', 'local_campus', $startformatted),
            $labels
        );

        $next = (object)[
            'lesson' => 'Leçon 2 - Cannes',
            'date' => $nextformatted,
        ];
        self::assertContains(
            get_string('mycourses_learning_next_lesson', 'local_campus', $next),
            $labels
        );
    }

    private function renderer(): \renderer_base {
        global $PAGE;
        $PAGE->set_context(\context_system::instance());
        return $PAGE->get_renderer('core');
    }
}
