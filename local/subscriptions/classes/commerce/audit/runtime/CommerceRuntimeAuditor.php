<?php

namespace local_subscriptions\commerce\audit\runtime;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\runtime\CommerceRuntimeFactory;

/**
 * Audits fulfilled Commerce/Legacy records without mutating payment data.
 *
 * A baseline timestamp separates historical Legacy debt from recent Commerce
 * regressions. Historical inconsistencies remain visible but do not block a
 * release. Recent access/token failures are blocking.
 */
final class CommerceRuntimeAuditor {

    public function audit(?int $baseline = null): CommerceRuntimeAuditReport {
        global $DB;

        $checks = [];
        $issues = [];

        $baseline = $baseline !== null && $baseline > 0 ? $baseline : null;
        $checks['audit_baseline'] = $baseline === null
            ? 'NOT_CONFIGURED'
            : userdate($baseline, '%Y-%m-%d %H:%M:%S %Z');

        if ($baseline === null) {
            $issues[] = $this->issue(
                'warning',
                'audit_baseline_not_configured',
                'No Commerce audit baseline is configured. Existing inconsistencies are treated as historical warnings. '
                    . 'Use --since=YYYY-MM-DD or configure commerce_runtime_audit_since.'
            );
        }

        try {
            $runtime = CommerceRuntimeFactory::create();
            $checks['runtime'] = $runtime !== null ? 'OK' : 'ERROR';
            $checks['subscription_fulfillment_handler'] =
                $runtime->fulfillment_handlers()->has('subscription_enrolment') ? 'OK' : 'ERROR';
            $checks['digital_fulfillment_handler'] =
                $runtime->fulfillment_handlers()->has('digital_download') ? 'OK' : 'ERROR';
        } catch (\Throwable $exception) {
            $checks['runtime'] = 'ERROR';
            $issues[] = $this->issue('error', 'runtime_unavailable', $exception->getMessage());
        }

        if ($DB->get_manager()->table_exists('subscription_payment_request')) {
            $this->audit_subscription_requests($baseline, $checks, $issues);
        }

        if ($DB->get_manager()->table_exists('subscription_digital_payment_request')) {
            $this->audit_digital_requests($baseline, $checks, $issues);
        }

        $checks['commerce_fulfillment_enabled'] =
            !empty(get_config('local_subscriptions', 'commerce_fulfillment_enabled'));
        $checks['shadow_enabled'] =
            !empty(get_config('local_subscriptions', 'commerce_checkout_shadow_enabled'));

        return new CommerceRuntimeAuditReport($checks, $issues);
    }

    private function audit_subscription_requests(?int $baseline, array &$checks, array &$issues): void {
        global $DB;

        $basewhere = "pr.status IN ('paid', 'completed')
                      AND (pr.subscriptionid IS NULL OR s.id IS NULL)";

        $historical = $this->count_partitioned(
            'subscription_payment_request',
            'pr',
            'LEFT JOIN {user_subscription} s ON s.id = pr.subscriptionid',
            $basewhere,
            $baseline,
            false
        );
        $recent = $this->count_partitioned(
            'subscription_payment_request',
            'pr',
            'LEFT JOIN {user_subscription} s ON s.id = pr.subscriptionid',
            $basewhere,
            $baseline,
            true
        );

        $checks['historical_paid_subscription_requests_without_access'] = $historical;
        $checks['recent_paid_subscription_requests_without_access'] = $recent;

        if ($historical > 0) {
            $issues[] = $this->issue(
                'warning',
                'historical_paid_subscription_without_access',
                $historical . ' historical paid subscription payment request(s) have no valid linked subscription.'
            );
        }
        if ($recent > 0) {
            $issues[] = $this->issue(
                'error',
                'recent_paid_subscription_without_access',
                $recent . ' paid subscription payment request(s) created since the audit baseline have no valid linked subscription.'
            );
        }

        $emailwhere = "pr.status IN ('paid', 'completed') AND COALESCE(pr.emailsent, 0) = 0";
        $historicalemails = $this->count_partitioned(
            'subscription_payment_request', 'pr', '', $emailwhere, $baseline, false
        );
        $recentemails = $this->count_partitioned(
            'subscription_payment_request', 'pr', '', $emailwhere, $baseline, true
        );
        $checks['historical_paid_subscription_requests_without_email'] = $historicalemails;
        $checks['recent_paid_subscription_requests_without_email'] = $recentemails;

        if ($historicalemails > 0) {
            $issues[] = $this->issue(
                'warning',
                'historical_subscription_email_incomplete',
                $historicalemails . ' historical paid subscription payment request(s) have emailsent=0.'
            );
        }
        if ($recentemails > 0) {
            $issues[] = $this->issue(
                'warning',
                'recent_subscription_email_incomplete',
                $recentemails . ' recent paid subscription payment request(s) have emailsent=0.'
            );
        }

        $duplicates = $DB->get_records_sql(
            "SELECT MIN(id) AS id, transactionid, COUNT(1) AS duplicatecount
               FROM {subscription_payment_request}
              WHERE transactionid IS NOT NULL AND transactionid <> ''
           GROUP BY transactionid
             HAVING COUNT(1) > 1"
        );
        $checks['duplicate_subscription_transactions'] = count($duplicates);
        if ($duplicates !== []) {
            $issues[] = $this->issue(
                'error',
                'duplicate_subscription_transaction',
                count($duplicates) . ' duplicated subscription provider transaction identifier(s) were found.'
            );
        }
    }

