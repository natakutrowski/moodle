<?php

declare(strict_types=1);

namespace local_campus;

use local_campus\mycourses\MyCoursePresentation;
use local_campus\mycourses\MyCoursesCollection;
use local_campus\output\mycourses\MyCoursesPage;
use local_subscriptions\commerce\course\library\CommerceCourseAccessPresentation;

final class my_courses_page_polish_test extends \advanced_testcase {
    public function test_empty_state_uses_storefront_and_trial_catalogue_without_subscription_cta(): void {
        $page = new MyCoursesPage(new MyCoursesCollection([]));
        $context = $this->invoke_private($page, 'empty_context');

        $this->assertStringContainsString('/boutique', $context['storefronturl']);
        $this->assertStringContainsString('segment=trial', $context['trialurl']);
        $this->assertStringNotContainsString('subscribe.php', json_encode($context));
        $this->assertStringNotContainsString('checkout.php', json_encode($context));
    }

    public function test_hero_summarises_current_moodle_enrolments(): void {
        $courses = new MyCoursesCollection([
            new MyCoursePresentation(
                (object)['id' => 17, 'category' => 3, 'fullname' => 'A1'],
                40.0,
                4,
                10,
                false,
                false,
                CommerceCourseAccessPresentation::unknown(17)
            ),
            new MyCoursePresentation(
                (object)['id' => 18, 'category' => 3, 'fullname' => 'A2'],
                100.0,
                10,
                10,
                true,
                false,
                CommerceCourseAccessPresentation::unknown(18)
            ),
        ]);

        $context = $this->invoke_private(new MyCoursesPage($courses), 'hero_context');
        $values = array_column($context['stats'], 'value');

        $this->assertTrue($context['hasstats']);
        $this->assertSame([2, 1, 1], $values);
    }

    /** @return array<string, mixed> */
    private function invoke_private(MyCoursesPage $page, string $method): array {
        $reflection = new \ReflectionMethod($page, $method);
        $reflection->setAccessible(true);
        $result = $reflection->invoke($page);

        $this->assertIsArray($result);
        return $result;
    }
}
