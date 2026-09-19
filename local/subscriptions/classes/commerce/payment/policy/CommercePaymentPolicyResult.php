<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\policy;

use local_subscriptions\commerce\payment\availability\CommercePaymentMethodAvailability;

defined('MOODLE_INTERNAL') || die();

/**
 * Provider-independent payment UX policy result.
 *
 * Availability remains authoritative. Policy may reorder/recommend available
 * methods, but must never invent or silently enable a method.
 */
final class CommercePaymentPolicyResult {
    /**
     * @param CommercePaymentMethodAvailability[] $orderedmethods
     */
    public function __construct(
        private readonly string $country,
        private readonly string $currency,
        private readonly array $orderedmethods,
        private readonly ?string $recommendedmethod,
        private readonly ?string $advicekey = null
    ) {
        foreach ($orderedmethods as $method) {
            if (!$method instanceof CommercePaymentMethodAvailability) {
                throw new \coding_exception(
                    'Payment policy contains an invalid availability item.'
                );
            }
            if (!$method->is_available()) {
                throw new \coding_exception(
                    'Payment policy cannot expose an unavailable method.'
                );
            }
        }
    }

    public function get_country(): string {
        return strtoupper(trim($this->country));
    }

    public function get_currency(): string {
        return strtoupper(trim($this->currency));
    }

    /**
     * @return CommercePaymentMethodAvailability[]
     */
    public function get_ordered_methods(): array {
        return $this->orderedmethods;
    }

    public function get_recommended_method(): ?string {
        return $this->recommendedmethod;
    }

    public function get_advice_key(): ?string {
        return $this->advicekey;
    }

    public function has_advice(): bool {
        return $this->advicekey !== null
            && trim($this->advicekey) !== '';
    }
}
