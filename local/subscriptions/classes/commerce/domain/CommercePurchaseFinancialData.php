<?php

namespace local_subscriptions\commerce\domain;

defined('MOODLE_INTERNAL') || die();

/**
 * Provider-independent financial representation of a Commerce purchase.
 *
 * Every amount is expressed in the smallest currency unit.
 *
 * Examples:
 *
 * - EUR 120.00 => 12000
 * - RUB 2500.00 => 250000
 */
final class CommercePurchaseFinancialData {

    /**
     * @param string $currency ISO currency code.
     * @param int $subtotalminor Amount before discounts, taxes and fees.
     * @param int $discountminor Discount amount.
     * @param int $taxminor Tax amount.
     * @param int $feesminor Additional fees.
     * @param int $totalminor Final amount charged.
     * @param int $capturedminor Amount successfully captured.
     * @param int $refundedminor Amount refunded.
     */
    public function __construct(
        private readonly string $currency,
        private readonly int $subtotalminor,
        private readonly int $discountminor,
        private readonly int $taxminor,
        private readonly int $feesminor,
        private readonly int $totalminor,
        private readonly int $capturedminor,
        private readonly int $refundedminor = 0
    ) {
        $this->validate_currency(
            $currency
        );

        $this->validate_amount(
            'subtotal',
            $subtotalminor
        );

        $this->validate_amount(
            'discount',
            $discountminor
        );

        $this->validate_amount(
            'tax',
            $taxminor
        );

        $this->validate_amount(
            'fees',
            $feesminor
        );

        $this->validate_amount(
            'total',
            $totalminor
        );

        $this->validate_amount(
            'captured',
            $capturedminor
        );

        $this->validate_amount(
            'refunded',
            $refundedminor
        );
    }

    /**
     * Build financial data from an existing Commerce purchase.
     *
     * @param CommercePurchase $purchase Commerce purchase.
     * @return self
     */
    public static function from_purchase(
        CommercePurchase $purchase
    ): self {
        $payment =
            $purchase->get_payment();

        $totalminor =
            $payment->get_amount_minor();

        $discountminor =
            self::resolve_metadata_amount_minor(
                $purchase,
                'discount_minor',
                'locked_discount_amount'
            );

        $taxminor =
            self::resolve_metadata_amount_minor(
                $purchase,
                'tax_minor',
                'tax_amount'
            );

        $feesminor =
            self::resolve_metadata_amount_minor(
                $purchase,
                'fees_minor',
                'fees_amount'
            );

        $refundedminor =
            self::resolve_metadata_amount_minor(
                $purchase,
                'refunded_minor',
                'refunded_amount'
            );

        $subtotalminor =
            $totalminor
            + $discountminor
            - $taxminor
            - $feesminor;

        if ($subtotalminor < 0) {
            $subtotalminor =
                $totalminor;
        }

        $capturedminor =
            $payment->is_successful()
                ? $totalminor
                : 0;

        $capturedmetadata =
            $purchase->get_metadata_value(
                'captured_minor'
            );

        if (
            is_int($capturedmetadata)
            && $capturedmetadata >= 0
        ) {
            $capturedminor =
                $capturedmetadata;
        }

        return new self(
            $payment->get_currency(),
            $subtotalminor,
            $discountminor,
            $taxminor,
            $feesminor,
            $totalminor,
            $capturedminor,
            $refundedminor
        );
    }

    /**
     * Return the ISO currency code.
     *
     * @return string
     */
    public function get_currency(): string {
        return strtoupper(
            $this->currency
        );
    }

    /**
     * Return the subtotal amount.
     *
     * @return int
     */
    public function get_subtotal_minor(): int {
        return $this->subtotalminor;
    }

    /**
     * Return the discount amount.
     *
     * @return int
     */
    public function get_discount_minor(): int {
        return $this->discountminor;
    }

    /**
     * Return the tax amount.
     *
     * @return int
     */
    public function get_tax_minor(): int {
        return $this->taxminor;
    }

    /**
     * Return the fees amount.
     *
     * @return int
     */
    public function get_fees_minor(): int {
        return $this->feesminor;
    }

    /**
     * Return the final total amount.
     *
     * @return int
     */
    public function get_total_minor(): int {
        return $this->totalminor;
    }

    /**
     * Return the captured amount.
     *
     * @return int
     */
    public function get_captured_minor(): int {
        return $this->capturedminor;
    }

