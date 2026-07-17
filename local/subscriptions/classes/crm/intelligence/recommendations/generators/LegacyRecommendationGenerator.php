<?php

namespace local_subscriptions\crm\intelligence\recommendations\generators;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\recommendations\Recommendation;
use local_subscriptions\crm\intelligence\recommendations\RecommendationAction;
use local_subscriptions\crm\intelligence\recommendations\RecommendationEvidence;
use local_subscriptions\crm\intelligence\recommendations\RecommendationGenerationContext;
use local_subscriptions\crm\intelligence\recommendations\RecommendationGenerationResult;
use local_subscriptions\crm\intelligence\recommendations\RecommendationSource;
use local_subscriptions\crm\intelligence\recommendations\RecommendationType;

/**
 * Compatibility generator containing the original recommendation rules.
 *
 * These rules will progressively move to dedicated domain generators during
 * Phase 7.2. Until then, this generator guarantees identical business output.
 */
final class LegacyRecommendationGenerator implements RecommendationGeneratorInterface {

    public const KEY = 'legacy_crm_intelligence';

    /**
     * Return the stable generator key.
     */
    public function key(): string {
        return self::KEY;
    }

    /**
     * Generate recommendations from existing CRM intelligence rules.
     */
    public function generate(
        RecommendationGenerationContext $context
    ): RecommendationGenerationResult {
        $recommendations = [];
        $target = $context->user_target();
        $generatedat = $context->timestamp();

        foreach ($context->opportunities as $opportunity) {
            if (!property_exists($opportunity, 'key')) {
                continue;
            }

            $opportunitykey = (string)$opportunity->key;

            if ($opportunitykey === 'trial_to_purchase') {
                $recommendations[] = new Recommendation(
                    key: 'send_trial_conversion_email',
                    type: Recommendation::PRESENTATION_ACTION,
                    priority: 90,
                    action: new RecommendationAction(
                        key: 'send_trial_conversion_email',
                        action: RecommendationAction::EMAIL_TRIAL_CONVERSION,
                        primary: true,
                        confirmationrequired: true
                    ),
                    recommendationtype: RecommendationType::COMMERCIAL_OPPORTUNITY,
                    target: $target,
                    evidence: [
                        $this->opportunity_evidence(
                            $opportunitykey,
                            90,
                            $generatedat
                        ),
                    ],
                    sources: [
                        RecommendationSource::CRM_INTELLIGENCE,
                    ],
                    createdat: $generatedat
                );
            }

            if ($opportunitykey === 'upgrade_subscription') {
                $recommendations[] = new Recommendation(
                    key: 'propose_upgrade',
                    type: Recommendation::PRESENTATION_ACTION,
                    priority: 75,
                    action: new RecommendationAction(
                        key: 'propose_upgrade',
                        action: RecommendationAction::EMAIL_UPGRADE,
                        primary: true,
                        confirmationrequired: true
                    ),
                    recommendationtype: RecommendationType::COMMERCIAL_OPPORTUNITY,
                    target: $target,
                    evidence: [
                        $this->opportunity_evidence(
                            $opportunitykey,
                            75,
                            $generatedat
                        ),
                    ],
                    sources: [
                        RecommendationSource::CRM_INTELLIGENCE,
                    ],
                    createdat: $generatedat
                );
            }

            if ($opportunitykey === 'winback_expired_customer') {
                $recommendations[] = new Recommendation(
                    key: 'send_winback_message',
                    type: Recommendation::PRESENTATION_ACTION,
                    priority: 80,
                    action: new RecommendationAction(
                        key: 'send_winback_message',
                        action: RecommendationAction::EMAIL_WINBACK,
                        primary: true,
                        confirmationrequired: true
                    ),
                    recommendationtype: RecommendationType::FOLLOW_UP,
                    target: $target,
                    evidence: [
                        $this->opportunity_evidence(
                            $opportunitykey,
                            80,
                            $generatedat
                        ),
                    ],
                    sources: [
                        RecommendationSource::CRM_INTELLIGENCE,
                        RecommendationSource::SUBSCRIPTIONS,
                    ],
                    createdat: $generatedat
                );
            }

            if ($opportunitykey === 'cross_sell_digital_product') {
                $recommendations[] = new Recommendation(
                    key: 'suggest_digital_product',
                    type: Recommendation::PRESENTATION_ACTION,
                    priority: 65,
                    action: new RecommendationAction(
                        key: 'suggest_digital_product',
                        action: RecommendationAction::EMAIL_DIGITAL_PRODUCT,
                        primary: true,
                        confirmationrequired: true
                    ),
                    recommendationtype: RecommendationType::COMMERCIAL_OPPORTUNITY,
                    target: $target,
                    evidence: [
                        $this->opportunity_evidence(
                            $opportunitykey,
                            65,
                            $generatedat
                        ),
                    ],
                    sources: [
                        RecommendationSource::CRM_INTELLIGENCE,
                        RecommendationSource::DIGITAL_PURCHASES,
                    ],
                    createdat: $generatedat
                );
            }
        }

        if (
            $context->snapshot->notesCount === 0 &&
            $context->snapshot->is_customer()
        ) {
            $actionparams = [];

            if ($context->resolved_userid() !== null) {
                $actionparams['userid'] = $context->resolved_userid();
            }

            $recommendations[] = new Recommendation(
                key: 'create_first_crm_note',
                type: Recommendation::PRESENTATION_ACTION,
                priority: 45,
                action: new RecommendationAction(
                    key: 'create_first_crm_note',
                    action: RecommendationAction::CREATE_NOTE,
                    params: $actionparams,
                    primary: true,
                    confirmationrequired: true
                ),
                recommendationtype: RecommendationType::FOLLOW_UP,
                target: $target,
                evidence: [
                    new RecommendationEvidence(
                        RecommendationSource::CRM_INTELLIGENCE,
                        'crm.customer_without_notes',
                        true,
                        45,
                        $generatedat,
                        [
                            'notescount' => 0,
                        ]
                    ),
                ],
                sources: [
                    RecommendationSource::CRM_INTELLIGENCE,
                ],
                createdat: $generatedat
            );
        }

        return RecommendationGenerationResult::success(
            $this->key(),
            $recommendations,
            [
                'recommendationcount' => count($recommendations),
                'opportunitycount' => count($context->opportunities),
            ]
        );
    }

    /**
     * Build explainable evidence from an existing commercial opportunity.
     */
    private function opportunity_evidence(
        string $opportunitykey,
        int $weight,
        int $detectedat
    ): RecommendationEvidence {
        return new RecommendationEvidence(
            RecommendationSource::CRM_INTELLIGENCE,
            'opportunity.' . $opportunitykey,
            true,
            $weight,
            $detectedat,
            [
                'opportunitykey' => $opportunitykey,
            ]
        );
    }
}