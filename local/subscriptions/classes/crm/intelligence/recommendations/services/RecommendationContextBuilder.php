<?php

namespace local_subscriptions\crm\intelligence\recommendations\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\core\CrmIntelligenceSnapshot;
use local_subscriptions\crm\intelligence\recommendations\RecommendationGenerationContext;
use local_subscriptions\crm\intelligence\scoring\LeadScore;
use local_subscriptions\crm\success\dto\CustomerSuccessResult;
use local_subscriptions\crm\success\services\CustomerSuccessRuntimeFactory;

/**
 * Builds the transversal context consumed by recommendation generators.
 *
 * This service coordinates already existing CRM and Customer Success
 * components. It performs no SQL and contains no recommendation rules.
 */
final class RecommendationContextBuilder {

    public const DATA_CUSTOMER_SUCCESS = 'customer_success.result';

    public function __construct(
        private readonly CustomerSuccessRuntimeFactory $successfactory =
            new CustomerSuccessRuntimeFactory()
    ) {
    }

    /**
     * Build a recommendation context for one Moodle user.
     */
    public function build(
        int $userid,
        CrmIntelligenceSnapshot $snapshot,
        LeadScore $leadscore,
        array $opportunities = [],
        ?int $generatedat = null
    ): RecommendationGenerationContext {
        if ($userid <= 0) {
            throw new \InvalidArgumentException(
                'Recommendation context userid must be greater than zero.'
            );
        }

        $generatedat = $generatedat ?? time();

        if ($generatedat <= 0) {
            throw new \InvalidArgumentException(
                'Recommendation context timestamp must be greater than zero.'
            );
        }

        $successresult = $this->successfactory
            ->create()
            ->evaluate(
                $userid,
                $generatedat
            );

        return new RecommendationGenerationContext(
            snapshot: $snapshot,
            leadscore: $leadscore,
            opportunities: $opportunities,
            userid: $userid,
            data: [
                self::DATA_CUSTOMER_SUCCESS => $successresult,
            ],
            generatedat: $generatedat
        );
    }

    /**
     * Read Customer Success data from an existing context.
     */
    public static function customer_success(
        RecommendationGenerationContext $context
    ): ?CustomerSuccessResult {
        $value = $context->get(
            self::DATA_CUSTOMER_SUCCESS
        );

        return $value instanceof CustomerSuccessResult
            ? $value
            : null;
    }
}