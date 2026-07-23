<?php

namespace local_subscriptions\commerce\purchase\subscription;

defined('MOODLE_INTERNAL') || die();

/**
 * Read-only source of subscription plan business information.
 */
interface SubscriptionPlanRepository {

    public function find(
        int $planid
    ): ?SubscriptionPlanDescriptor;
}