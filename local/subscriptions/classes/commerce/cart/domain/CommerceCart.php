<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\cart\domain;

defined('MOODLE_INTERNAL') || die();

/** Immutable Commerce cart aggregate. */
final class CommerceCart {
    private readonly string $uuid;
    private readonly string $currency;
    /** @var array<string, CommerceCartItem> */
    private readonly array $items;

    /**
     * @param CommerceCartItem[] $items
     */
    public function __construct(
        string $uuid,
        private readonly int $customerid,
        string $currency,
        array $items = [],
        private readonly array $metadata = [],
        private readonly ?int $timecreated = null,
        private readonly ?int $timemodified = null
    ) {
        $uuid = strtolower(trim($uuid));
        $currency = strtoupper(trim($currency));

        if (!preg_match('/^[a-f0-9]{32}$/', $uuid)) {
            throw new \coding_exception('A Commerce cart UUID must contain 32 hexadecimal characters.');
        }

        if ($customerid < 0) {
            throw new \coding_exception('A Commerce cart customer identifier cannot be negative.');
        }

        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new \coding_exception('A Commerce cart currency must use ISO 4217 format.');
        }

        $indexed = [];
        foreach ($items as $item) {
            if (!$item instanceof CommerceCartItem) {
                throw new \coding_exception('Invalid Commerce cart item collection.');
            }
            $indexed[$item->get_key()] = $item;
        }

        $this->uuid = $uuid;
        $this->currency = $currency;
        $this->items = $indexed;
    }

    public function get_uuid(): string {
        return $this->uuid;
    }

    public function get_customer_id(): int {
        return $this->customerid;
    }

    public function get_currency(): string {
        return $this->currency;
    }

    /** @return CommerceCartItem[] */
    public function get_items(): array {
        return array_values($this->items);
    }

    public function get_item(string $key): ?CommerceCartItem {
        return $this->items[$key] ?? null;
    }

    public function is_empty(): bool {
        return $this->items === [];
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function with_metadata(array $metadata, ?int $timemodified = null): self {
        return new self(
            $this->uuid,
            $this->customerid,
            $this->currency,
            $this->get_items(),
            $metadata,
            $this->timecreated,
            $timemodified ?? time()
        );
    }

    public function get_time_created(): ?int {
        return $this->timecreated;
    }

    public function get_time_modified(): ?int {
        return $this->timemodified;
    }

    /** @param CommerceCartItem[] $items */
    public function with_items(array $items, ?int $timemodified = null): self {
        return new self(
            $this->uuid,
            $this->customerid,
            $this->currency,
            $items,
            $this->metadata,
            $this->timecreated,
            $timemodified ?? time()
        );
    }

    public function to_array(): array {
        return [
            'uuid' => $this->uuid,
            'customerid' => $this->customerid,
            'currency' => $this->currency,
            'items' => array_map(
                static fn(CommerceCartItem $item): array => $item->to_array(),
                $this->get_items()
            ),
            'metadata' => $this->metadata,
            'timecreated' => $this->timecreated,
            'timemodified' => $this->timemodified,
        ];
    }

    public static function from_array(array $data): self {
        $items = array_map(
            static fn(array $item): CommerceCartItem => CommerceCartItem::from_array($item),
            (array)($data['items'] ?? [])
        );

        return new self(
            (string)($data['uuid'] ?? ''),
            (int)($data['customerid'] ?? 0),
            (string)($data['currency'] ?? ''),
            $items,
            (array)($data['metadata'] ?? []),
            isset($data['timecreated']) ? (int)$data['timecreated'] : null,
            isset($data['timemodified']) ? (int)$data['timemodified'] : null
        );
    }
}
