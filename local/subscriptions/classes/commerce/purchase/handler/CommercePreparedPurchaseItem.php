<?php

namespace local_subscriptions\commerce\purchase\handler;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\purchase\CommercePurchaseRequestItem;

/**
 * Business-prepared purchase item.
 *
 * It contains all provider-independent information required by the future
 * payment and fulfillment layers.
 */
final class CommercePreparedPurchaseItem {

    public function __construct(
        private readonly CommercePurchaseRequestItem $requestitem,
        private readonly string $handlerkey,
        private readonly string $fulfillmentkey,
        private readonly array $paymentmetadata = [],
        private readonly array $fulfillmentmetadata = [],
        private readonly array $metadata = []
    ) {
        if (trim($handlerkey) === '') {
            throw new \coding_exception(
                'A prepared Commerce item handler key cannot be empty.'
            );
        }

        if (trim($fulfillmentkey) === '') {
            throw new \coding_exception(
                'A prepared Commerce item fulfillment key cannot be empty.'
            );
        }
    }

    public function get_request_item():
        CommercePurchaseRequestItem {
        return $this->requestitem;
    }

    public function get_handler_key(): string {
        return strtolower(
            trim($this->handlerkey)
        );
    }

    public function get_fulfillment_key(): string {
        return strtolower(
            trim($this->fulfillmentkey)
        );
    }

    public function get_payment_metadata(): array {
        return $this->paymentmetadata;
    }

    public function get_fulfillment_metadata(): array {
        return $this->fulfillmentmetadata;
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function get_total_amount_minor(): int {
        return $this->requestitem
            ->get_total_amount_minor();
    }

    public function get_currency(): string {
        return $this->requestitem
            ->get_currency();
    }

    public function is_free(): bool {
        return $this->requestitem->is_free();
    }
}