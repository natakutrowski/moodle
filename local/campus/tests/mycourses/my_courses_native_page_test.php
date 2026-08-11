<?php

declare(strict_types=1);

namespace local_campus;

defined('MOODLE_INTERNAL') || die();

use local_campus\mycourses\MyCoursePresentation;
use local_campus\mycourses\MyCoursesCollection;
use local_campus\output\mycourses\MyCoursesPage;
use local_subscriptions\commerce\course\library\CommerceCourseAccessPeriod;
use local_subscriptions\commerce\course\library\CommerceCourseAccessPresentation;

/** Tests the native CampusFR My courses presentation contract. */
final class my_courses_native_page_test extends \advanced_testcase {
    public function test_page_exports_native_course_cards_without_edly_renderer_contract(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course([
            'fullname' => 'Cours CampusFR A1',
            'summary' => '<p>Une présentation claire du cours.</p>',
            'summaryformat' => FORMAT_HTML,
        ]);

        $access = new CommerceCourseAccessPresentation(
            (int)$course->id,
            'purchase',
            new CommerceCourseAccessPeriod(null, null, true),
            '',
            '',
            '',
            '',
            'native',
            []
        );
        $item = new MyCoursePresentation($course, 42.5, 17, 40, false, false, $access);
        $page = new MyCoursesPage(new MyCoursesCollection([$item]));

        $context = $page->export_for_template($this->get_renderer());
        $card = $context['categories'][0]['courses'][0];

        $this->assertTrue($context['hascourses']);
        $this->assertSame('Cours CampusFR A1', $card['title']);
        $this->assertSame(42.5, $card['progress']['percentage']);
        $this->assertTrue($card['progress']['hascounts']);
        $this->assertSame(get_string('course_access_lifetime', 'local_campus'), $card['accesslabel']);
        $this->assertArrayNotHasKey('purchaseurl', $card);
        $this->assertArrayNotHasKey('commercialreference', $card);
    }

    private function get_renderer(): \renderer_base {
        global $PAGE;
        $PAGE->set_context(\context_system::instance());
        return $PAGE->get_renderer('core');
    }
}
