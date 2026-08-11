<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\certification\payment;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\persistence\CommercePersistenceSchema;

/** Certifies an actual paid Alfa Native Commerce purchase end to end. */
final class CommerceAlfaPurchaseCertifier {
    public function __construct(private readonly \moodle_database $database) {}

    public function certify(string $reference): array {
        $checks = [];
        $purchase = $this->database->get_record(CommercePersistenceSchema::TABLE_PURCHASE, ['reference' => trim($reference)]);
        $this->check($checks, 'purchase', $purchase !== false, $purchase ? 'Native Purchase found.' : 'Native Purchase not found.');
        if (!$purchase) {
            return $this->report($reference, false, $checks);
        }

        $payments = $this->database->get_records(CommercePersistenceSchema::TABLE_PAYMENT, ['purchaseid' => $purchase->id], 'sequence ASC');
        $alfapayments = array_filter($payments, static fn($payment): bool => strtolower((string)$payment->provider) === 'alfa');
        $paid = array_filter($alfapayments, static fn($payment): bool => in_array(strtolower((string)$payment->status), ['paid', 'succeeded', 'success'], true));
        $this->check($checks, 'alfa_payment', count($alfapayments) === 1, count($alfapayments) . ' Alfa payment(s) found; expected exactly one.');
        $this->check($checks, 'payment_paid', count($paid) === 1, count($paid) === 1 ? 'Alfa payment is paid.' : 'No unique paid Alfa payment found.');
        $this->check($checks, 'currency', strtoupper((string)$purchase->currency) === 'RUB', 'Purchase currency is ' . strtoupper((string)$purchase->currency) . '.');
        $this->check($checks, 'purchase_status', strtolower((string)$purchase->status) === 'fulfilled', 'Purchase status is ' . (string)$purchase->status . '.');

        $items = $this->database->get_records(CommercePersistenceSchema::TABLE_ITEM, ['purchaseid' => $purchase->id]);
        $grants = $this->database->get_records(
            'local_subs_commerce_grant',
            ['purchasereference' => (string) $purchase->reference],
            'id ASC'
        );
        $this->check($checks, 'items', count($items) >= 1, count($items) . ' commercial item(s) found.');
        $this->check($checks, 'grants', count($grants) >= 1, count($grants) . ' Grant(s) found.');

        $allactive = $grants !== [];
        $allcompleted = $grants !== [];
        $allattempted = $grants !== [];
        foreach ($grants as $grant) {
            $allactive = $allactive && strtolower((string)$grant->status) === 'active';
            $state = $this->database->get_record(
                'local_subs_commerce_ful_state',
                ['grantreference' => (string) $grant->grantreference]
            );
            $allcompleted = $allcompleted && $state && strtolower((string)$state->status) === 'completed';
            $allattempted = $allattempted && $this->database->record_exists(
                'local_subs_commerce_ful_attempt',
                [
                    'grantreference' => (string) $grant->grantreference,
                    'status' => 'completed',
                ]
            );
        }
        $this->check($checks, 'grant_lifecycle', $allactive, 'All Grants are active.');
        $this->check($checks, 'fulfillment_state', $allcompleted, 'All fulfillment states are completed.');
        $this->check($checks, 'fulfillment_attempt', $allattempted, 'Every Grant has a completed attempt.');

        $metadataok = true;
        foreach ($alfapayments as $payment) {
            $metadata = json_decode((string)$payment->metadatajson, true);
            $metadataok = $metadataok && is_array($metadata);
            $metadataok = $metadataok && trim((string)($payment->providerorderid ?? $payment->providerreference ?? '')) !== '';
        }
        $this->check($checks, 'provider_identity', $metadataok && $alfapayments !== [], 'Alfa provider identity and metadata are persisted.');

        return $this->report($reference, !in_array(false, array_column($checks, 'passed'), true), $checks);
    }

    private function report(string $reference, bool $certified, array $checks): array {
        return ['phase' => '7.95H4.10', 'provider' => 'alfa', 'purchase_reference' => trim($reference), 'certified' => $certified, 'checks' => $checks];
    }

    private function check(array &$checks, string $key, bool $passed, string $message): void {
        $checks[] = ['key' => $key, 'passed' => $passed, 'status' => $passed ? 'PASS' : 'FAIL', 'message' => $message];
    }
}
