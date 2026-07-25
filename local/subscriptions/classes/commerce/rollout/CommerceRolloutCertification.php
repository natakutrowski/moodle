<?php

declare(strict_types=1);
namespace local_subscriptions\commerce\rollout;
defined('MOODLE_INTERNAL') || die();
final class CommerceRolloutCertification {
    public function checklist(): array {
        return [
            'phpunit_suite' => 'Full local_subscriptions PHPUnit suite passes',
            'runtime_paths' => 'Runtime path matrix is complete',
            'digital_stripe' => 'Digital Stripe payment reconciles with zero issues',
            'subscription_stripe' => 'Subscription Stripe payment reconciles with zero issues',
            'digital_alfa' => 'Digital Alfa payment reconciles with zero issues',
            'subscription_alfa' => 'Subscription Alfa payment reconciles with zero issues',
            'manual_admin' => 'Manual Admin add/edit/delete/view smoke tests pass',
            'emails' => 'Transactional and resend emails pass',
            'cron' => 'Commerce cron run completes without divergence',
            'duplicate_callback' => 'Duplicate callback is idempotent',
            'repair_dry_run' => 'Reconciliation detection produces reviewed results',
        ];
    }
}
