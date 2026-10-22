<?php

declare(strict_types=1);

namespace local_subscriptions\trial;

defined('MOODLE_INTERNAL') || die();

/**
 * Prevents accidental administrative creation of new Trial subscriptions after Date A.
 */
final class TrialManualCreationGuard {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly TrialLegacyTransitionPolicy $policy
    ) {
    }

    public static function create(): self {
        global $DB;
        return new self($DB, TrialLegacyTransitionPolicy::create());
    }

    public function is_plan_blocked(int $planid, ?int $at = null): bool {
        if ($planid <= 0 || $this->policy->registrations_are_open($at)) {
            return false;
        }

        return (bool)$this->db->get_field(
            'subscription_plan',
            'is_trial',
            ['id' => $planid],
            IGNORE_MISSING
        );
    }

    public function assert_plan_allowed(int $planid, ?int $at = null): void {
        if ($this->is_plan_blocked($planid, $at)) {
            throw new \moodle_exception(
                'trial_admin_creation_closed',
                'local_subscriptions'
            );
        }
    }
}
