<?php

namespace local_subscriptions\crm\intelligence\opportunities;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\core\CrmIntelligenceSnapshot;
use local_subscriptions\crm\intelligence\scoring\LeadScore;

final class OpportunityEngine {

    public function detect(CrmIntelligenceSnapshot $snapshot, LeadScore $score): array {
        $opportunities = [];

        if ($snapshot->trialSubscriptions > 0 && $snapshot->paidSubscriptions === 0) {
            $opportunities[] = new Opportunity('trial_to_purchase', 90);
        }

        if ($snapshot->activeSubscriptions > 0 && $snapshot->paidDigitalPurchases === 0) {
            $opportunities[] = new Opportunity('cross_sell_digital_product', 65);
        }

        if ($snapshot->activeSubscriptions > 0 && $score->engagement >= 40) {
            $opportunities[] = new Opportunity('upgrade_subscription', 75);
        }

        if ($snapshot->expiredSubscriptions > 0 && $snapshot->activeSubscriptions === 0) {
            $opportunities[] = new Opportunity('winback_expired_customer', 80);
        }

        usort($opportunities, static fn($a, $b): int => $b->priority <=> $a->priority);

        return $opportunities;
    }
}