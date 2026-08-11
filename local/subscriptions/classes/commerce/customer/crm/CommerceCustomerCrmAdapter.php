<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\crm;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\customer\readmodel\CommerceCustomerPurchase;
use local_subscriptions\commerce\customer\readmodel\CommerceCustomerSnapshot;
use local_subscriptions\crm\user\UserProfileStats;

/**
 * Adapts the unified Commerce snapshot to stable CRM/User360 presentations.
 *
 * This class deliberately contains presentation compatibility only. All
 * Commerce identity, payment, purchase and Grant rules remain in the unified
 * read model.
 */
final class CommerceCustomerCrmAdapter {
    /** @return \stdClass[] */
    public function purchase_rows(CommerceCustomerSnapshot $snapshot): array {
        return array_map(
            fn(CommerceCustomerPurchase $purchase): \stdClass => $this->purchase_row($purchase),
            $snapshot->purchases
        );
    }

    public function stats(
        CommerceCustomerSnapshot $snapshot,
        int $accessiblecourses,
        int $lastactivity,
        bool $usersuspended = false,
        ?string $legacystatus = null
    ): UserProfileStats {
        $metrics = $snapshot->metrics;

        $status = $usersuspended
            ? 'suspended'
            : $this->customer_status($snapshot, $legacystatus);

        return new UserProfileStats(
            $status,
            (int)(($metrics->purchasebytype['course'] ?? 0) + ($metrics->purchasebytype['upgrade'] ?? 0)),
            (int)($metrics->purchasebytype['digital'] ?? 0),
            $accessiblecourses,
            (($metrics->revenuebycurrency['EUR'] ?? 0) / 100),
            (($metrics->revenuebycurrency['RUB'] ?? 0) / 100),
            $lastactivity,
            $metrics->purchasecount,
            $metrics->successfulpurchasecount,
            (int)($metrics->purchasebytype['bundle'] ?? 0),
            (int)($metrics->purchasebytype['upgrade'] ?? 0),
            $metrics->paymentattemptcount,
            $metrics->activegrantcount,
            $snapshot->identity->hasguesthistory
        );
    }

    private function customer_status(CommerceCustomerSnapshot $snapshot, ?string $legacystatus): string {
        if ($snapshot->metrics->activegrantcount > 0 || $snapshot->metrics->successfulpurchasecount > 0) {
            return 'active_customer';
        }

        if ($snapshot->metrics->purchasecount > 0) {
            return 'former_customer';
        }

        return $legacystatus ?: 'lead';
    }

    private function purchase_row(CommerceCustomerPurchase $purchase): \stdClass {
        $payment = null;
        foreach ($purchase->payments as $candidate) {
            if ($candidate->is_successful()) {
                $payment = $candidate;
                break;
            }
            $payment ??= $candidate;
        }

        $labels = [];
        foreach ($purchase->items as $item) {
            $label = trim((string)($item['label'] ?? ''));
            if ($label !== '') {
                $labels[] = $label;
            }
        }

        return (object)[
            'id' => $purchase->id,
            'reference' => $purchase->reference,
            'publicreference' => $purchase->publicreference,
            'type' => $purchase->type,
            'status' => $purchase->status,
            'currency' => $purchase->currency,
            'totalminor' => $purchase->totalminor,
            'total' => $purchase->totalminor / 100,
            'timecreated' => $purchase->timecreated,
            'timemodified' => $purchase->timemodified,
            'labels' => $labels,
            'label' => $labels !== [] ? implode(', ', $labels) : $purchase->publicreference,
            'items' => $purchase->items,
            'payments' => $purchase->payments,
            'grants' => $purchase->grants,
            'paymentstatus' => $payment?->status ?? '',
            'provider' => $payment?->provider ?? '',
            'transactionid' => $payment?->transactionid ?? '',
            'paidat' => $payment?->paidat,
            'successful' => $purchase->has_successful_payment(),
            'failedpayment' => $purchase->has_failed_payment(),
            'orderurl' => (new \moodle_url('/local/subscriptions/order_details.php', [
                'reference' => $purchase->reference,
            ]))->out(false),
        ];
    }
}
