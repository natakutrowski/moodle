<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\readmodel;

defined('MOODLE_INTERNAL') || die();

/** Immutable entitlement Grant presentation for CRM consumers. */
final class CommerceCustomerGrant {
    public function __construct(
        public readonly int $id,
        public readonly string $reference,
        public readonly string $purchasereference,
        public readonly string $itemreference,
        public readonly string $productsku,
        public readonly string $type,
        public readonly string $resourcekey,
        public readonly string $status,
        public readonly ?int $beneficiaryuserid,
        public readonly string $beneficiaryemail,
        public readonly int $validfrom,
        public readonly ?int $validuntil,
        public readonly array $configuration = [],
        public readonly array $metadata = []
    ) {
        if ($id <= 0 || trim($reference) === '' || trim($type) === '') {
            throw new \coding_exception('Invalid Commerce customer Grant presentation.');
        }
    }

    public function is_active(?int $now = null): bool {
        $now ??= time();
        return $this->status === 'active'
            && $this->validfrom <= $now
            && ($this->validuntil === null || $this->validuntil >= $now);
    }

    /** @return array<string, mixed> */
    public function to_array(): array {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'purchasereference' => $this->purchasereference,
            'itemreference' => $this->itemreference,
            'productsku' => $this->productsku,
            'type' => $this->type,
            'resourcekey' => $this->resourcekey,
            'status' => $this->status,
            'beneficiaryuserid' => $this->beneficiaryuserid,
            'beneficiaryemail' => $this->beneficiaryemail,
            'validfrom' => $this->validfrom,
            'validuntil' => $this->validuntil,
            'configuration' => $this->configuration,
            'metadata' => $this->metadata,
            'active' => $this->is_active(),
        ];
    }
}
