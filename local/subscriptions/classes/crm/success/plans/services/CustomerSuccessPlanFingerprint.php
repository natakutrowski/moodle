<?php

namespace local_subscriptions\crm\success\plans\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\plans\dto\CustomerSuccessRecommendationInput;

/**
 * Creates a stable plan fingerprint for deduplication.
 */
final class CustomerSuccessPlanFingerprint {

    /**
     * @param CustomerSuccessRecommendationInput[] $recommendations
     */
    public function build(
        int $userid,
        string $objectivekey,
        array $recommendations
    ): string {
        $recommendationids = array_map(
            static fn(
                CustomerSuccessRecommendationInput $recommendation
            ): int => $recommendation->recommendationid,
            $recommendations
        );

        sort(
            $recommendationids,
            SORT_NUMERIC
        );

        return hash(
            'sha256',
            implode(
                '|',
                [
                    $userid,
                    $objectivekey,
                    implode(',', $recommendationids),
                ]
            )
        );
    }
}