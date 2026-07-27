<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\shadow;

defined('MOODLE_INTERNAL') || die();

/** Explicit G1 inventory of current fulfillment-triggering runtime paths. */
final class CommerceShadowEntryPointRegistry {
    /** @return CommerceShadowEntryPoint[] */
    public function all(): array {
        return [
            new CommerceShadowEntryPoint('stripe_webhook', 'Stripe webhook', CommerceShadowSource::STRIPE_WEBHOOK, 'webhook/stripe.php', 'both'),
            new CommerceShadowEntryPoint('stripe_return', 'Stripe payment return', CommerceShadowSource::STRIPE_RETURN, 'payment_success.php', 'subscription'),
            new CommerceShadowEntryPoint('alfa_webhook', 'AlfaBank webhook', CommerceShadowSource::ALFA_WEBHOOK, 'webhook/alfa.php', 'both'),
            new CommerceShadowEntryPoint('alfa_return', 'AlfaBank payment return', CommerceShadowSource::ALFA_RETURN, 'payment/return.php', 'subscription'),
            new CommerceShadowEntryPoint('digital_success', 'Digital purchase success', CommerceShadowSource::DIGITAL_SUCCESS, 'digital_success.php', 'digital'),
            new CommerceShadowEntryPoint('crm_manual_subscription', 'CRM manual subscription creation', CommerceShadowSource::CRM_MANUAL, 'admin/subscriptions/add.php', 'subscription'),
            new CommerceShadowEntryPoint('paid_request_repair', 'Paid payment-request repair', CommerceShadowSource::REPAIR_JOB, 'classes/commerce/task/job/PaidPaymentRequestRepairJob.php', 'both'),
            new CommerceShadowEntryPoint('digital_reconciliation', 'Digital payment reconciliation', CommerceShadowSource::RECONCILIATION_JOB, 'classes/commerce/task/job/DigitalPaymentReconciliationJob.php', 'digital'),
        ];
    }

    public function find(string $key): ?CommerceShadowEntryPoint {
        foreach ($this->all() as $entrypoint) {
            if ($entrypoint->get_key() === trim($key)) {
                return $entrypoint;
            }
        }
        return null;
    }
}
