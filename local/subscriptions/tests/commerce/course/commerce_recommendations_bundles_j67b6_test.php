<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J6.7B6 recommendations and bundle classification contract. */
final class commerce_recommendations_bundles_j67b6_test
        extends \advanced_testcase {

    public function test_service_gives_real_upgrade_precedence_over_trial(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/course/'
            . 'recommendation/CommerceCourseRecommendationService.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            '$istrialoffer = $istrialeligible && !$explicitupgrade;',
            $source
        );
        $this->assertStringContainsString(
            '$isupgrade = !$isbundle && $decision[\'upgrade\'];',
            $source
        );
        $this->assertStringContainsString(
            '$isbundle ? [] : array_keys($trials)',
            $source
        );
    }

    public function test_ranker_cannot_promote_bundle_as_upgrade(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/course/'
            . 'recommendation/CommerceCourseRecommendationRanker.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            "\$upgrade && \$type !== 'bundle'",
            $source
        );
    }
}
