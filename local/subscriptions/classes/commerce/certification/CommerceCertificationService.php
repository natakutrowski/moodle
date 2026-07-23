<?php

namespace local_subscriptions\commerce\certification;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\audit\integrity\CommerceIntegrityAuditor;
use local_subscriptions\commerce\audit\runtime\CommerceRuntimeAuditor;
use local_subscriptions\validation\CommerceReleaseValidator;
use local_subscriptions\validation\ValidationResult;

/** Central read-only service for Phase 7.93G.6 certification. */
final class CommerceCertificationService {
    public function certify(int $baseline): CommerceCertificationReport {
        $checks = [];

        $preflight = new ValidationResult();
        (new CommerceReleaseValidator())->validate($preflight);
        foreach ($preflight->checks() as $item) {
            $status = ($item['severity'] ?? '') === 'error'
                ? CommerceCertificationCheck::FAIL
                : (($item['severity'] ?? '') === 'warning' ? CommerceCertificationCheck::WARNING : CommerceCertificationCheck::PASS);
            $checks[] = new CommerceCertificationCheck('Architecture & configuration', (string)$item['code'], $status, (string)$item['message'], $item['context'] ?? []);
        }

        $runtime = (new CommerceRuntimeAuditor())->audit($baseline);
        foreach ($runtime->get_issues() as $issue) {
            $checks[] = new CommerceCertificationCheck(
                'Runtime & fulfillment',
                (string)($issue['code'] ?? 'runtime_issue'),
                ($issue['severity'] ?? '') === 'error' ? CommerceCertificationCheck::FAIL : CommerceCertificationCheck::WARNING,
                (string)($issue['message'] ?? '')
            );
        }
        if (!$runtime->has_errors() && !$runtime->has_warnings()) {
            $checks[] = new CommerceCertificationCheck('Runtime & fulfillment', 'runtime_integrity', CommerceCertificationCheck::PASS, 'Runtime and post-payment integrity checks passed.');
        }

        $integrity = (new CommerceIntegrityAuditor())->audit($baseline);
        foreach ($integrity->issues() as $issue) {
            $checks[] = new CommerceCertificationCheck(
                'Evidence & idempotence',
                (string)$issue['code'],
                $issue['severity'] === 'error' ? CommerceCertificationCheck::FAIL : CommerceCertificationCheck::WARNING,
                (string)$issue['message']
            );
        }
        if (!$integrity->has_errors() && !$integrity->has_warnings()) {
            $checks[] = new CommerceCertificationCheck('Evidence & idempotence', 'integrity_evidence', CommerceCertificationCheck::PASS, 'Recent paid records are fulfilled and provider identifiers are unique.', $integrity->metrics());
        }

        foreach ((new CommerceCertificationMatrix())->scenarios() as $scenario) {
            $checks[] = new CommerceCertificationCheck(
                'Scenario matrix',
                $scenario->get_key(),
                $scenario->is_enabled() ? CommerceCertificationCheck::PASS : CommerceCertificationCheck::WARNING,
                $scenario->is_enabled() ? 'Scenario is enabled and included in certification.' : 'Scenario toggle is disabled; functional evidence must be certified separately.',
                $scenario->to_array()
            );
        }

        $checks[] = new CommerceCertificationCheck(
            'Production safety',
            'commerce_kill_switch',
            CommerceCertificationCheck::PASS,
            'The global commerce_fulfillment_enabled switch is available for immediate rollback.',
            ['enabled' => !empty(get_config('local_subscriptions', 'commerce_fulfillment_enabled'))]
        );

        return new CommerceCertificationReport($checks, $baseline, time());
    }
}
