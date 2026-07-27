<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\shadow\certification;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\runtime\switching\CommerceRuntimeMode;
use local_subscriptions\commerce\shadow\CommerceShadowDivergenceClassifier;

/** Functional and structural certification for phase 7.94H7. */
final class CommerceRuntimeH7CertificationAuditor {
    public function audit(?int $since = null, ?int $userid = null): array {
        global $CFG, $DB;

        $since = $since ?? (int)get_config('local_subscriptions', 'commerce_runtime_h7_since');
        $since = max(0, $since);

        $params = ['since' => $since];
        $userwhere = '';
        if ($userid !== null && $userid > 0) {
            $userwhere = ' AND p.userid = :userid';
            $params['userid'] = $userid;
        }

        $basefrom = "
              FROM {local_subs_commerce_shadow} s
              JOIN {local_subscriptions_commerce_purchase} p
                ON p.reference = s.purchasereference
             WHERE s.timecreated >= :since{$userwhere}";

        $total = (int)$DB->count_records_sql('SELECT COUNT(1)' . $basefrom, $params);
        $subscription = (int)$DB->count_records_sql(
            "SELECT COUNT(1){$basefrom} AND p.legacyfamily = :subscriptionfamily",
            $params + ['subscriptionfamily' => 'subscription']
        );
        $digital = (int)$DB->count_records_sql(
            "SELECT COUNT(1){$basefrom} AND p.legacyfamily = :digitalfamily",
            $params + ['digitalfamily' => 'digital']
        );
        $businessdifferences = (int)$DB->count_records_sql(
            "SELECT COUNT(1){$basefrom} AND s.classification = :businessdifference",
            $params + ['businessdifference' => CommerceShadowDivergenceClassifier::BUSINESS_DIFFERENCE]
        );
        $shadowfailures = (int)$DB->count_records_sql(
            "SELECT COUNT(1){$basefrom} AND s.classification = :shadowfailure",
            $params + ['shadowfailure' => CommerceShadowDivergenceClassifier::SHADOW_FAILURE]
        );
        $duplicates = (int)$DB->count_records_sql(
            "SELECT COUNT(1) FROM (
                SELECT s.purchasereference
                  {$basefrom}
              GROUP BY s.purchasereference
                HAVING COUNT(1) > 1
            ) duplicate_refs",
            $params
        );
        $imprecise = (int)$DB->count_records_sql(
            "SELECT COUNT(1){$basefrom}
               AND (s.entrypoint = :repair OR s.entrypoint = :runtime OR s.entrypoint LIKE :dualwrite)",
            $params + [
                'repair' => 'repair_job',
                'runtime' => 'runtime',
                'dualwrite' => 'dualwrite.%',
            ]
        );

        $root = $CFG->dirroot . '/local/subscriptions';
        $checks = [
            'trigger_context_present' => is_file($root . '/classes/commerce/shadow/runtime/CommerceShadowTriggerContext.php'),
            'dualwrite_instrumented' => str_contains(
                (string)file_get_contents($root . '/classes/commerce/dualwrite/CommerceDualWriteBridge.php'),
                'CommerceDualWriteShadowObserver::after_synchronise'
            ),
            'runtime_mode_shadow' => (string)get_config('local_subscriptions', 'commerce_runtime_mode') === CommerceRuntimeMode::SHADOW,
            'shadow_enabled' => (bool)get_config('local_subscriptions', 'commerce_fulfillment_shadow_enabled'),
            'subscription_observed' => $subscription >= 1,
            'digital_observed' => $digital >= 1,
            'no_business_difference' => $businessdifferences === 0,
            'no_shadow_failure' => $shadowfailures === 0,
            'no_duplicate_purchase' => $duplicates === 0,
            'precise_entrypoints' => $imprecise === 0,
        ];

        $errors = count(array_filter($checks, static fn(bool $ok): bool => !$ok));

        return [
            'phase' => '7.94H7',
            'since' => $since,
            'userid' => $userid,
            'checks' => $checks,
            'metrics' => [
                'executions' => $total,
                'subscription' => $subscription,
                'digital' => $digital,
                'business_difference' => $businessdifferences,
                'shadow_failure' => $shadowfailures,
                'duplicate_purchase_references' => $duplicates,
                'imprecise_entrypoints' => $imprecise,
            ],
            'errors' => $errors,
            'certified' => $errors === 0,
        ];
    }
}
