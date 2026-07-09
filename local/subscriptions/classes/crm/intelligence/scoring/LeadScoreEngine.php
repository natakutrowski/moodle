<?php

namespace local_subscriptions\crm\intelligence\scoring;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\core\CrmIntelligenceSnapshot;

final class LeadScoreEngine {

    public function score(CrmIntelligenceSnapshot $snapshot): LeadScore {
        $commercial = 0;
        $engagement = 0;
        $risk = 0;
        $reasons = [];

        if ($snapshot->activeSubscriptions > 0) {
            $commercial += 35;
            $engagement += 20;
            $reasons[] = LeadScoreReasons::ACTIVE_CUSTOMER;
        }

        if ($snapshot->trialSubscriptions > 0) {
            $commercial += 25;
            $engagement += 15;
            $reasons[] = LeadScoreReasons::TRIAL_USER;
        }

        if ($snapshot->paidDigitalPurchases > 0) {
            $commercial += 20;
            $reasons[] = LeadScoreReasons::PAID_DIGITAL_PURCHASE;
        }

        if ($snapshot->total_spent_normalized() >= 250) {
            $commercial += 25;
            $reasons[] = LeadScoreReasons::HIGH_VALUE;
        }

        $days = $snapshot->days_since_last_activity();

        if ($days !== null && $days <= 7) {
            $engagement += 25;
            $reasons[] = LeadScoreReasons::RECENT_ACTIVITY;
        }

        if ($days !== null && $days >= 30) {
            $risk += 30;
            $reasons[] = LeadScoreReasons::INACTIVE;
        }

        if ($snapshot->expiredSubscriptions > 0 && $snapshot->activeSubscriptions === 0) {
            $risk += 25;
            $reasons[] = LeadScoreReasons::EXPIRED_SUBSCRIPTION;
        }

        if ($snapshot->suspended) {
            $risk += 60;
            $reasons[] = LeadScoreReasons::SUSPENDED;
        }

        return new LeadScore(
            min(100, $commercial),
            min(100, $engagement),
            min(100, $risk),
            array_values(array_unique($reasons))
        );
    }
}