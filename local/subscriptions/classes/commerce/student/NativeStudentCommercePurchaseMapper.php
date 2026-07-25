<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\student;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\persistence\CommercePurchasePersistenceSnapshot;

/** Maps native Commerce snapshots to the stable student-page view model. */
final class NativeStudentCommercePurchaseMapper {
    public function map_subscription(CommercePurchasePersistenceSnapshot $snapshot): \stdClass {
        $purchase = $snapshot->get_purchase()->to_record();
        $metadata = $this->decode((string)$purchase->metadatajson);
        $item = $snapshot->get_items()[0]->to_record();
        $payment = $this->latest_payment($snapshot);
        $itemmetadata = $this->decode((string)$item->metadatajson);
        $planid = $this->positive_int($metadata['plan_id'] ?? null)
            ?? $this->positive_int($itemmetadata['plan_id'] ?? null)
            ?? $this->positive_int($itemmetadata['legacy_id'] ?? null)
            ?? $this->reference_id((string)$item->itemreference, 'subscription-plan:');
        $startdate = $this->positive_int($metadata['start_date'] ?? null)
            ?? (int)($purchase->timecreated ?? 0);
        $enddate = $this->positive_int($metadata['end_date'] ?? null);
        $status = $this->subscription_display_status(
            (string)$purchase->status,
            $metadata['legacy_status'] ?? null,
            $startdate,
            $enddate
        );

        return (object)[
            'id' => (int)($purchase->legacyid ?? 0),
            'userid' => $purchase->userid,
            'planid' => $planid ?? 0,
            'status' => $status,
            'start_date' => $startdate,
            'end_date' => $enddate,
            'creation_date' => $purchase->timecreated,
            'last_update' => $purchase->timemodified,
            'pricepaid' => ((int)$purchase->totalminor) / 100,
            'currency' => (string)$purchase->currency,
            'payment_provider' => $payment?->provider,
            'transactionid' => $payment?->transactionid,
            'payment_request_id' => $payment?->legacyrequestid,
            'provider_subscription_id' => $metadata['provider_subscription_id'] ?? null,
            'provider_customer_id' => $metadata['provider_customer_id'] ?? null,
            'payment_failed' => !empty($metadata['payment_failed']),
            'discount_percent' => (int)($metadata['discount_percent'] ?? 0),
            'discount_amount' => (float)($metadata['discount_amount'] ?? 0),
            'discount_reason' => $metadata['discount_reason'] ?? null,
            'commerce_purchase_uuid' => (string)$purchase->purchaseuuid,
            'commerce_reference' => (string)$purchase->reference,
        ];
    }

    public function map_digital(CommercePurchasePersistenceSnapshot $snapshot): \stdClass {
        $purchase = $snapshot->get_purchase()->to_record();
        $metadata = $this->decode((string)$purchase->metadatajson);
        $customer = $this->decode((string)$purchase->customerjson);
        $item = $snapshot->get_items()[0]->to_record();
        $payment = $this->latest_payment($snapshot);
        $itemmetadata = $this->decode((string)$item->metadatajson);
        $productid = $this->positive_int($metadata['product_id'] ?? null)
            ?? $this->positive_int($itemmetadata['product_id'] ?? null)
            ?? $this->positive_int($itemmetadata['legacy_id'] ?? null)
            ?? $this->reference_id((string)$item->itemreference, 'digital-product:')
            ?? $this->product_id_from_slug((string)$item->itemreference);
        $status = $this->digital_display_status(
            (string)$purchase->status,
            $metadata['legacy_status'] ?? null,
            $payment?->status ?? null
        );

        return (object)[
            'id' => (int)($purchase->legacyid ?? 0),
            'userid' => $purchase->userid,
            'productid' => $productid ?? 0,
            'productname' => (string)($item->label ?? ''),
            'productslug' => $itemmetadata['slug'] ?? $this->reference_slug((string)$item->itemreference, 'digital-product:'),
            'status' => $status,
            'email' => $purchase->customeremail ?? ($customer['email'] ?? null),
            'firstname' => $customer['firstname'] ?? ($metadata['firstname'] ?? null),
            'lastname' => $customer['lastname'] ?? ($metadata['lastname'] ?? null),
            'price' => ((int)$purchase->totalminor) / 100,
            'currency' => (string)$purchase->currency,
            'payment_provider' => $payment?->provider,
            'transactionid' => $payment?->transactionid,
            'paymentid' => $payment?->legacyrequestid,
            'payment_date' => $payment?->paidat,
            'creation_date' => $purchase->timecreated,
            'last_update' => $purchase->timemodified,
            'download_token' => $metadata['download_token'] ?? null,
            'download_token_expires' => $this->positive_int($metadata['download_token_expires'] ?? null),
            'expiration_date' => $this->positive_int($metadata['expiration_date'] ?? null),
            'buyer_lang' => $metadata['buyer_language'] ?? null,
            'commerce_purchase_uuid' => (string)$purchase->purchaseuuid,
            'commerce_reference' => (string)$purchase->reference,
        ];
    }

