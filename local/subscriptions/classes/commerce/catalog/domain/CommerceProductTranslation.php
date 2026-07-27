<?php

namespace local_subscriptions\commerce\catalog\domain;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable localised editorial content for a Commerce product.
 */
final class CommerceProductTranslation {

    private readonly string $productsku;
    private readonly string $language;
    private readonly string $name;
    private readonly string $shortdescription;
    private readonly string $description;

    public function __construct(
        string $productsku,
        string $language,
        string $name,
        string $shortdescription = '',
        string $description = '',
        private readonly array $metadata = [],
        private readonly ?int $id = null
    ) {
        $productsku = strtoupper(trim($productsku));
        $language = strtolower(trim($language));

        if ($productsku === '') {
            throw new \coding_exception('A Commerce product translation requires a product SKU.');
        }

        if (!preg_match('/^[a-z]{2,3}(?:_[a-z0-9]{2,8})?$/', $language)) {
            throw new \coding_exception('A Commerce product translation requires a valid Moodle language code.');
        }

        if (trim($name) === '') {
            throw new \coding_exception('A Commerce product translation name cannot be empty.');
        }

        if ($id !== null && $id <= 0) {
            throw new \coding_exception('A Commerce product translation identifier must be positive.');
        }

        $this->productsku = $productsku;
        $this->language = $language;
        $this->name = trim($name);
        $this->shortdescription = trim($shortdescription);
        $this->description = trim($description);
    }

    public function get_id(): ?int {
        return $this->id;
    }

    public function get_product_sku(): string {
        return $this->productsku;
    }

    public function get_language(): string {
        return $this->language;
    }

    public function get_name(): string {
        return $this->name;
    }

    public function get_short_description(): string {
        return $this->shortdescription;
    }

    public function get_description(): string {
        return $this->description;
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function to_array(): array {
        return [
            'id' => $this->id,
            'productsku' => $this->productsku,
            'language' => $this->language,
            'name' => $this->name,
            'shortdescription' => $this->shortdescription,
            'description' => $this->description,
            'metadata' => $this->metadata,
        ];
    }
}
