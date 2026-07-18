<?php

namespace local_subscriptions\crm\intelligence\priority;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\core\CrmIntelligenceLimits;

/**
 * Builds Dashboard priorities from persistent recommendations.
 *
 * This read-side builder must never invoke:
 * - UserIntelligenceBuilder;
 * - CustomerSuccessRuntime;
 * - RecommendationEngine;
 * - CRM collectors.
 */
final class DailyPriorityBuilder {

    public function __construct(
        private readonly DailyPriorityRepository $repository =
            new DailyPriorityRepository()
    ) {
    }

    /**
     * Returns the highest active persisted recommendations.
     *
     * @param int $limit Maximum number of priorities.
     * @return DailyPriority[]
     */
    public function build(
        int $limit =
            CrmIntelligenceLimits::DASHBOARD_PRIORITIES
    ): array {
        $limit = max(1, min(100, $limit));

        $priorities = [];

        foreach (
            $this->repository
                ->get_active_user_priorities($limit)
            as $record
        ) {
            $priorities[] = new DailyPriority(
                userid: (int)$record->userid,
                key: (string)$record->recommendationkey,
                score: (int)$record->priority,
                action: 'open_user_profile',
                displayname: fullname($record)
            );
        }

        return $priorities;
    }
}