    /**
     * Return the refunded amount.
     *
     * @return int
     */
    public function get_refunded_minor(): int {
        return $this->refundedminor;
    }

    /**
     * Whether all financial amounts are internally consistent.
     *
     * @return bool
     */
    public function is_consistent(): bool {
        $calculatedtotal =
            $this->subtotalminor
            - $this->discountminor
            + $this->taxminor
            + $this->feesminor;

        if ($calculatedtotal !== $this->totalminor) {
            return false;
        }

        if ($this->capturedminor > $this->totalminor) {
            return false;
        }

        if ($this->refundedminor > $this->capturedminor) {
            return false;
        }

        return true;
    }

    /**
     * Whether the purchase has been fully captured.
     *
     * @return bool
     */
    public function is_fully_captured(): bool {
        return $this->totalminor > 0
            && $this->capturedminor
                === $this->totalminor;
    }

    /**
     * Whether the purchase has been partially or fully refunded.
     *
     * @return bool
     */
    public function has_refund(): bool {
        return $this->refundedminor > 0;
    }

    /**
     * Whether the purchase has been fully refunded.
     *
     * @return bool
     */
    public function is_fully_refunded(): bool {
        return $this->capturedminor > 0
            && $this->refundedminor
                === $this->capturedminor;
    }

    /**
     * Return the non-refunded captured amount.
     *
     * @return int
     */
    public function get_net_captured_minor(): int {
        return max(
            0,
            $this->capturedminor
                - $this->refundedminor
        );
    }

    /**
     * Export the financial data.
     *
     * @return array
     */
    public function to_array(): array {
        return [
            'currency' =>
                $this->get_currency(),

            'subtotal_minor' =>
                $this->get_subtotal_minor(),

            'discount_minor' =>
                $this->get_discount_minor(),

            'tax_minor' =>
                $this->get_tax_minor(),

            'fees_minor' =>
                $this->get_fees_minor(),

            'total_minor' =>
                $this->get_total_minor(),

            'captured_minor' =>
                $this->get_captured_minor(),

            'refunded_minor' =>
                $this->get_refunded_minor(),

            'net_captured_minor' =>
                $this->get_net_captured_minor(),

            'consistent' =>
                $this->is_consistent(),
        ];
    }

    /**
     * Resolve an amount from Commerce metadata.
     *
     * The minor-unit key is preferred. A Legacy major-unit key can be used as
     * a fallback and is converted using two decimal places.
     *
     * @param CommercePurchase $purchase Commerce purchase.
     * @param string $minorkey Minor-unit metadata key.
     * @param string $majorkey Major-unit metadata key.
     * @return int
     */
    private static function resolve_metadata_amount_minor(
        CommercePurchase $purchase,
        string $minorkey,
        string $majorkey
    ): int {
        $minorvalue =
            $purchase->get_metadata_value(
                $minorkey
            );

        if (
            is_int($minorvalue)
            && $minorvalue >= 0
        ) {
            return $minorvalue;
        }

        if (
            is_string($minorvalue)
            && ctype_digit($minorvalue)
        ) {
            return (int)$minorvalue;
        }

        $majorvalue =
            $purchase->get_metadata_value(
                $majorkey
            );

        if (
            is_int($majorvalue)
            || is_float($majorvalue)
            || (
                is_string($majorvalue)
                && is_numeric($majorvalue)
            )
        ) {
            return max(
                0,
                (int)round(
                    (float)$majorvalue * 100
                )
            );
        }

        return 0;
    }

    /**
     * Validate a currency code.
     *
     * @param string $currency Currency.
     * @return void
     */
    private function validate_currency(
        string $currency
    ): void {
        if (
            !preg_match(
                '/^[A-Z]{3}$/',
                strtoupper(
                    trim($currency)
                )
            )
        ) {
            throw new \coding_exception(
                'A Commerce financial currency must be a three-letter ISO code.'
            );
        }
    }

    /**
     * Validate a financial amount.
     *
     * @param string $name Amount name.
     * @param int $value Amount.
     * @return void
     */
    private function validate_amount(
        string $name,
        int $value
    ): void {
        if ($value < 0) {
            throw new \coding_exception(
                sprintf(
                    'The Commerce financial amount "%s" cannot be negative.',
                    $name
                )
            );
        }
    }
}