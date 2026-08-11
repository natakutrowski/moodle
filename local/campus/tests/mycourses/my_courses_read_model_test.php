<?php

declare(strict_types=1);

namespace local_campus;

use local_campus\mycourses\MyCoursePresentation;
use local_campus\mycourses\MyCoursesCollection;
use local_campus\output\mycourses\MyCoursesPage;
use local_subscriptions\commerce\course\library\CommerceCourseAccessOrigin;
use local_subscriptions\commerce\course\library\CommerceCourseAccessPeriod;
use local_subscriptions\commerce\course\library\CommerceCourseAccessPresentation;

final class my_courses_read_model_test extends \advanced_testcase {
    public function test_collection_preserves_enrolments_and_builds_renderer_maps(): void {
        $course1 = (object)['id' => 17, 'category' => 3, 'fullname' => 'A1'];
        $course2 = (object)['id' => 18, 'category' => 3, 'fullname' => 'A2'];
        $collection = new MyCoursesCollection([
            new MyCoursePresentation($course1, 50.0, 5, 10, false, true, CommerceCourseAccessPresentation::unknown(17)),
            new MyCoursePresentation($course2, 100.0, 8, 8, true, false, CommerceCourseAccessPresentation::unknown(18)),
        ]);

        $this->assertCount(2, $collection);
        $this->assertSame([17, 18], array_keys($collection->course_records()));
        $this->assertSame([17 => 50.0, 18 => 100.0], $collection->progress_map());
        $this->assertSame([18], $collection->completed_course_ids());
        $this->assertSame([17 => true], $collection->trial_course_map());
        $this->assertCount(2, $collection->grouped_by_category()[3]);
    }

    public function test_page_exposes_only_customer_facing_access_period(): void {
        $course = (object)['id' => 17, 'category' => 3, 'fullname' => 'A1'];
        $access = new CommerceCourseAccessPresentation(
            17,
            CommerceCourseAccessOrigin::PURCHASE,
            new CommerceCourseAccessPeriod(1_700_000_000, null, true),
            'https://example.test/order',
            'CFR-2026-ABC123'
        );
        $item = new MyCoursePresentation($course, null, null, null, false, false, $access);
        $page = new MyCoursesPage(new MyCoursesCollection([$item]));

        $info = $this->invoke_access_context($page, $item);

        $this->assertFalse($info['trial']);
        $this->assertTrue($info['haslabel']);
        $this->assertSame(get_string('course_access_lifetime', 'local_campus'), $info['label']);
        $this->assertSame('lifetime', $info['state']);
        $this->assertArrayNotHasKey('purchaseurl', $info);
        $this->assertArrayNotHasKey('commercialreference', $info);
    }

    public function test_trial_role_is_used_when_commerce_origin_is_unknown(): void {
        $course = (object)['id' => 18, 'category' => 3, 'fullname' => 'A2'];
        $item = new MyCoursePresentation(
            $course,
            null,
            null,
            null,
            false,
            true,
            CommerceCourseAccessPresentation::unknown(18)
        );
        $page = new MyCoursesPage(new MyCoursesCollection([$item]));

        $info = $this->invoke_access_context($page, $item);

        $this->assertTrue($info['trial']);
        $this->assertFalse($info['haslabel']);
        $this->assertSame('', $info['label']);
        $this->assertSame('neutral', $info['state']);
    }

    /** @return array{trial:bool,haslabel:bool,label:string,state:string} */
    private function invoke_access_context(MyCoursesPage $page, MyCoursePresentation $item): array {
        $reflection = new \ReflectionMethod($page, 'access_context');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($page, $item);

        $this->assertIsArray($result);
        return $result;
    }
}
