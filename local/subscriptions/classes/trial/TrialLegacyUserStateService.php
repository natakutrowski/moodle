<?php

declare(strict_types=1);

namespace local_subscriptions\trial;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\trial_manager;

/**
 * Read-only per-user Trial lifecycle projection for CRM/admin surfaces.
 *
 * This service deliberately never grants, restores or revokes access. It only
 * explains the historical/runtime state assembled by 7.98D.
 */
final class TrialLegacyUserStateService {
    public const STATE_NONE = 'none';
    public const STATE_ACTIVE = 'active';
    public const STATE_EXPIRED = 'expired';
    public const STATE_CONVERTED = 'converted';
    public const STATE_LEGACY_HISTORICAL = 'legacy_historical';

    public function __construct(
        private readonly \moodle_database $db,
        private readonly TrialLegacyTransitionPolicy $policy
    ) {
    }

    public static function create(): self {
        global $DB;
        return new self($DB, TrialLegacyTransitionPolicy::create());
    }

    /**
     * @return array<string, mixed>
     */
    public function describe(int $userid, ?int $at = null): array {
        $at ??= time();
        $transition = $this->policy->snapshot($at);

        $result = [
            'state' => self::STATE_NONE,
            'has_trial_history' => false,
            'active' => false,
            'converted' => false,
            'historical_only' => false,
            'subscription_id' => 0,
            'subscription_status' => '',
            'start_at' => 0,
            'end_at' => 0,
            'campus_status' => 0,
            'campus_created_at' => 0,
            'campus_expires_at' => 0,
            'conversion_purchase_id' => 0,
            'conversion_at' => 0,
            'transition_phase' => (string)$transition['phase'],
            'registration_close_at' => (int)($transition['registration_close_at'] ?? 0),
            'legacy_expire_at' => (int)($transition['legacy_expire_at'] ?? 0),
        ];

        if ($userid <= 0) {
            return $result;
        }

        $trialplanid = trial_manager::get_trial_planid();
        $subscriptions = [];
        if ($trialplanid > 0 && $this->db->get_manager()->table_exists(new \xmldb_table('user_subscription'))) {
            $subscriptions = array_values($this->db->get_records(
                'user_subscription',
                ['userid' => $userid, 'planid' => $trialplanid],
                'start_date DESC, id DESC',
                'id,status,start_date,end_date,creation_date,last_update'
            ));
        }

        $campus = null;
        if ($this->db->get_manager()->table_exists(new \xmldb_table('local_campus_trial'))) {
            $campus = $this->db->get_record_sql(
                'SELECT *
                   FROM {local_campus_trial}
                  WHERE userid = :userid
               ORDER BY timecreated DESC, id DESC',
                ['userid' => $userid],
                IGNORE_MULTIPLE
            ) ?: null;
        }

        $conversion = $this->conversion_purchase($userid);

        $result['has_trial_history'] = $subscriptions !== [] || $campus !== null || $conversion !== null;

        if ($campus !== null) {
            $result['campus_status'] = (int)($campus->status ?? 0);
            $result['campus_created_at'] = (int)($campus->timecreated ?? 0);
            $result['campus_expires_at'] = (int)($campus->expiresat ?? 0);
        }

        if ($conversion !== null) {
            $result['converted'] = true;
            $result['conversion_purchase_id'] = (int)$conversion->id;
            $result['conversion_at'] = (int)$conversion->timecreated;
        }

        $latest = $subscriptions[0] ?? null;
        if ($latest !== null) {
            $result['subscription_id'] = (int)$latest->id;
            $result['subscription_status'] = strtolower(trim((string)$latest->status));
            $result['start_at'] = (int)$latest->start_date;
            $result['end_at'] = (int)$latest->end_date;
        }

        foreach ($subscriptions as $subscription) {
            $status = strtolower(trim((string)$subscription->status));
            if ($status === 'replaced') {
                $result['converted'] = true;
            }
            if (
                $status === 'active'
                && (int)$subscription->end_date > $at
                && !$this->policy->legacy_window_is_closed($at)
            ) {
                $result['active'] = true;
                $result['subscription_id'] = (int)$subscription->id;
                $result['subscription_status'] = $status;
                $result['start_at'] = (int)$subscription->start_date;
                $result['end_at'] = (int)$subscription->end_date;
                break;
            }
        }

        if (!empty($result['converted'])) {
            $result['state'] = self::STATE_CONVERTED;
            return $result;
        }

        if (!empty($result['active'])) {
            $result['state'] = self::STATE_ACTIVE;
            return $result;
        }

        if ($subscriptions !== []) {
            $result['state'] = self::STATE_EXPIRED;
            return $result;
        }

        if ($campus !== null) {
            $result['state'] = self::STATE_LEGACY_HISTORICAL;
            $result['historical_only'] = true;
            return $result;
        }

        return $result;
    }

    private function conversion_purchase(int $userid): ?\stdClass {
        $itemtable = new \xmldb_table('local_subscriptions_commerce_purchase_item');
        $purchasetable = new \xmldb_table('local_subscriptions_commerce_purchase');
        if (
            !$this->db->get_manager()->table_exists($itemtable)
            || !$this->db->get_manager()->table_exists($purchasetable)
        ) {
            return null;
        }

        $metadata = $this->db->sql_like('i.metadatajson', ':metaneedle', false);
        $fulfillment = $this->db->sql_like('i.fulfillmentjson', ':fulfillmentneedle', false);

        return $this->db->get_record_sql(
            "SELECT p.id, p.timecreated
               FROM {local_subscriptions_commerce_purchase} p
               JOIN {local_subscriptions_commerce_purchase_item} i
                 ON i.purchaseid = p.id
              WHERE p.userid = :userid
                AND ({$metadata} OR {$fulfillment})
           ORDER BY p.timecreated DESC, p.id DESC",
            [
                'userid' => $userid,
                'metaneedle' => '%trialconversion%',
                'fulfillmentneedle' => '%trialconversion%',
            ],
            IGNORE_MULTIPLE
        ) ?: null;
    }
}
