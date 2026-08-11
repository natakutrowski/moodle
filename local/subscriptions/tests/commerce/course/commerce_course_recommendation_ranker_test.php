<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\course\recommendation\CommerceCourseRecommendationRanker;

/** Tests the stable business priority used for learner recommendations. */
final class commerce_course_recommendation_ranker_test extends \advanced_testcase {
    public function test_priority_order_is_upgrade_then_gustave_then_featured(): void {
        $ranker = new CommerceCourseRecommendationRanker();

        $upgrade = $ranker->score(true, [], false, 'course_access');
        $gustave = $ranker->score(false, ['gustave_choice'], false, 'bundle');
        $featured = $ranker->score(false, [], true, 'course_access');
        $ordinary = $ranker->score(false, [], false, 'course_access');

        $this->assertGreaterThan($gustave, $upgrade);
        $this->assertGreaterThan($featured, $gustave);
        $this->assertGreaterThan($ordinary, $featured);
    }

    public function test_storefront_badges_other_than_gustave_do_not_change_priority(): void {
        $ranker = new CommerceCourseRecommendationRanker();

        $ordinary = $ranker->score(false, [], false, 'course_access');
        $decorated = $ranker->score(false, ['new', 'popular', 'premium'], false, 'course_access');

        $this->assertSame($ordinary, $decorated);
    }
}
