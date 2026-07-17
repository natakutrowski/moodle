<?php

namespace local_subscriptions\crm\success\plans\services\dependencies;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\plans\domain\CustomerSuccessActionCategory;
use local_subscriptions\crm\success\plans\dto\CustomerSuccessDependency;
use local_subscriptions\crm\success\plans\dto\CustomerSuccessRecommendationInput;

/**
 * A generic communication follow-up should not precede an unresolved support issue.
 */
final class SupportBeforeFollowUpRule
    implements CustomerSuccessDependencyRuleInterface {

    public function detect(
        CustomerSuccessRecommendationInput $candidate,
        CustomerSuccessRecommendationInput $possibledependency
    ): ?CustomerSuccessDependency {
        if (
            $candidate->category !==
                CustomerSuccessActionCategory::COMMUNICATION ||
            $possibledependency->category !==
                CustomerSuccessActionCategory::SUPPORT
        ) {
            return null;
        }

        return new CustomerSuccessDependency(
            recommendationid:
                $candidate->recommendationid,
            dependsonrecommendationid:
                $possibledependency->recommendationid,
            reasonkey:
                'support_before_follow_up'
        );
    }
}