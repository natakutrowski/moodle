<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\entitlement\domain;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable customer entitlement planned from a purchased catalogue product.
 */
final class CommerceEntitlementGrant {
    private readonly string $reference;
    private readonly string $purchasereference;
    private readonly string $itemreference;
    private readonly string $productsku;
    private readonly string $type;
    private readonly string $resourcekey;
    private readonly string $beneficiaryemail;

    public function __construct(
        string $reference,
        string $purchasereference,
        string $itemreference,
        string $productsku,
        string $type,
        string $resourcekey,
        private readonly int $quantity,
        private readonly ?int $beneficiaryuserid,
        string $beneficiaryemail,
        private readonly int $validfrom,
        private readonly ?int $validuntil = null,
        private readonly array $configuration = [],
        private readonly array $metadata = []
    ) {
        $reference = trim($reference);
        $purchasereference = trim($purchasereference);
        $itemreference = trim($itemreference);
        $productsku = strtoupper(trim($productsku));
        $type = strtolower(trim($type));
        $resourcekey = trim($resourcekey);
        $beneficiaryemail = trim(\core_text::strtolower($beneficiaryemail));

        if ($reference === '') {
            throw new \coding_exception('A Commerce entitlement grant reference cannot be empty.');
        }

        if ($purchasereference === '' || $itemreference === '') {
            throw new \coding_exception('A Commerce entitlement grant requires purchase and item references.');
        }

        if ($productsku === '') {
            throw new \coding_exception('A Commerce entitlement grant requires a product SKU.');
        }

        if (!preg_match('/^[a-z][a-z0-9_]{1,63}$/', $type)) {
            throw new \coding_exception('Invalid Commerce entitlement grant type.');
        }

        if ($resourcekey === '') {
            throw new \coding_exception('A Commerce entitlement grant resource key cannot be empty.');
        }

        if ($quantity <= 0) {
            throw new \coding_exception('A Commerce entitlement grant quantity must be positive.');
        }

        if ($beneficiaryuserid !== null && $beneficiaryuserid <= 0) {
            throw new \coding_exception('A Commerce entitlement beneficiary user identifier must be positive.');
        }

        if (!validate_email($beneficiaryemail)) {
            throw new \coding_exception('A Commerce entitlement grant requires a valid beneficiary email.');
        }

        if ($validfrom <= 0) {
            throw new \coding_exception('A Commerce entitlement grant start timestamp must be positive.');
        }

        if ($validuntil !== null && $validuntil <= $validfrom) {
            throw new \coding_exception('A Commerce entitlement grant end timestamp must be later than its start.');
        }

        $this->reference = $reference;
        $this->purchasereference = $purchasereference;
        $this->itemreference = $itemreference;
        $this->productsku = $productsku;
        $this->type = $type;
        $this->resourcekey = $resourcekey;
        $this->beneficiaryemail = $beneficiaryemail;
    }

    public function get_reference(): string {
        return $this->reference;
    }

    public function get_purchase_reference(): string {
        return $this->purchasereference;
    }

    public function get_item_reference(): string {
        return $this->itemreference;
    }

    public function get_product_sku(): string {
        return $this->productsku;
    }

    public function get_type(): string {
        return $this->type;
    }

    public function get_resource_key(): string {
        return $this->resourcekey;
    }

    public function get_quantity(): int {
        return $this->quantity;
    }

    public function get_beneficiary_user_id(): ?int {
        return $this->beneficiaryuserid;
    }

    public function get_beneficiary_email(): string {
        return $this->beneficiaryemail;
    }

    public function get_valid_from(): int {
        return $this->validfrom;
    }

    public function get_valid_until(): ?int {
        return $this->validuntil;
    }

    public function is_lifetime(): bool {
        return $this->validuntil === null;
    }

    public function get_configuration(): array {
        return $this->configuration;
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function get_idempotency_key(): string {
        return implode(':', [
            $this->purchasereference,
            $this->itemreference,
            $this->type,
            hash('sha256', $this->resourcekey),
        ]);
    }

    public function to_array(): array {
        return [
            'reference' => $this->reference,
            'purchasereference' => $this->purchasereference,
            'itemreference' => $this->itemreference,
            'productsku' => $this->productsku,
            'type' => $this->type,
            'resourcekey' => $this->resourcekey,
            'quantity' => $this->quantity,
            'beneficiaryuserid' => $this->beneficiaryuserid,
            'beneficiaryemail' => $this->beneficiaryemail,
            'validfrom' => $this->validfrom,
            'validuntil' => $this->validuntil,
            'configuration' => $this->configuration,
            'metadata' => $this->metadata,
        ];
    }
}