    private function subscription_display_status(
        string $nativestatus,
        mixed $legacystatus,
        int $startdate,
        ?int $enddate
    ): string {
        $legacy = strtolower(
            trim((string)$legacystatus)
        );

        // Important: "replaced" porte une information métier
        // qui ne peut pas être déduite du statut Commerce canonique.
        if ($legacy === 'replaced') {
            return 'replaced';
        }

        if ($legacy !== '') {
            return $legacy;
        }

        $native = strtolower(
            trim($nativestatus)
        );

        if (in_array($native, [
            'active',
            'queued',
            'expired',
            'replaced',
            'canceled',
            'cancelled',
            'pending',
            'failed',
            'error',
            'suspended',
        ], true)) {
            return $native;
        }

        if (in_array($native, [
            'fulfilled',
            'captured',
            'paid',
            'completed',
        ], true)) {
            $now = time();

            if ($startdate > $now) {
                return 'queued';
            }

            if (
                $enddate !== null
                && $enddate < $now
            ) {
                return 'expired';
            }

            return 'active';
        }

        return $native !== ''
            ? $native
            : 'unknown';
    }

    private function digital_display_status(
        string $nativestatus,
        mixed $legacystatus,
        mixed $paymentstatus
    ): string {
        $legacy = strtolower(trim((string)$legacystatus));
        if ($legacy !== '') {
            return $legacy;
        }

        $payment = strtolower(trim((string)$paymentstatus));
        if (in_array($payment, ['paid', 'completed', 'pending', 'failed', 'cancelled', 'canceled', 'error'], true)) {
            return $payment;
        }

        $native = strtolower(trim($nativestatus));
        return match ($native) {
            'captured', 'fulfilled' => 'paid',
            default => $native !== '' ? $native : 'unknown',
        };
    }

    private function product_id_from_slug(string $reference): ?int {
        global $DB;

        $slug = $this->reference_slug($reference, 'digital-product:');
        if ($slug === null || ctype_digit($slug)) {
            return null;
        }

        $id = $DB->get_field('subscription_digital_product', 'id', ['slug' => $slug]);
        return $this->positive_int($id);
    }

    private function reference_slug(string $reference, string $prefix): ?string {
        if (!str_starts_with($reference, $prefix)) {
            return null;
        }
        $value = trim(substr($reference, strlen($prefix)));
        return $value !== '' ? $value : null;
    }

    private function latest_payment(CommercePurchasePersistenceSnapshot $snapshot): ?\stdClass {
        $payments = $snapshot->get_payments();
        if ($payments === []) { return null; }
        return $payments[array_key_last($payments)]->to_record();
    }

    private function decode(string $json): array {
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function reference_id(string $reference, string $prefix): ?int {
        if (!str_starts_with($reference, $prefix)) { return null; }
        return $this->positive_int(substr($reference, strlen($prefix)));
    }

    private function positive_int(mixed $value): ?int {
        $value = (int)$value;
        return $value > 0 ? $value : null;
    }
}
