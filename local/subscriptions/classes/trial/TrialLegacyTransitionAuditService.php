<?php

declare(strict_types=1);

namespace local_subscriptions\trial;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\trial_manager;

/**
 * Read-only inventory for the 7.98D Legacy Trial transition.
 */
final class TrialLegacyTransitionAuditService {
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
    public function inspect(?int $at = null): array {
        $at ??= time();
        $trialplanid = trial_manager::get_trial_planid();

        $campusdays = $this->integer_config('local_campus', 'trialdays', 0);
        $durationpolicy = TrialDurationPolicy::create();
        $subscriptiondays = $durationpolicy->configured_days();

        return [
            'generated_at' => $at,
            'transition' => $this->policy->snapshot($at),
            'configuration_issues' => $this->policy->configuration_issues(),
            'duration_configuration' => [
                'authority_component' => 'local_subscriptions',
                'authority_key' => TrialDurationPolicy::CONFIG_KEY,
                'authoritative_days' => $subscriptiondays,
                'legacy_local_campus_trialdays' => $campusdays,
                'legacy_ignored' => true,
                'legacy_differs' => $campusdays !== $subscriptiondays,
            ],
            'trial_plan_id' => $trialplanid,
            'campus_trial' => $this->campus_trial_counts($at),
            'subscriptions' => $this->subscription_counts($trialplanid, $at),
            'roles' => $this->role_counts(),
            'native_trialconversion' => $this->conversion_counts(),
            'consistency' => $this->consistency_counts($trialplanid, $at),
        ];
    }

    /** @return array<string, int|bool> */
    private function campus_trial_counts(int $at): array {
        $table = new \xmldb_table('local_campus_trial');
        if (!$this->db->get_manager()->table_exists($table)) {
            return [
                'available' => false,
                'total' => 0,
                'linked_to_user' => 0,
                'status_active' => 0,
                'status_expired' => 0,
                'status_banned' => 0,
                'effective_active' => 0,
                'past_expiry' => 0,
            ];
        }

        return [
            'available' => true,
            'total' => $this->db->count_records('local_campus_trial'),
            'linked_to_user' => $this->db->count_records_select(
                'local_campus_trial',
                'userid IS NOT NULL AND userid > 0'
            ),
            'status_active' => $this->db->count_records('local_campus_trial', ['status' => 1]),
            'status_expired' => $this->db->count_records('local_campus_trial', ['status' => 2]),
            'status_banned' => $this->db->count_records('local_campus_trial', ['status' => 3]),
            'effective_active' => $this->db->count_records_select(
                'local_campus_trial',
                'status = :status AND expiresat > :at',
                ['status' => 1, 'at' => $at]
            ),
            'past_expiry' => $this->db->count_records_select(
                'local_campus_trial',
                'expiresat <= :at',
                ['at' => $at]
            ),
        ];
    }

    /** @return array<string, mixed> */
    private function subscription_counts(int $trialplanid, int $at): array {
        $table = new \xmldb_table('user_subscription');
        $result = [
            'available' => $this->db->get_manager()->table_exists($table),
            'total' => 0,
            'distinct_users' => 0,
            'active_valid' => 0,
            'active_past_end' => 0,
            'by_status' => [],
        ];
        if (!$result['available'] || $trialplanid <= 0) {
            return $result;
        }

        $records = $this->db->get_records(
            'user_subscription',
            ['planid' => $trialplanid],
            '',
            'id,userid,status,end_date'
        );
        $users = [];
        foreach ($records as $record) {
            $status = strtolower(trim((string)$record->status));
            $result['by_status'][$status] = ($result['by_status'][$status] ?? 0) + 1;
            if ((int)$record->userid > 0) {
                $users[(int)$record->userid] = true;
            }
            if ($status === 'active') {
                if ((int)$record->end_date > $at) {
                    $result['active_valid']++;
                } else {
                    $result['active_past_end']++;
                }
            }
        }

        $result['total'] = count($records);
        $result['distinct_users'] = count($users);
        ksort($result['by_status']);
        return $result;
    }

    /** @return array<string, array<string, int|bool>> */
    private function role_counts(): array {
        return [
            'trialstudent' => $this->role_count('trialstudent'),
            'triallimited' => $this->role_count('triallimited'),
        ];
    }

    /** @return array<string, int|bool> */
    private function role_count(string $shortname): array {
        $roleid = (int)$this->db->get_field(
            'role',
            'id',
            ['shortname' => $shortname],
            IGNORE_MISSING
        );
        if ($roleid <= 0) {
            return [
                'role_exists' => false,
                'role_id' => 0,
                'assignments' => 0,
                'distinct_users' => 0,
            ];
        }

        $distinctusers = (int)$this->db->get_field_sql(
            'SELECT COUNT(DISTINCT userid)
               FROM {role_assignments}
              WHERE roleid = :roleid',
            ['roleid' => $roleid]
        );

        return [
            'role_exists' => true,
            'role_id' => $roleid,
            'assignments' => $this->db->count_records('role_assignments', ['roleid' => $roleid]),
            'distinct_users' => $distinctusers,
        ];
    }

