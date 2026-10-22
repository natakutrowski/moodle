<?php

declare(strict_types=1);

namespace local_subscriptions\trial;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\constants\Status;
use local_subscriptions\trial_manager;

/**
 * Resolves Trial -> Native Commerce eligibility during the 7.98D sunset.
 *
 * Before Date A, the historical per-user discount window remains authoritative.
 * From Date A (inclusive) until Date B (exclusive), every non-converted member
 * of the pre-closure Trial cohort may still convert, even when the individual
 * free-access period has already expired. The free-access entitlement itself is
 * deliberately not extended here.
 */
final class TrialLegacyConversionEligibilityService {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly ?TrialLegacyTransitionPolicy $policy = null
    ) {
    }

    public static function create(): self {
        global $DB;
        return new self($DB);
    }

    public function is_eligible(int $userid, ?int $at = null): bool {
        return $this->deadline($userid, $at) > ($at ?? time());
    }

    /**
     * Conversion deadline for this user, or 0 when no Trial conversion is available.
     */
    public function deadline(int $userid, ?int $at = null): int {
        if ($userid <= 0) {
            return 0;
        }

        $at ??= time();
        $policy = $this->policy ?? TrialLegacyTransitionPolicy::create();

        if (!$policy->conversion_window_is_open($at)) {
            return 0;
        }

        $closeat = $policy->registration_close_at();
        $legacyexpireat = $policy->legacy_expire_at();

        // The transition grace is only activated by a complete, ordered A/B
        // calendar. A partially configured calendar keeps the historical
        // behaviour rather than accidentally creating an unlimited discount.
        if (
            $closeat !== null &&
            $legacyexpireat !== null &&
            $legacyexpireat > $closeat &&
            $at >= $closeat &&
            $at < $legacyexpireat
        ) {
            return $this->is_preclosure_nonconverted_trial_user($userid, $closeat)
                ? $legacyexpireat
                : 0;
        }

        return trial_manager::discount_window_deadline($userid);
    }

    /**
     * True only for the legacy cohort that existed before Date A and has not
     * been fully replaced by paid access.
     */
    public function is_preclosure_nonconverted_trial_user(int $userid, int $closeat): bool {
        if ($userid <= 0 || $closeat <= 0) {
            return false;
        }

        $statuses = [Status::ACTIVE, Status::EXPIRED, Status::COMPLETED];
        [$statussql, $statusparams] = $this->db->get_in_or_equal(
            $statuses,
            SQL_PARAMS_NAMED,
            'trialstatus'
        );
        $params = $statusparams + [
            'userid' => $userid,
            'closecreation' => $closeat,
            'closestart' => $closeat,
        ];

        $sql = "SELECT us.id, us.start_date, us.creation_date, us.status
                  FROM {user_subscription} us
                  JOIN {subscription_plan} sp ON sp.id = us.planid
                 WHERE us.userid = :userid
                   AND sp.is_trial = 1
                   AND us.status {$statussql}
                   AND (
                        (us.creation_date > 0 AND us.creation_date < :closecreation)
                        OR
                        (COALESCE(us.creation_date, 0) <= 0 AND us.start_date > 0 AND us.start_date < :closestart)
                   )";

        if ($this->db->record_exists_sql($sql, $params)) {
            return true;
        }

        // Some pre-Native Trial accounts only have the historical Campus row.
        // D1 audits these explicitly; D3 must not strand them during the grace
        // period. Status 3 is intentionally excluded because Legacy uses it for
        // banned/converted rows and it is not a safe conversion entitlement.
        $campustable = new \xmldb_table('local_campus_trial');
        if (!$this->db->get_manager()->table_exists($campustable)) {
            return false;
        }

        return $this->db->record_exists_select(
            'local_campus_trial',
            'userid = :campususerid AND status IN (:campusactive, :campusexpired) AND timecreated < :campusclose',
            [
                'campususerid' => $userid,
                'campusactive' => 1,
                'campusexpired' => 2,
                'campusclose' => $closeat,
            ]
        );
    }
}
