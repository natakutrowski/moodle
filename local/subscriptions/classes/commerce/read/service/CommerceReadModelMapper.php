<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\read\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\read\dto\CommerceFulfillmentView;
use local_subscriptions\commerce\read\dto\CommercePaymentView;
use local_subscriptions\commerce\read\dto\CommercePurchaseView;

/** Maps native SQL records to stable read DTOs. */
final class CommerceReadModelMapper {
    public function map_purchase(\stdClass $purchase, array $items, array $payments, array $fulfillments): CommercePurchaseView {
        return new CommercePurchaseView(
            (string)$purchase->purchaseuuid,
            (string)$purchase->reference,
            (string)$purchase->type,
            $purchase->legacyfamily !== null ? (string)$purchase->legacyfamily : null,
            $purchase->legacyid !== null ? (int)$purchase->legacyid : null,
            $purchase->userid !== null ? (int)$purchase->userid : null,
            $purchase->customeremail !== null ? (string)$purchase->customeremail : null,
            (string)$purchase->status,
            (string)$purchase->currency,
            (int)$purchase->subtotalminor,
            (int)$purchase->discountminor,
            (int)$purchase->totalminor,
            (int)$purchase->timecreated,
            (int)$purchase->timemodified,
            array_map(fn(\stdClass $item): array => $this->map_item($item), $items),
            array_map(fn(\stdClass $payment): CommercePaymentView => $this->map_payment($payment), $payments),
            array_map(fn(\stdClass $fulfillment): CommerceFulfillmentView => $this->map_fulfillment($fulfillment), $fulfillments)
        );
    }

    private function map_item(\stdClass $item): array {
        return [
            'position' => (int)$item->position,
            'type' => (string)$item->itemtype,
            'reference' => (string)$item->itemreference,
            'label' => (string)$item->label,
            'quantity' => (int)$item->quantity,
            'currency' => (string)$item->currency,
            'unitminor' => (int)$item->unitminor,
            'grossminor' => (int)$item->grossminor,
            'discountminor' => (int)$item->discountminor,
            'netminor' => (int)$item->netminor,
            'fulfillment' => $this->decode_json((string)$item->fulfillmentjson),
            'metadata' => $this->decode_json((string)$item->metadatajson),
        ];
    }

    private function map_payment(\stdClass $payment): CommercePaymentView {
        return new CommercePaymentView(
            (int)$payment->sequence,
            $payment->provider !== null ? (string)$payment->provider : null,
            $payment->providerreference !== null ? (string)$payment->providerreference : null,
            (string)$payment->status,
            (string)$payment->currency,
            (int)$payment->amountminor,
            $payment->transactionid !== null ? (string)$payment->transactionid : null,
            $payment->legacyrequestid !== null ? (int)$payment->legacyrequestid : null,
            $payment->paidat !== null ? (int)$payment->paidat : null,
            $this->decode_json((string)$payment->metadatajson)
        );
    }

    private function map_fulfillment(\stdClass $fulfillment): CommerceFulfillmentView {
        return new CommerceFulfillmentView(
            (int)$fulfillment->sequence,
            (string)$fulfillment->reference,
            (string)$fulfillment->fulfillmentkey,
            (string)$fulfillment->idempotencykey,
            (string)$fulfillment->status,
            $this->decode_json((string)$fulfillment->metadatajson)
        );
    }

    private function decode_json(string $json): array {
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }
}