    /** @return array<string, int|bool> */
    private function conversion_counts(): array {
        $itemtable = new \xmldb_table('local_subscriptions_commerce_purchase_item');
        $purchasetable = new \xmldb_table('local_subscriptions_commerce_purchase');
        if (
            !$this->db->get_manager()->table_exists($itemtable) ||
            !$this->db->get_manager()->table_exists($purchasetable)
        ) {
            return [
                'available' => false,
                'items' => 0,
                'purchases' => 0,
                'users' => 0,
            ];
        }

        $metadata = $this->db->sql_like('metadatajson', ':metaneedle', false);
        $fulfillment = $this->db->sql_like('fulfillmentjson', ':fulfillmentneedle', false);
        $items = $this->db->get_records_select(
            'local_subscriptions_commerce_purchase_item',
            "({$metadata} OR {$fulfillment})",
            [
                'metaneedle' => '%trialconversion%',
                'fulfillmentneedle' => '%trialconversion%',
            ],
            '',
            'id,purchaseid'
        );

        $purchaseids = [];
        foreach ($items as $item) {
            if ((int)$item->purchaseid > 0) {
                $purchaseids[(int)$item->purchaseid] = true;
            }
        }

        $users = [];
        if ($purchaseids !== []) {
            $purchases = $this->db->get_records_list(
                'local_subscriptions_commerce_purchase',
                'id',
                array_keys($purchaseids),
                '',
                'id,userid'
            );
            foreach ($purchases as $purchase) {
                if ((int)$purchase->userid > 0) {
                    $users[(int)$purchase->userid] = true;
                }
            }
        }

        return [
            'available' => true,
            'items' => count($items),
            'purchases' => count($purchaseids),
            'users' => count($users),
        ];
    }

    /** @return array<string, int|bool> */
    private function consistency_counts(int $trialplanid, int $at): array {
        $campustable = new \xmldb_table('local_campus_trial');
        $subscriptiontable = new \xmldb_table('user_subscription');
        $available = $trialplanid > 0 &&
            $this->db->get_manager()->table_exists($campustable) &&
            $this->db->get_manager()->table_exists($subscriptiontable);
        if (!$available) {
            return [
                'available' => false,
                'campus_users_without_trial_subscription' => 0,
                'campus_active_users_without_trial_subscription' => 0,
                'campus_historical_users_without_trial_subscription' => 0,
                'trial_subscription_users_without_campus_row' => 0,
            ];
        }

        $campusmissing = (int)$this->db->get_field_sql(
            'SELECT COUNT(DISTINCT ct.userid)
               FROM {local_campus_trial} ct
          LEFT JOIN {user_subscription} us
                 ON us.userid = ct.userid
                AND us.planid = :campusplanid
              WHERE ct.userid IS NOT NULL
                AND ct.userid > 0
                AND us.id IS NULL',
            ['campusplanid' => $trialplanid]
        );

        $campusactivemissing = (int)$this->db->get_field_sql(
            'SELECT COUNT(DISTINCT ct.userid)
               FROM {local_campus_trial} ct
          LEFT JOIN {user_subscription} us
                 ON us.userid = ct.userid
                AND us.planid = :activeplanid
              WHERE ct.userid IS NOT NULL
                AND ct.userid > 0
                AND us.id IS NULL
                AND ct.status = :activestatus
                AND ct.expiresat > :at',
            [
                'activeplanid' => $trialplanid,
                'activestatus' => 1,
                'at' => $at,
            ]
        );

        $subscriptionmissing = (int)$this->db->get_field_sql(
            'SELECT COUNT(DISTINCT us.userid)
               FROM {user_subscription} us
          LEFT JOIN {local_campus_trial} ct
                 ON ct.userid = us.userid
              WHERE us.planid = :subscriptionplanid
                AND ct.id IS NULL',
            ['subscriptionplanid' => $trialplanid]
        );

        return [
            'available' => true,
            'campus_users_without_trial_subscription' => $campusmissing,
            'campus_active_users_without_trial_subscription' => $campusactivemissing,
            'campus_historical_users_without_trial_subscription' => max(0, $campusmissing - $campusactivemissing),
            'trial_subscription_users_without_campus_row' => $subscriptionmissing,
        ];
    }

    private function integer_config(string $component, string $key, int $default): int {
        $value = get_config($component, $key);
        return $value === false || $value === '' ? $default : (int)$value;
    }
}
