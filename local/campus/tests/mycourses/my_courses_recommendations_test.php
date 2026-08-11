<?php

declare(strict_types=1);

namespace local_campus;

defined('MOODLE_INTERNAL') || die();

use local_campus\mycourses\MyCoursesCollection;
use local_campus\output\mycourses\MyCoursesPage;
use local_subscriptions\commerce\course\recommendation\CommerceCourseRecommendationCollection;
use local_subscriptions\commerce\course\recommendation\CommerceCourseRecommendationPresentation;

/** Tests the recommendation section exported by My courses. */
final class my_courses_recommendations_test extends \advanced_testcase {
    public function test_page_exports_premium_recommendation_section(): void {
        $recommendation = new CommerceCourseRecommendationPresentation(
            'BUNDLE.A2',
            'bundle',
            'Pack A2',
            'Cours et ressources pour continuer.',
            null,
            'https://example.test/product',
            '159,00 EUR',
            null,
            null,
            true,
            '79,00 EUR',
            'A2 Grammar',
            'A2 Full'
        );
        $page = new MyCoursesPage(
            new MyCoursesCollection([]),
            new CommerceCourseRecommendationCollection([$recommendation])
        );

        $context = $page->export_for_template($this->get_renderer());
        $this->assertTrue($context['recommendations']['hasitems']);
        $this->assertSame('Pack A2', $context['recommendations']['items'][0]['title']);
        $this->assertArrayNotHasKey('typelabel', $context['recommendations']['items'][0]);
        $this->assertArrayNotHasKey('badges', $context['recommendations']['items'][0]);
        $item = $context['recommendations']['items'][0];
        $this->assertFalse($item['upgrade']);
        $this->assertSame(get_string('mycourses_recommendation_discover', 'local_campus'), $item['ctlabel']);
        $this->assertSame('159,00 EUR', $item['priceformatted']);
        $this->assertFalse($item['hasupgradepath']);
    }

    private function get_renderer(): \renderer_base {
        global $PAGE;
        $PAGE->set_context(\context_system::instance());
        return $PAGE->get_renderer('core');
    }
}
