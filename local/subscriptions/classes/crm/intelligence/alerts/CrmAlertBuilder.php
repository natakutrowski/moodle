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
            $alerts[] = $this->create_alert(
                snapshot: $snapshot,
                key: 'high_risk_user',
                severity: 'danger',
                priority: 95
            );
        }

        if (
            in_array(
                'trial_to_purchase',
                $opportunities,
                true
            )
        ) {
            $alerts[] = $this->create_alert(
                snapshot: $snapshot,
                key: 'trial_without_purchase',
                severity: 'warning',
                priority: 85
            );
        }

        if (
            in_array(
                'winback_expired_customer',
                $opportunities,
                true
            )
        ) {
            $alerts[] = $this->create_alert(
                snapshot: $snapshot,
                key: 'expired_without_reactivation',
                severity: 'warning',
                priority: 80
            );
        }

        if (
            in_array(
                'cold_user',
                $segments,
                true
            )
        ) {
            $alerts[] = $this->create_alert(
                snapshot: $snapshot,
                key: 'inactive_user',
                severity: 'warning',
                priority: 70
            );
        }

        if (
            $commercialscore >= 60 &&
            $riskscore <= 20
        ) {
            $alerts[] = $this->create_alert(
                snapshot: $snapshot,
                key: 'hot_opportunity',
                severity: 'success',
                priority: 75
            );
        }

        return $alerts;
    }

    /**
     * Creates an enriched alert from one persisted Intelligence snapshot.
     *
     * All data comes from the repository query. This method must never reload
     * the Moodle user or invoke an Intelligence calculation.
     */
    private function create_alert(
        \stdClass $snapshot,
        string $key,
        string $severity,
        int $priority
    ): CrmAlert {
        return new CrmAlert(
            key: $key,
            severity: $severity,
            priority: $priority,
            userid: (int)$snapshot->userid,
            displayname: fullname($snapshot),
            email: self::nullable_string(
                $snapshot->email ?? null
            ),
            snapshottime: self::nullable_positive_int(
                $snapshot->snapshottime ?? null
            ),
            commercialscore: self::nullable_int(
                $snapshot->commercialscore ?? null
            ),
            engagementscore: self::nullable_int(
                $snapshot->engagementscore ?? null
            ),
            riskscore: self::nullable_int(
                $snapshot->riskscore ?? null
            ),
            globalscore: self::nullable_int(
                $snapshot->globalscore ?? null
            )
        );
    }

    /**
     * Normalizes an optional integer value.
     */
    private static function nullable_int(
        mixed $value
    ): ?int {
        if (
            $value === null ||
            $value === ''
        ) {
            return null;
        }

        return (int)$value;
    }

    /**
     * Normalizes an optional positive integer value.
     */
    private static function nullable_positive_int(
        mixed $value
    ): ?int {
        $value = self::nullable_int($value);

        if (
            $value === null ||
            $value <= 0
        ) {
            return null;
        }

        return $value;
    }

    /**
     * Normalizes an optional string value.
     */
    private static function nullable_string(
        mixed $value
    ): ?string {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== ''
            ? $value
            : null;
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