<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\crm;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\customer\readmodel\CommerceCustomerGrant;
use local_subscriptions\commerce\customer\readmodel\CommerceCustomerPayment;
use local_subscriptions\commerce\customer\readmodel\CommerceCustomerPurchase;
use local_subscriptions\commerce\customer\readmodel\CommerceCustomerSnapshot;
use local_subscriptions\crm\user\UserProfileTimelineEvent;

/** Builds Native Commerce timeline events from one unified customer snapshot. */
final class CommerceCustomerTimelineCollector {
    /** @return UserProfileTimelineEvent[] */
    public function collect(CommerceCustomerSnapshot $snapshot): array {
        $events = [];

        foreach ($snapshot->purchases as $purchase) {
            $events[] = $this->purchase_event($purchase);
            foreach ($purchase->payments as $payment) {
                $events[] = $this->payment_event($purchase, $payment);
            }
        }

        foreach ($snapshot->grants as $grant) {
            $events[] = $this->grant_event($grant);
        }

        return $events;
    }

    private function purchase_event(CommerceCustomerPurchase $purchase): UserProfileTimelineEvent {
        $typekey = in_array($purchase->type, ['course', 'digital', 'bundle', 'upgrade', 'mixed'], true)
            ? $purchase->type
            : 'purchase';

        return new UserProfileTimelineEvent(
            'commerce_purchase_' . $typekey,
            $purchase->timecreated,
            get_string('crm_timeline_commerce_purchase_' . $typekey, 'local_subscriptions'),
            $this->purchase_description($purchase),
            $this->purchase_icon($typekey),
            $purchase->has_successful_payment() ? 'medium' : 'normal',
            (new \moodle_url('/local/subscriptions/order_details.php', [
                'reference' => $purchase->reference,
            ]))->out(false),
            [
                'objecttype' => 'commerce_purchase',
                'objectid' => $purchase->id,
                'purchaseid' => $purchase->id,
                'reference' => $purchase->publicreference,
                'purchasetype' => $purchase->type,
                'status' => $purchase->status,
                'currency' => $purchase->currency,
                'amountminor' => $purchase->totalminor,
                'actorid' => 0,
                'rawtype' => 'commerce_purchase',
            ]
        );
    }

    private function payment_event(
        CommerceCustomerPurchase $purchase,
        CommerceCustomerPayment $payment
    ): UserProfileTimelineEvent {
        $state = $payment->is_successful()
            ? 'paid'
            : ($payment->is_failed() ? 'failed' : 'pending');

        return new UserProfileTimelineEvent(
            'commerce_payment_' . $state,
            $payment->paidat ?? $payment->timemodified,
            get_string('crm_timeline_commerce_payment_' . $state, 'local_subscriptions'),
            get_string('crm_timeline_commerce_payment_description', 'local_subscriptions', (object)[
                'reference' => $purchase->publicreference,
                'amount' => format_float($payment->amountminor / 100, 2) . ' ' . $payment->currency,
                'provider' => $payment->provider ?: '-',
            ]),
            $state === 'paid' ? '💳' : ($state === 'failed' ? '⚠️' : '🧾'),
            $state === 'failed' ? 'high' : ($state === 'paid' ? 'medium' : 'normal'),
            (new \moodle_url('/local/subscriptions/order_details.php', [
                'reference' => $purchase->reference,
            ]))->out(false),
            [
                'objecttype' => 'commerce_payment',
                'objectid' => $payment->id,
                'purchaseid' => $purchase->id,
                'reference' => $purchase->publicreference,
                'status' => $payment->status,
                'provider' => $payment->provider,
                'transactionid' => $payment->transactionid,
                'currency' => $payment->currency,
                'amountminor' => $payment->amountminor,
                'actorid' => 0,
                'rawtype' => 'commerce_payment',
            ]
        );
    }

    private function grant_event(CommerceCustomerGrant $grant): UserProfileTimelineEvent {
        $type = in_array($grant->type, ['course_access', 'digital_download'], true)
            ? $grant->type
            : 'access';

        return new UserProfileTimelineEvent(
            'commerce_grant_' . $type,
            $grant->validfrom,
            get_string('crm_timeline_commerce_grant_' . $type, 'local_subscriptions'),
            $grant->productsku,
            $type === 'course_access' ? '🎓' : ($type === 'digital_download' ? '📥' : '🔑'),
            $grant->is_active() ? 'medium' : 'normal',
            null,
            [
                'objecttype' => 'commerce_grant',
                'objectid' => $grant->id,
                'grantreference' => $grant->reference,
                'purchasereference' => $grant->purchasereference,
                'productsku' => $grant->productsku,
                'resourcetype' => $grant->type,
                'resourcekey' => $grant->resourcekey,
                'status' => $grant->status,
                'actorid' => 0,
                'rawtype' => 'commerce_grant',
            ]
        );
    }

    private function purchase_description(CommerceCustomerPurchase $purchase): string {
        $labels = [];
        foreach ($purchase->items as $item) {
            $label = trim((string)($item['label'] ?? ''));
            if ($label !== '') {
                $labels[] = $label;
            }
        }

        return get_string('crm_timeline_commerce_purchase_description', 'local_subscriptions', (object)[
            'reference' => $purchase->publicreference,
            'items' => $labels !== [] ? implode(', ', $labels) : '-',
            'amount' => format_float($purchase->totalminor / 100, 2) . ' ' . $purchase->currency,
        ]);
    }

    private function purchase_icon(string $type): string {
        return match ($type) {
            'course' => '🎓',
            'digital' => '📦',
            'bundle' => '🧩',
            'upgrade' => '⬆️',
            'mixed' => '🛒',
            default => '🧾',
        };
    }
}
