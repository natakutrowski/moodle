<?php

namespace local_subscriptions\crm\success\plans\services\dependencies;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\plans\domain\CustomerSuccessActionCategory;
use local_subscriptions\crm\success\plans\dto\CustomerSuccessDependency;
use local_subscriptions\crm\success\plans\dto\CustomerSuccessRecommendationInput;

/**
 * Learning support actions require working course access first.
 */
final class AccessBeforeLearningRule
    implements CustomerSuccessDependencyRuleInterface {

    public function detect(
        CustomerSuccessRecommendationInput $candidate,
        CustomerSuccessRecommendationInput $possibledependency
    ): ?CustomerSuccessDependency {
        if (
            $candidate->category !==
                CustomerSuccessActionCategory::LEARNING ||
            $possibledependency->category !==
                CustomerSuccessActionCategory::ACCESS
        ) {
            return null;
        }

        return new CustomerSuccessDependency(
            recommendationid:
                $candidate->recommendationid,
            dependsonrecommendationid:
                $possibledependency->recommendationid,
            reasonkey:
                'access_before_learning'
        );
    }
}