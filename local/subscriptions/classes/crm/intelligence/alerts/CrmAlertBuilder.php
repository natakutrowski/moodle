<?php

namespace local_subscriptions\crm\intelligence\alerts;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\core\CrmIntelligenceLimits;

/**
 * Builds Dashboard alerts from persisted Intelligence snapshots.
 *
 * This builder belongs to the read side and must never invoke:
 * - UserIntelligenceBuilder;
 * - CustomerSuccessRuntime;
 * - RecommendationEngine;
 * - CRM collectors.
 */
final class CrmAlertBuilder {

    public function __construct(
        private readonly CrmAlertReadRepository
            $repository =
                new CrmAlertReadRepository()
    ) {
    }

    /**
     * Builds the highest-priority persisted Intelligence alerts.
     *
     * @param int $limit Maximum number of user snapshots inspected.
     * @return CrmAlert[]
     */
    public function build(
        int $limit =
            CrmIntelligenceLimits::DASHBOARD_ALERT_USERS
    ): array {
        $limit = max(1, min(200, $limit));

        $alerts = [];

        foreach (
            $this->repository
                ->get_latest_snapshots($limit)
            as $snapshot
        ) {
            foreach (
                $this->build_for_snapshot($snapshot)
                as $alert
            ) {
                $alerts[] = $alert;
            }
        }

        usort(
            $alerts,
            static function(
                CrmAlert $left,
                CrmAlert $right
            ): int {
                if (
                    $left->priority ===
                    $right->priority
                ) {
                    return
                        ($right->userid ?? 0) <=>
                        ($left->userid ?? 0);
                }

                return
                    $right->priority <=>
                    $left->priority;
            }
        );

        return array_slice(
            $alerts,
            0,
            CrmIntelligenceLimits::DASHBOARD_ALERTS
        );
    }

    /**
     * Builds alerts for one persisted snapshot.
     *
     * The rules intentionally mirror CrmAlertEngine.
     *
     * @param \stdClass $snapshot Persisted score snapshot.
     * @return CrmAlert[]
     */
    private function build_for_snapshot(
        \stdClass $snapshot
    ): array {
        $userid = (int)$snapshot->userid;
        $commercialscore =
            (int)$snapshot->commercialscore;
        $riskscore =
            (int)$snapshot->riskscore;

        $segments = self::decode_keys(
            $snapshot->segmentsjson ?? null
        );

        $opportunities = self::decode_keys(
            $snapshot->opportunitiesjson ?? null
        );

        $alerts = [];

        if ($riskscore >= 60) {
            $alerts[] = new CrmAlert(
                key: 'high_risk_user',
                severity: 'danger',
                priority: 95,
                userid: $userid
            );
        }

        if (
            in_array(
                'trial_to_purchase',
                $opportunities,
                true
            )
        ) {
            $alerts[] = new CrmAlert(
                key: 'trial_without_purchase',
                severity: 'warning',
                priority: 85,
                userid: $userid
            );
        }

        if (
            in_array(
                'winback_expired_customer',
                $opportunities,
                true
            )
        ) {
            $alerts[] = new CrmAlert(
                key: 'expired_without_reactivation',
                severity: 'warning',
                priority: 80,
                userid: $userid
            );
        }

        if (
            in_array(
                'cold_user',
                $segments,
                true
            )
        ) {
            $alerts[] = new CrmAlert(
                key: 'inactive_user',
                severity: 'warning',
                priority: 70,
                userid: $userid
            );
        }

        if (
            $commercialscore >= 60 &&
            $riskscore <= 20
        ) {
            $alerts[] = new CrmAlert(
                key: 'hot_opportunity',
                severity: 'success',
                priority: 75,
                userid: $userid
            );
        }

        return $alerts;
    }

    /**
     * Safely decodes a persisted JSON list.
     *
     * @param mixed $json JSON source.
     * @return string[]
     */
    private static function decode_keys(
        mixed $json
    ): array {
        if (
            !is_string($json) ||
            trim($json) === ''
        ) {
            return [];
        }

        try {
            $decoded = json_decode(
                $json,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException) {
            return [];
        }

        if (!is_array($decoded)) {
            return [];
        }

        $keys = [];

        foreach ($decoded as $value) {
            if (
                is_string($value) &&
                $value !== ''
            ) {
                $keys[] = $value;
            }
        }

        return array_values(
            array_unique($keys)
        );
    }
}