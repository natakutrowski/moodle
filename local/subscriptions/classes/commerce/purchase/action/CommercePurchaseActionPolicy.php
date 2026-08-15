<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\purchase\action;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseDetails;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseSummary;

/** Central safety policy for CRM purchase actions. */
final class CommercePurchaseActionPolicy {
    public function can_retry_fulfillment(CommercePurchaseDetails $purchase): bool {
        if ($purchase->summary->source !== 'native' || $purchase->legacyfamily !== null) {
            return false;
        }

        if (!in_array($purchase->summary->paymentstatus, ['paid', 'succeeded', 'completed'], true)) {
            return false;
        }
        foreach ($purchase->fulfillments as $fulfillment) {
            if (in_array($fulfillment->status, ['pending', 'running', 'failed', 'error'], true)) {
                return true;
            }
        }
        return $purchase->fulfillments === [];
    }

    public function can_retry_summary(CommercePurchaseSummary $purchase): bool {
        if ($purchase->source !== 'native') {
            return false;
        }

        $paymentready = in_array($purchase->paymentstatus, ['paid', 'succeeded', 'completed', 'captured'], true)
            || in_array($purchase->commercialstatus, ['paid', 'to_fulfill', 'partially_fulfilled'], true);

        if (!$paymentready) {
            return false;
        }

        return !in_array(
            $purchase->fulfillmentstatus,
            ['fulfilled', 'completed', 'succeeded', 'success'],
            true
        );
    }


    public function can_resend_receipt_summary(CommercePurchaseSummary $purchase): bool {
        return in_array(
            $purchase->paymentstatus,
            ['paid', 'succeeded', 'completed', 'captured'],
            true
        );
    }

    public function can_resend_access_summary(CommercePurchaseSummary $purchase): bool {
        if (!$this->can_resend_receipt_summary($purchase)) {
            return false;
        }

        return in_array(
            $purchase->fulfillmentstatus,
            ['fulfilled', 'completed', 'succeeded', 'success'],
            true
        );
    }

    public function can_create_personal_offer_summary(CommercePurchaseSummary $purchase): bool {
        return $purchase->customer->email !== '';
    }

    public function can_add_note(CommercePurchaseDetails $purchase): bool {
        return $purchase->summary->id > 0;
    }

    /** Destructive provider-aware actions are intentionally deferred until a Native command exists. */
    public function destructive_actions_available(): bool {
        return false;
    }
}
