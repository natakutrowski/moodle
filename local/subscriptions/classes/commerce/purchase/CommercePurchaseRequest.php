<?php

namespace local_subscriptions\commerce\purchase;

defined('MOODLE_INTERNAL') || die();

/**
 * Provider-independent request to purchase one or more Commerce items.
 *
 * This object contains no Stripe, Alfa or PayPal implementation detail.
 */
final class CommercePurchaseRequest {

    /**
     * @param CommercePurchaseRequestItem[] $items
     */
    public function __construct(
        private readonly string $reference,
        private readonly CommerceCustomer $customer,
        private readonly array $items,
        private readonly string $status =
            CommercePurchaseRequestStatus::DRAFT,
        private readonly ?string $preferredprovider = null,
        private readonly ?string $returnurl = null,
        private readonly ?string $cancelurl = null,
        private readonly array $metadata = [],
        private readonly ?int $createdat = null
    ) {
        if (trim($reference) === '') {
            throw new \coding_exception(
                'A Commerce purchase request reference cannot be empty.'
            );
        }

        if ($items === []) {
            throw new \coding_exception(
                'A Commerce purchase request must contain at least one item.'
            );
        }

        foreach ($items as $item) {
            if (!$item instanceof CommercePurchaseRequestItem) {
                throw new \coding_exception(
                    'A Commerce purchase request contains an invalid item.'
                );
            }
        }

        CommercePurchaseRequestStatus::normalise(
            $status
        );

        $this->validate_currencies($items);
    }

    public function get_reference(): string {
        return trim($this->reference);
    }

    public function get_customer(): CommerceCustomer {
        return $this->customer;
    }

    /**
     * @return CommercePurchaseRequestItem[]
     */
    public function get_items(): array {
        return $this->items;
    }

    public function get_status(): string {
        return CommercePurchaseRequestStatus::normalise(
            $this->status
        );
    }

    public function get_preferred_provider(): ?string {
        if ($this->preferredprovider === null) {
            return null;
        }

        $provider = strtolower(
            trim($this->preferredprovider)
        );

        return $provider !== ''
            ? $provider
            : null;
    }

    public function get_return_url(): ?string {
        return $this->normalise_nullable_string(
            $this->returnurl
        );
    }

    public function get_cancel_url(): ?string {
        return $this->normalise_nullable_string(
            $this->cancelurl
        );
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function get_metadata_value(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->metadata[$key]
            ?? $default;
    }

    public function get_created_at(): ?int {
        return $this->createdat;
    }

    public function get_currency(): string {
        return $this->items[0]->get_currency();
    }

    public function get_total_amount_minor(): int {
        return array_sum(
            array_map(
                static fn(
                    CommercePurchaseRequestItem $item
                ): int => $item->get_total_amount_minor(),
                $this->items
            )
        );
    }

    public function is_free(): bool {
        return $this->get_total_amount_minor() === 0;
    }

    public function contains_multiple_items(): bool {
        return count($this->items) > 1;
    }

    public function is_terminal(): bool {
        return CommercePurchaseRequestStatus::is_terminal(
            $this->status
        );
    }

    /**
     * Returns a new immutable request with another workflow status.
     */
    public function with_status(
        string $status
    ): self {
        return new self(
            $this->reference,
            $this->customer,
            $this->items,
            CommercePurchaseRequestStatus::normalise(
                $status
            ),
            $this->preferredprovider,
            $this->returnurl,
            $this->cancelurl,
            $this->metadata,
            $this->createdat
        );
    }

    /**
     * @param CommercePurchaseRequestItem[] $items
     */
    private function validate_currencies(
        array $items
    ): void {
        $currencies = [];

        foreach ($items as $item) {
            $currencies[] =
                $item->get_currency();
        }

        $currencies = array_values(
            array_unique(
                $currencies
            )
        );

        if (count($currencies) !== 1) {
            throw new \coding_exception(
                'A Commerce purchase request cannot mix currencies.'
            );
        }
    }

    private function normalise_nullable_string(
        ?string $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value !== ''
            ? $value
            : null;
    }
}