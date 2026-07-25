<?php

namespace local_subscriptions\commerce\domain\value;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable commercial context captured for a Commerce purchase.
 *
 * It records the offer and pricing context used at purchase time without
 * depending on a future Catalog persistence model. It deliberately contains
 * no payment-provider data.
 */
final class CommercePurchaseSnapshot {

    public function __construct(
        string $offerreference,
        string $offerlabel,
        ?string $offerversion = null,
        private readonly array $pricingcontext = [],
        private readonly array $fulfillmentcontext = [],
        private readonly array $terms = [],
        private readonly array $metadata = []
    ) {
        $this->offerreference = self::require_non_empty(
            $offerreference,
            'offer reference'
        );
        $this->offerlabel = self::require_non_empty(
            $offerlabel,
            'offer label'
        );
        $this->offerversion = self::normalise_nullable($offerversion);
    }

    private readonly string $offerreference;
    private readonly string $offerlabel;
    private readonly ?string $offerversion;

    public function get_offer_reference(): string {
        return $this->offerreference;
    }

    public function get_offer_label(): string {
        return $this->offerlabel;
    }

    public function get_offer_version(): ?string {
        return $this->offerversion;
    }

    public function get_pricing_context(): array {
        return $this->pricingcontext;
    }

    public function get_pricing_context_value(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->pricingcontext[$key] ?? $default;
    }

    public function get_fulfillment_context(): array {
        return $this->fulfillmentcontext;
    }

    public function get_fulfillment_context_value(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->fulfillmentcontext[$key] ?? $default;
    }

    public function get_terms(): array {
        return $this->terms;
    }

    public function get_term(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->terms[$key] ?? $default;
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function get_metadata_value(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->metadata[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public function to_array(): array {
        return [
            'offerreference' => $this->offerreference,
            'offerlabel' => $this->offerlabel,
            'offerversion' => $this->offerversion,
            'pricingcontext' => $this->pricingcontext,
            'fulfillmentcontext' => $this->fulfillmentcontext,
            'terms' => $this->terms,
            'metadata' => $this->metadata,
        ];
    }

    private static function require_non_empty(
        string $value,
        string $label
    ): string {
        $normalised = trim($value);
        if ($normalised === '') {
            throw new \coding_exception(
                'A Commerce purchase snapshot ' . $label . ' cannot be empty.'
            );
        }

        return $normalised;
    }

    private static function normalise_nullable(?string $value): ?string {
        if ($value === null) {
            return null;
        }

        $normalised = trim($value);
        return $normalised === '' ? null : $normalised;
    }
}
