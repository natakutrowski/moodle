<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\readmodel;

defined('MOODLE_INTERNAL') || die();

/** Immutable filters for the federated Commerce catalogue. */
final class CommerceCatalogListFilter {
    public function __construct(
        public readonly string $query = '',
        public readonly string $type = '',
        public readonly string $editorial = '',
        public readonly string $visibility = '',
        public readonly string $availability = '',
        public readonly string $technical = '',
        public readonly string $currency = '',
        public readonly string $origin = ''
    ) {
    }

    public function matches(CommerceCatalogProductSummary $product): bool {
        if ($this->query !== '') {
            $haystack = \core_text::strtolower(implode(' ', [
                $product->get_name(), $product->get_sku(), $product->get_description(),
            ]));
            if (!str_contains($haystack, \core_text::strtolower($this->query))) {
                return false;
            }
        }
        if ($this->type !== '' && $product->get_type() !== $this->type) { return false; }
        if ($this->editorial !== '' && $product->get_editorial_status() !== $this->editorial) { return false; }
        if ($this->visibility !== '' && $product->get_visibility() !== $this->visibility) { return false; }
        if ($this->availability !== '' && $product->get_availability() !== $this->availability) { return false; }
        if ($this->technical !== '' && $product->get_technical_state() !== $this->technical) { return false; }
        if ($this->origin !== '' && $product->get_origin() !== $this->origin) { return false; }
        if ($this->currency !== '') {
            foreach ($product->get_prices() as $price) {
                if ($price->get_currency() === $this->currency) { return true; }
            }
            return false;
        }
        return true;
    }
}
