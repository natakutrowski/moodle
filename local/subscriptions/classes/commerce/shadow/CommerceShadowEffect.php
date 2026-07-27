<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\shadow;

defined('MOODLE_INTERNAL') || die();

/** Canonical observable fulfillment effect used by G3 comparisons. */
final class CommerceShadowEffect {
    public function __construct(
        private readonly string $grantreference,
        private readonly string $type,
        private readonly string $resourcekey,
        private readonly ?int $beneficiaryuserid,
        private readonly string $beneficiaryemail,
        private readonly array $attributes = []
    ) {
        if (trim($this->grantreference) === '' || trim($this->type) === '' || trim($this->resourcekey) === '') {
            throw new \coding_exception('Invalid Commerce Shadow effect identity.');
        }
    }

    public function get_grant_reference(): string { return $this->grantreference; }
    public function get_type(): string { return $this->type; }
    public function get_resource_key(): string { return $this->resourcekey; }
    public function get_beneficiary_user_id(): ?int { return $this->beneficiaryuserid; }
    public function get_beneficiary_email(): string { return $this->beneficiaryemail; }
    public function get_attributes(): array { return $this->attributes; }

    public function identity_key(): string {
        return implode('|', [
            $this->type,
            $this->resourcekey,
            (string)($this->beneficiaryuserid ?? 0),
            strtolower($this->beneficiaryemail),
        ]);
    }

    public function canonical_array(): array {
        $attributes = $this->normalise($this->attributes);
        return [
            'type' => $this->type,
            'resourcekey' => $this->resourcekey,
            'beneficiaryuserid' => $this->beneficiaryuserid,
            'beneficiaryemail' => strtolower($this->beneficiaryemail),
            'attributes' => $attributes,
        ];
    }

    private function normalise(array $value): array {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->normalise($item);
            }
        }
        if (array_is_list($value)) {
            sort($value);
        } else {
            ksort($value);
        }
        return $value;
    }
}
