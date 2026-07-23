<?php

namespace local_subscriptions\commerce\audit\integrity;

defined('MOODLE_INTERNAL') || die();

/**
 * Evidence-based audit of paid records created since the Commerce baseline.
 * It never calls a provider and never mutates payment data.
 */
final class CommerceIntegrityAuditor {
    public function audit(int $baseline): CommerceIntegrityAuditReport {
        global $DB;
        $metrics = ['baseline' => $baseline];
        $issues = [];

        $this->audit_subscription_evidence($baseline, $metrics, $issues);
        $this->audit_digital_evidence($baseline, $metrics, $issues);
        $this->audit_duplicate_sessions($baseline, $metrics, $issues);

        return new CommerceIntegrityAuditReport($metrics, $issues);
    }

    private function audit_subscription_evidence(int $baseline, array &$metrics, array &$issues): void {
        global $DB;
        if (!$DB->get_manager()->table_exists('subscription_payment_request')) {
            $issues[] = $this->issue('error', 'subscription_table_missing', 'subscription_payment_request is missing.');
            return;
        }
        $paid = (int)$DB->count_records_sql(
            "SELECT COUNT(1) FROM {subscription_payment_request} WHERE status IN ('paid','completed') AND creation_date >= :baseline",
            ['baseline' => $baseline]
        );
        $healthy = (int)$DB->count_records_sql(
            "SELECT COUNT(1)
               FROM {subscription_payment_request} pr
               JOIN {user_subscription} s ON s.id = pr.subscriptionid
              WHERE pr.status IN ('paid','completed') AND pr.creation_date >= :baseline",
            ['baseline' => $baseline]
        );
        $metrics['recent_paid_subscription_requests'] = $paid;
        $metrics['recent_healthy_subscription_requests'] = $healthy;
        if ($paid === 0) {
            $issues[] = $this->issue('warning', 'subscription_evidence_missing', 'No paid subscription evidence exists since the baseline.');
        } else if ($healthy !== $paid) {
            $issues[] = $this->issue('error', 'subscription_integrity_failed', ($paid - $healthy) . ' recent paid subscription request(s) have no linked subscription.');
        }
    }

    private function audit_digital_evidence(int $baseline, array &$metrics, array &$issues): void {
        global $DB;
        if (!$DB->get_manager()->table_exists('subscription_digital_payment_request')) {
            $issues[] = $this->issue('error', 'digital_table_missing', 'subscription_digital_payment_request is missing.');
            return;
        }
        $paid = (int)$DB->count_records_sql(
            "SELECT COUNT(1) FROM {subscription_digital_payment_request} WHERE status IN ('paid','completed') AND creation_date >= :baseline",
            ['baseline' => $baseline]
        );
        $healthy = (int)$DB->count_records_sql(
            "SELECT COUNT(1) FROM {subscription_digital_payment_request}
              WHERE status IN ('paid','completed') AND creation_date >= :baseline
                AND download_token IS NOT NULL AND download_token <> ''",
            ['baseline' => $baseline]
        );
        $metrics['recent_paid_digital_requests'] = $paid;
        $metrics['recent_healthy_digital_requests'] = $healthy;
        if ($paid === 0) {
            $issues[] = $this->issue('warning', 'digital_evidence_missing', 'No paid digital evidence exists since the baseline.');
        } else if ($healthy !== $paid) {
            $issues[] = $this->issue('error', 'digital_integrity_failed', ($paid - $healthy) . ' recent paid digital request(s) have no token.');
        }
    }

    private function audit_duplicate_sessions(int $baseline, array &$metrics, array &$issues): void {
        global $DB;
        foreach ([
            'subscription_payment_request' => 'subscription',
            'subscription_digital_payment_request' => 'digital',
        ] as $table => $label) {
            if (!$DB->get_manager()->table_exists($table)) {
                continue;
            }
            $duplicates = $DB->get_records_sql(
                "SELECT MIN(id) AS id, sessionid, COUNT(1) AS duplicatecount
                   FROM {{$table}}
                  WHERE creation_date >= :baseline AND sessionid IS NOT NULL AND sessionid <> ''
               GROUP BY sessionid HAVING COUNT(1) > 1",
                ['baseline' => $baseline]
            );
            $metrics['recent_duplicate_' . $label . '_sessions'] = count($duplicates);
            if ($duplicates !== []) {
                $issues[] = $this->issue('error', 'duplicate_' . $label . '_session', count($duplicates) . ' duplicated recent provider session identifier(s) were found.');
            }
        }
    }

    private function issue(string $severity, string $code, string $message): array {
        return ['severity' => $severity, 'code' => $code, 'message' => $message];
    }
}
