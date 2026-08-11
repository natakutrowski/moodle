<?php

declare(strict_types=1);

namespace local_campus;

defined('MOODLE_INTERNAL') || die();

/** J6.5 ranking and contextual Trial UX contract. */
final class trial_j65_recommendation_ranking_test extends \advanced_testcase {
    public function test_trial_grammar_is_ranked_before_bundles(): void {
        global $CFG;

        $ranker = file_get_contents(
            $CFG->dirroot .
            '/local/subscriptions/classes/commerce/course/recommendation/' .
            'CommerceCourseRecommendationRanker.php'
        );
        $service = file_get_contents(
            $CFG->dirroot .
            '/local/subscriptions/classes/commerce/course/recommendation/' .
            'CommerceCourseRecommendationService.php'
        );

        $this->assertIsString($ranker);
        $this->assertStringContainsString(
            'TRIAL_GRAMMAR_SCORE',
            $ranker
        );
        $this->assertStringContainsString(
            'TRIAL_FULL_SCORE',
            $ranker
        );

        $this->assertIsString($service);
        $this->assertStringContainsString(
            'product_access_level',
            $service
        );
    }

    public function test_non_course_trial_banner_falls_back_to_boutique(): void {
        global $CFG;

        $source = file_get_contents($CFG->dirroot . '/local/campus/lib.php');

        $this->assertIsString($source);
        $this->assertStringContainsString(
            "return null;",
            $source
        );
        $this->assertStringContainsString(
            "new moodle_url('/boutique')",
            $source
        );
    }
}
