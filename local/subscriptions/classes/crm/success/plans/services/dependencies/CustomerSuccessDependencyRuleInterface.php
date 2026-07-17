<?php

namespace local_subscriptions\crm\success\plans\services\dependencies;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\plans\dto\CustomerSuccessDependency;
use local_subscriptions\crm\success\plans\dto\CustomerSuccessRecommendationInput;

/**
 * Detects a dependency between two candidate actions.
 */
interface CustomerSuccessDependencyRuleInterface {

    public function detect(
        CustomerSuccessRecommendationInput $candidate,
        CustomerSuccessRecommendationInput $possibledependency
    ): ?CustomerSuccessDependency;
}