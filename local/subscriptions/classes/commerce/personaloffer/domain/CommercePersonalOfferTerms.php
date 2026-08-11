<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\domain;

defined('MOODLE_INTERNAL') || die();

/** Immutable, versioned commercial terms attached to one Personal Offer. */
final class CommercePersonalOfferTerms {
    public const VERSION = 1;
    public const STRATEGY_FIXED_PRICE = 'fixed_price';
    public const STRATEGY_FIXED_DISCOUNT = 'fixed_discount';
    public const STRATEGY_PERCENTAGE_DISCOUNT = 'percentage_discount';

    private readonly array $data;

    public function __construct(array $data) {
        $pricing = $data['pricing'] ?? null;
        if (!is_array($pricing)) {
            throw new \coding_exception('Personal Offer terms require a pricing definition.');
        }

        $strategy = (string)($pricing['strategy'] ?? '');
        if (!in_array($strategy, [
            self::STRATEGY_FIXED_PRICE,
            self::STRATEGY_FIXED_DISCOUNT,
            self::STRATEGY_PERCENTAGE_DISCOUNT,
        ], true)) {
            throw new \coding_exception('Unsupported Personal Offer pricing strategy.');
        }

        if ($strategy === self::STRATEGY_PERCENTAGE_DISCOUNT) {
            $basispoints = $pricing['basispoints'] ?? null;
            if (!is_int($basispoints) || $basispoints <= 0 || $basispoints > 10000) {
                throw new \coding_exception('Personal Offer percentage discount must be between 1 and 10000 basis points.');
            }
        } else {
            $amounts = $pricing['amounts'] ?? null;
            if (!is_array($amounts) || $amounts === []) {
                throw new \coding_exception('Personal Offer fixed pricing requires at least one currency amount.');
            }
            foreach ($amounts as $currency => $minor) {
                if (!is_string($currency) || !preg_match('/^[A-Z]{3}$/', strtoupper($currency))) {
                    throw new \coding_exception('Personal Offer currency codes must use ISO-style three-letter identifiers.');
                }
                if (!is_int($minor) || $minor < 0) {
                    throw new \coding_exception('Personal Offer currency amounts must be non-negative minor-unit integers.');
                }
            }
        }

        $this->data = $data;
    }

    public static function fixed_price(array $amounts): self {
        return new self(['pricing' => ['strategy' => self::STRATEGY_FIXED_PRICE, 'amounts' => self::normalise_amounts($amounts)]]);
    }

    public static function fixed_discount(array $amounts): self {
        return new self(['pricing' => ['strategy' => self::STRATEGY_FIXED_DISCOUNT, 'amounts' => self::normalise_amounts($amounts)]]);
    }

    public static function percentage_discount(int $basispoints): self {
        return new self(['pricing' => ['strategy' => self::STRATEGY_PERCENTAGE_DISCOUNT, 'basispoints' => $basispoints]]);
    }

    public function get_version(): int { return self::VERSION; }
    public function get_pricing_strategy(): string { return (string)$this->data['pricing']['strategy']; }
    public function get_data(): array { return $this->data; }

    public function get_amount_for_currency(string $currency): ?int {
        $currency = strtoupper(trim($currency));
        $amounts = $this->data['pricing']['amounts'] ?? [];
        return array_key_exists($currency, $amounts) ? (int)$amounts[$currency] : null;
    }

    public function get_percentage_basispoints(): ?int {
        return $this->get_pricing_strategy() === self::STRATEGY_PERCENTAGE_DISCOUNT
            ? (int)$this->data['pricing']['basispoints']
            : null;
    }

    private static function normalise_amounts(array $amounts): array {
        $normalised = [];
        foreach ($amounts as $currency => $minor) {
            $normalised[strtoupper(trim((string)$currency))] = $minor;
        }
        ksort($normalised);
        return $normalised;
    }
}
