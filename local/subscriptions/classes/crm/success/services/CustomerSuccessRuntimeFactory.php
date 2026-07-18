<?php

namespace local_subscriptions\crm\success\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\collectors\CommercialLoyaltyCollector;
use local_subscriptions\crm\success\collectors\LevelUpXpCollector;
use local_subscriptions\crm\success\collectors\MoodleActivityCollector;
use local_subscriptions\crm\success\collectors\MoodleCourseProgressCollector;
use local_subscriptions\crm\success\collectors\PoodllMiniLessonCollector;
use local_subscriptions\crm\success\collectors\PoodllReadAloudCollector;
use local_subscriptions\crm\success\collectors\PoodllSoloCollector;
use local_subscriptions\crm\success\collectors\PoodllWordCardsCollector;
use local_subscriptions\crm\success\collectors\SuccessCollectorRegistry;
use local_subscriptions\crm\success\collectors\SupportInboxCollector;
use local_subscriptions\crm\success\collectors\WorkItemSuccessCollector;
use local_subscriptions\crm\success\rules\CommercialLoyaltySignalRule;
use local_subscriptions\crm\success\rules\LevelUpXpSignalRule;
use local_subscriptions\crm\success\rules\MoodleActivitySignalRule;
use local_subscriptions\crm\success\rules\MoodleLearningSignalRule;
use local_subscriptions\crm\success\rules\PoodllLearningSignalRule;
use local_subscriptions\crm\success\rules\SupportInboxSignalRule;
use local_subscriptions\crm\success\rules\WorkItemSuccessSignalRule;
use local_subscriptions\crm\success\scoring\SuccessScoreEngine;
use local_subscriptions\crm\success\scoring\SuccessScoreProfile;
use local_subscriptions\crm\success\signals\SuccessSignalEngine;
use local_subscriptions\crm\success\signals\SuccessSignalRuleRegistry;
use local_subscriptions\crm\success\repositories\PoodllLearningRepository;
use local_subscriptions\crm\success\repositories\EnrolledCourseProvider;
use local_subscriptions\crm\success\repositories\LevelUpXpRepository;
use local_subscriptions\crm\success\repositories\MoodleCourseProgressRepository;

/**
 * Builds the default Customer Success runtime.
 */
final class CustomerSuccessRuntimeFactory {

    public function create(
        ?string $useremail = null,
        ?int $userlastaccess = null
    ): CustomerSuccessRuntime {
        $courseprovider =
            new EnrolledCourseProvider();

        $courseprogressrepository =
            new MoodleCourseProgressRepository(
                courseprovider:
                    $courseprovider
            );

        $leveluprepository =
            new LevelUpXpRepository(
                courseprovider:
                    $courseprovider
            );

        $poodllrepository =
            new PoodllLearningRepository();

        $collectors =
            new SuccessCollectorRegistry([
                new MoodleActivityCollector(
                    userlastaccess:
                        $userlastaccess
                ),
                new MoodleCourseProgressCollector(
                    repository:
                        $courseprogressrepository
                ),

                new LevelUpXpCollector(
                    repository:
                        $leveluprepository
                ),
                new PoodllMiniLessonCollector(
                    repository:
                        $poodllrepository
                ),

                new PoodllReadAloudCollector(
                    repository:
                        $poodllrepository
                ),

                new PoodllSoloCollector(
                    repository:
                        $poodllrepository
                ),

                new PoodllWordCardsCollector(
                    repository:
                        $poodllrepository
                ),
                new CommercialLoyaltyCollector(
                    useremail: $useremail
                ),
                new SupportInboxCollector(),
                new WorkItemSuccessCollector(),
            ]);

        $rules =
            new SuccessSignalRuleRegistry([
                new MoodleActivitySignalRule(),
                new MoodleLearningSignalRule(),
                new LevelUpXpSignalRule(),
                new PoodllLearningSignalRule(),
                new CommercialLoyaltySignalRule(),
                new SupportInboxSignalRule(),
                new WorkItemSuccessSignalRule(),
            ]);

        $signalengine =
            new SuccessSignalEngine($rules);

        $scoreengine =
            new SuccessScoreEngine(
                SuccessScoreProfile::campusfr_default()
            );

        return new CustomerSuccessRuntime(
            $collectors,
            $signalengine,
            $scoreengine
        );
    }
}