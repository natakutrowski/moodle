<?php

namespace local_subscriptions\crm\intelligence\explanation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\scoring\LeadScoreReasons;
use local_subscriptions\crm\intelligence\core\UserIntelligence;

final class ExplanationBuilder {

    public function build(UserIntelligence $intelligence): array {
        $items = [];

        foreach ($intelligence->leadScore->reasons as $reason) {
            $items[] = match ($reason) {
                LeadScoreReasons::ACTIVE_CUSTOMER => new Explanation('active_customer', ExplanationCategories::POSITIVE, 35),
                LeadScoreReasons::TRIAL_USER => new Explanation('trial_user', ExplanationCategories::POSITIVE, 25),
                LeadScoreReasons::PAID_DIGITAL_PURCHASE => new Explanation('paid_digital_purchase', ExplanationCategories::POSITIVE, 20),
                LeadScoreReasons::HIGH_VALUE => new Explanation('high_value', ExplanationCategories::POSITIVE, 25),
                LeadScoreReasons::RECENT_ACTIVITY => new Explanation('recent_activity', ExplanationCategories::POSITIVE, 25),
                LeadScoreReasons::INACTIVE => new Explanation('inactive', ExplanationCategories::WARNING, -30),
                LeadScoreReasons::EXPIRED_SUBSCRIPTION => new Explanation('expired_subscription', ExplanationCategories::WARNING, -25),
                LeadScoreReasons::SUSPENDED => new Explanation('suspended', ExplanationCategories::RISK, -60),
                default => new Explanation((string)$reason, ExplanationCategories::WARNING, 0),
            };
        }

        if ($intelligence->snapshot->notesCount === 0 && $intelligence->snapshot->is_customer()) {
            $items[] = new Explanation('no_crm_note', ExplanationCategories::WARNING, -10);
        }

        usort($items, static fn($a, $b): int => abs($b->impact) <=> abs($a->impact));

        return $items;
    }
}