    private function audit_digital_requests(?int $baseline, array &$checks, array &$issues): void {
        global $DB;

        $tokenwhere = "pr.status IN ('paid', 'completed')
                       AND (pr.download_token IS NULL OR pr.download_token = '')";
        $historicaltokens = $this->count_partitioned(
            'subscription_digital_payment_request', 'pr', '', $tokenwhere, $baseline, false
        );
        $recenttokens = $this->count_partitioned(
            'subscription_digital_payment_request', 'pr', '', $tokenwhere, $baseline, true
        );
        $checks['historical_paid_digital_requests_without_token'] = $historicaltokens;
        $checks['recent_paid_digital_requests_without_token'] = $recenttokens;

        if ($historicaltokens > 0) {
            $issues[] = $this->issue(
                'warning',
                'historical_paid_digital_without_token',
                $historicaltokens . ' historical paid digital payment request(s) have no download token.'
            );
        }
        if ($recenttokens > 0) {
            $issues[] = $this->issue(
                'error',
                'recent_paid_digital_without_token',
                $recenttokens . ' paid digital payment request(s) created since the audit baseline have no download token.'
            );
        }

        $emailwhere = "pr.status IN ('paid', 'completed')
                       AND (COALESCE(pr.emailsent, 0) = 0 OR COALESCE(pr.receipt_sent, 0) = 0)";
        $historicalemails = $this->count_partitioned(
            'subscription_digital_payment_request', 'pr', '', $emailwhere, $baseline, false
        );
        $recentemails = $this->count_partitioned(
            'subscription_digital_payment_request', 'pr', '', $emailwhere, $baseline, true
        );
        $checks['historical_paid_digital_requests_with_incomplete_emails'] = $historicalemails;
        $checks['recent_paid_digital_requests_with_incomplete_emails'] = $recentemails;

        if ($historicalemails > 0) {
            $issues[] = $this->issue(
                'warning',
                'historical_digital_email_incomplete',
                $historicalemails . ' historical paid digital payment request(s) have incomplete email flags.'
            );
        }
        if ($recentemails > 0) {
            $issues[] = $this->issue(
                'warning',
                'recent_digital_email_incomplete',
                $recentemails . ' recent paid digital payment request(s) have incomplete email flags.'
            );
        }

        $duplicates = $DB->get_records_sql(
            "SELECT MIN(id) AS id, transactionid, COUNT(1) AS duplicatecount
               FROM {subscription_digital_payment_request}
              WHERE transactionid IS NOT NULL AND transactionid <> ''
           GROUP BY transactionid
             HAVING COUNT(1) > 1"
        );
        $checks['duplicate_digital_transactions'] = count($duplicates);
        if ($duplicates !== []) {
            $issues[] = $this->issue(
                'error',
                'duplicate_digital_transaction',
                count($duplicates) . ' duplicated digital provider transaction identifier(s) were found.'
            );
        }
    }

    /**
     * Counts either historical or recent records. Without a baseline, all
     * matching records are historical and the recent count is zero.
     */
    private function count_partitioned(
        string $table,
        string $alias,
        string $join,
        string $where,
        ?int $baseline,
        bool $recent
    ): int {
        global $DB;

        if ($baseline === null && $recent) {
            return 0;
        }

        $params = [];
        if ($baseline === null) {
            $dateclause = '';
        } else if ($recent) {
            $dateclause = " AND {$alias}.creation_date >= :baseline";
            $params['baseline'] = $baseline;
        } else {
            $dateclause = " AND {$alias}.creation_date < :baseline";
            $params['baseline'] = $baseline;
        }

        return (int)$DB->count_records_sql(
            "SELECT COUNT(1)
               FROM {{$table}} {$alias}
               {$join}
              WHERE {$where}{$dateclause}",
            $params
        );
    }

    private function issue(string $severity, string $code, string $message): array {
        return [
            'severity' => $severity,
            'code' => $code,
            'message' => $message,
        ];
    }
}