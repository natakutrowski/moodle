<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\readmodel;

defined('MOODLE_INTERNAL') || die();

/** Immutable product projection consumed by the public boutique. */
final class CommerceStorefrontProduct {
    /**
     * @param CommerceStorefrontPrice[] $prices
     * @param array<int, array<string, mixed>> $components
     */
    public function __construct(
        private readonly string $sku,
        private readonly string $name,
        private readonly string $shortdescription,
        private readonly string $description,
        private readonly string $type,
        private readonly array $prices,
        private readonly array $components,
        private readonly bool $quickpurchaseeligible,
        private readonly ?string $coverurl = null,
        private readonly array $legacyreferences = [],
        private readonly array $metadata = [],
        private readonly bool $featured = false,
        private readonly int $displayorder = 1000,
        private readonly array $badges = [],
        private readonly string $group = 'resources',
        private readonly array $trustitems = [],
        private readonly array $quickfacts = [],
        private readonly bool $owned = false,
        private readonly ?CommerceStorefrontUpgrade $upgrade = null,
        private readonly array $covers = [],
        private readonly ?int $id = null
    ) {
    }

    public function get_id(): ?int { return $this->id; }
    public function get_sku(): string { return $this->sku; }
    public function get_name(): string { return $this->name; }
    public function get_short_description(): string { return $this->shortdescription; }
    public function get_description(): string { return $this->description; }
    public function get_type(): string { return $this->type; }

    /** @return CommerceStorefrontPrice[] */
    public function get_prices(): array { return $this->prices; }

    /** @return array<int, array<string, mixed>> */
    public function get_components(): array { return $this->components; }

    public function is_quick_purchase_eligible(): bool { return $this->quickpurchaseeligible; }
    public function get_cover_url(string $context = 'storefront'): ?string {
        $context = strtolower(trim($context));
        return isset($this->covers[$context]) && trim((string)$this->covers[$context]) !== ''
            ? (string)$this->covers[$context]
            : $this->coverurl;
    }
    /** @return array<string,string|null> */
    public function get_covers(): array { return $this->covers; }

    /** @return array<int, array<string, mixed>> */
    public function get_legacy_references(): array { return $this->legacyreferences; }

    public function get_metadata(): array { return $this->metadata; }
    public function is_featured(): bool { return $this->featured; }
    public function get_display_order(): int { return $this->displayorder; }

    /** @return string[] */
    public function get_badges(): array { return $this->badges; }
    public function get_group(): string { return $this->group; }
    /** @return string[] */
    public function get_trust_items(): array { return $this->trustitems; }
    /** @return array<int,array{value:string,label:string}> */
    public function get_quick_facts(): array { return $this->quickfacts; }
    public function is_owned(): bool { return $this->owned; }
    public function get_upgrade(): ?CommerceStorefrontUpgrade { return $this->upgrade; }

    /** @return array<string, mixed> */
    public function to_array(): array {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'shortdescription' => $this->shortdescription,
            'description' => $this->description,
            'type' => $this->type,
            'prices' => array_map(
                static fn(CommerceStorefrontPrice $price): array => $price->to_array(),
                $this->prices
            ),
            'components' => $this->components,
            'quickpurchaseeligible' => $this->quickpurchaseeligible,
            'coverurl' => $this->get_cover_url(),
            'covers' => $this->covers,
            'legacyreferences' => $this->legacyreferences,
            'metadata' => $this->metadata,
            'featured' => $this->featured,
            'displayorder' => $this->displayorder,
            'badges' => $this->badges,
            'group' => $this->group,
            'trustitems' => $this->trustitems,
            'quickfacts' => $this->quickfacts,
            'owned' => $this->owned,
            'upgrade' => $this->upgrade?->to_array(),
        ];
    }
}
