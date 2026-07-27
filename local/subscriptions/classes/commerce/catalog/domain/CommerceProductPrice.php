<?php

namespace local_subscriptions\commerce\catalog\domain;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\value\CommerceMoney;

/**
 * Immutable price offered for one Native Commerce product.
 */
final class CommerceProductPrice {

    private readonly string $productsku;
    private readonly ?string $provider;
    private readonly ?string $providerpriceid;

    public function __construct(
        string $productsku,
        private readonly CommerceMoney $money,
        private readonly bool $active = true,
        ?string $provider = null,
        ?string $providerpriceid = null,
        private readonly array $metadata = [],
        private readonly ?int $id = null
    ) {
        $productsku = strtoupper(trim($productsku));
        if ($productsku === '') {
            throw new \coding_exception('A Commerce product price requires a product SKU.');
        }

        if ($id !== null && $id <= 0) {
            throw new \coding_exception('A Commerce product price identifier must be positive.');
        }

        if ($providerpriceid !== null && trim($providerpriceid) !== '' && trim((string)$provider) === '') {
            throw new \coding_exception('A provider price identifier requires a provider name.');
        }

        $this->productsku = $productsku;
        $this->provider = self::normalise_optional_identifier($provider);
        $this->providerpriceid = self::normalise_optional_value($providerpriceid);
    }

    public function get_id(): ?int {
        return $this->id;
    }

    public function get_product_sku(): string {
        return $this->productsku;
    }

    public function get_money(): CommerceMoney {
        return $this->money;
    }

    public function get_amount_minor(): int {
        return $this->money->get_amount_minor();
    }

    public function get_currency(): string {
        return $this->money->get_currency();
    }

    public function is_active(): bool {
        return $this->active;
    }

    public function get_provider(): ?string {
        return $this->provider;
    }

    public function get_provider_price_id(): ?string {
        return $this->providerpriceid;
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function to_array(): array {
        return [
            'id' => $this->id,
            'productsku' => $this->productsku,
            'money' => $this->money->to_array(),
            'active' => $this->active,
            'provider' => $this->provider,
            'providerpriceid' => $this->providerpriceid,
            'metadata' => $this->metadata,
        ];
    }

    private static function normalise_optional_identifier(?string $value): ?string {
        $value = self::normalise_optional_value($value);
        return $value === null ? null : strtolower($value);
    }

    private static function normalise_optional_value(?string $value): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        return $value === '' ? null : $value;
    }
}
