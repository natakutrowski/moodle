<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\bundle\pricing;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\value\CommerceMoney;

/** Calculated Bundle price and its comparison values. */
final class CommerceBundlePriceQuote {
    public function __construct(
        private readonly string $sku,
        private readonly CommerceBundlePricingConfiguration $configuration,
        private readonly CommerceMoney $componenttotal,
        private readonly CommerceMoney $finalprice,
        private readonly array $lines,
        private readonly bool $componentcomparisoncomplete = true
    ) {
        if ($componenttotal->get_currency() !== $finalprice->get_currency()) {
            throw new \coding_exception('A Bundle price quote must use one currency.');
        }
    }

    public function get_sku(): string {
        return $this->sku;
    }

    public function get_configuration(): CommerceBundlePricingConfiguration {
        return $this->configuration;
    }

    public function get_component_total(): CommerceMoney {
        return $this->componenttotal;
    }

    public function get_final_price(): CommerceMoney {
        return $this->finalprice;
    }

    public function has_component_comparison(): bool {
        return $this->componentcomparisoncomplete;
    }

    public function get_savings_minor(): int {
        if (!$this->componentcomparisoncomplete) {
            return 0;
        }

        return max(0, $this->componenttotal->get_amount_minor() - $this->finalprice->get_amount_minor());
    }

    public function get_lines(): array {
        return $this->lines;
    }

    public function to_array(): array {
        return [
            'sku' => $this->sku,
            'configuration' => $this->configuration->to_array(),
            'currency' => $this->finalprice->get_currency(),
            'componenttotalminor' => $this->componenttotal->get_amount_minor(),
            'finalpriceminor' => $this->finalprice->get_amount_minor(),
            'savingsminor' => $this->get_savings_minor(),
            'componentcomparisoncomplete' => $this->componentcomparisoncomplete,
            'lines' => $this->lines,
        ];
    }
}
