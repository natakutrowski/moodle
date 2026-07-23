<?php

namespace local_subscriptions\commerce\purchase\preparation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\purchase\CommercePurchaseRequest;
use local_subscriptions\commerce\purchase\handler\CommercePreparedPurchaseItem;
use local_subscriptions\commerce\purchase\handler\CommercePurchaseValidationResult;

/**
 * Fully validated provider-independent Commerce purchase preparation.
 */
final class CommercePurchasePreparation {

    /**
     * @param CommercePreparedPurchaseItem[] $items
     */
    public function __construct(
        private readonly CommercePurchaseRequest $request,
        private readonly array $items,
        private readonly CommercePurchaseValidationResult $validation,
        private readonly int $preparedat
    ) {
        if ($items === []) {
            throw new \coding_exception(
                'A Commerce purchase preparation must contain prepared items.'
            );
        }

        foreach ($items as $item) {
            if (!$item instanceof CommercePreparedPurchaseItem) {
                throw new \coding_exception(
                    'A Commerce purchase preparation contains an invalid item.'
                );
            }
        }

        if (!$validation->is_valid()) {
            throw new \coding_exception(
                'An invalid Commerce request cannot become a preparation.'
            );
        }

        if ($preparedat <= 0) {
            throw new \coding_exception(
                'A Commerce preparation timestamp must be positive.'
            );
        }
    }

    public function get_request():
        CommercePurchaseRequest {
        return $this->request;
    }

    /**
     * @return CommercePreparedPurchaseItem[]
     */
    public function get_items(): array {
        return $this->items;
    }

    public function get_validation():
        CommercePurchaseValidationResult {
        return $this->validation;
    }

    public function get_prepared_at(): int {
        return $this->preparedat;
    }

    public function get_reference(): string {
        return $this->request
            ->get_reference();
    }

    public function get_currency(): string {
        return $this->request
            ->get_currency();
    }

    public function get_total_amount_minor(): int {
        return array_sum(
            array_map(
                static fn(
                    CommercePreparedPurchaseItem $item
                ): int => $item->get_total_amount_minor(),
                $this->items
            )
        );
    }

    public function is_free(): bool {
        return $this->get_total_amount_minor() === 0;
    }

    public function requires_payment(): bool {
        return !$this->is_free();
    }

    public function contains_multiple_items(): bool {
        return count($this->items) > 1;
    }

    /**
     * Returns provider-independent payment line data.
     */
    public function get_payment_lines(): array {
        return array_map(
            static function(
                CommercePreparedPurchaseItem $item
            ): array {
                $requestitem =
                    $item->get_request_item();

                return [
                    'reference' =>
                        $requestitem
                            ->get_item()
                            ->get_reference(),

                    'handler' =>
                        $item->get_handler_key(),

                    'description' =>
                        $item->get_payment_metadata()[
                            'description'
                        ] ?? $requestitem
                            ->get_item()
                            ->get_name(),

                    'quantity' =>
                        $requestitem->get_quantity(),

                    'unitamountminor' =>
                        $requestitem
                            ->get_unit_amount_minor(),

                    'totalamountminor' =>
                        $requestitem
                            ->get_total_amount_minor(),

                    'currency' =>
                        $requestitem->get_currency(),
                ];
            },
            $this->items
        );
    }

    /**
     * Returns the future fulfillment operations.
     */
    public function get_fulfillment_operations():
        array {
        return array_map(
            static fn(
                CommercePreparedPurchaseItem $item
            ): array => [
                'key' =>
                    $item->get_fulfillment_key(),

                'metadata' =>
                    $item->get_fulfillment_metadata(),
            ],
            $this->items
        );
    }
}