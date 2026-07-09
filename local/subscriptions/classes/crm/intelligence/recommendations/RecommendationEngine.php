<?php

namespace local_subscriptions\crm\intelligence\recommendations;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\core\CrmIntelligenceSnapshot;
use local_subscriptions\crm\intelligence\scoring\LeadScore;

final class RecommendationEngine {

    public function build(CrmIntelligenceSnapshot $snapshot, LeadScore $score, array $opportunities): array {
        $recommendations = [];

        foreach ($opportunities as $opportunity) {
            if ($opportunity->key === 'trial_to_purchase') {
                $recommendations[] = new Recommendation(
                    'send_trial_conversion_email',
                    'action',
                    90,
                    new RecommendationAction('send_trial_conversion_email', 'email_trial_conversion')
                );
            }

            if ($opportunity->key === 'upgrade_subscription') {
                $recommendations[] = new Recommendation(
                    'propose_upgrade',
                    'action',
                    75,
                    new RecommendationAction('propose_upgrade', 'email_upgrade')
                );
            }

            if ($opportunity->key === 'winback_expired_customer') {
                $recommendations[] = new Recommendation(
                    'send_winback_message',
                    'action',
                    80,
                    new RecommendationAction('send_winback_message', 'email_winback')
                );
            }

            if ($opportunity->key === 'cross_sell_digital_product') {
                $recommendations[] = new Recommendation(
                    'suggest_digital_product',
                    'action',
                    65,
                    new RecommendationAction('suggest_digital_product', 'email_digital_product')
                );
            }
        }

        if ($score->risk >= 40) {
            $recommendations[] = new Recommendation(
                'review_user_manually',
                'warning',
                85,
                new RecommendationAction('review_user_manually', 'open_user_profile')
            );
        }

        if ($snapshot->notesCount === 0 && $snapshot->is_customer()) {
            $recommendations[] = new Recommendation(
                'create_first_crm_note',
                'action',
                45,
                new RecommendationAction('create_first_crm_note', 'create_note')
            );
        }

        usort($recommendations, static fn($a, $b): int => $b->priority <=> $a->priority);

        return $recommendations;
    }
}