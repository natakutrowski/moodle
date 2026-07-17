<?php

namespace local_subscriptions\crm\intelligence\recommendations;

defined('MOODLE_INTERNAL') || die();

/**
 * Functional categories of recommendations produced by CRM intelligence.
 *
 * These values describe the operational purpose of a recommendation.
 * They must remain stable because they can later be persisted, filtered,
 * reported and referenced by automation or Command Center integrations.
 */
final class RecommendationType {

    /**
     * A user should be contacted or followed up.
     */
    public const FOLLOW_UP = 'follow_up';

    /**
     * A Customer Success or retention risk requires human review.
     */
    public const RISK_REVIEW = 'risk_review';

    /**
     * A learning difficulty or abnormal progression requires attention.
     */
    public const LEARNING_SUPPORT = 'learning_support';

    /**
     * An Inbox or support situation requires attention.
     */
    public const SUPPORT_FOLLOW_UP = 'support_follow_up';

    /**
     * A payment problem requires investigation or recovery.
     */
    public const PAYMENT_RECOVERY = 'payment_recovery';

    /**
     * A commercial opportunity may be relevant.
     */
    public const COMMERCIAL_OPPORTUNITY = 'commercial_opportunity';

    /**
     * A Work Item could be useful.
     *
     * This type only proposes a Work Item. It does not create one
     * automatically.
     */
    public const WORK_ITEM_SUGGESTION = 'work_item_suggestion';

    /**
     * An existing Work Item requires attention.
     */
    public const WORK_ITEM_REVIEW = 'work_item_review';

    /**
     * A general operational anomaly requires investigation.
     */
    public const OPERATIONAL_REVIEW = 'operational_review';

    /**
     * Several CRM domains jointly indicate a situation requiring intervention.
     */
    public const CROSS_DOMAIN_INTERVENTION = 'cross_domain_intervention';    

    /**
     * A positive situation deserves recognition or monitoring.
     */
    public const POSITIVE_PROGRESS = 'positive_progress';

    /**
     * Return all supported recommendation types.
     *
     * @return string[]
     */
    public static function all(): array {
        return [
            self::FOLLOW_UP,
            self::RISK_REVIEW,
            self::LEARNING_SUPPORT,
            self::SUPPORT_FOLLOW_UP,
            self::PAYMENT_RECOVERY,
            self::COMMERCIAL_OPPORTUNITY,
            self::WORK_ITEM_SUGGESTION,
            self::WORK_ITEM_REVIEW,
            self::OPERATIONAL_REVIEW,
            self::CROSS_DOMAIN_INTERVENTION,
            self::POSITIVE_PROGRESS,
        ];
    }

    /**
     * Check whether a recommendation type is supported.
     */
    public static function is_valid(string $type): bool {
        return in_array($type, self::all(), true);
    }
}