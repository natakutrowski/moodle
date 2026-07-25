<?php

namespace local_subscriptions\commerce\domain\value;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable provider-independent monetary value.
 *
 * Amounts are always stored in the smallest currency unit. Decimal strings may
 * be converted at system boundaries, but all domain calculations remain based
 * on integers and never use floating-point arithmetic.
 */
final class CommerceMoney {

    /**
     * @param int $amountminor Amount expressed in the smallest currency unit.
     * @param string $currency Three-letter ISO 4217 currency code.
     */
    public function __construct(
        private readonly int $amountminor,
        private readonly string $currency
    ) {
        if ($amountminor < 0) {
            throw new \coding_exception(
                'A Commerce money amount cannot be negative.'
            );
        }

        $currency = self::normalise_currency($currency);

        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new \coding_exception(
                'A Commerce money currency must use ISO 4217 format.'
            );
        }
    }

    /**
     * Create a monetary value from its smallest currency unit.
     *
     * @param int $amountminor Amount in minor units.
     * @param string $currency Currency code.
     * @return self
     */
    public static function from_minor(
        int $amountminor,
        string $currency
    ): self {
        return new self(
            $amountminor,
            $currency
        );
    }

    /**
     * Create a zero monetary value.
     *
     * @param string $currency Currency code.
     * @return self
     */
    public static function zero(string $currency): self {
        return new self(
            0,
            $currency
        );
    }

    /**
     * Create a monetary value from an exact decimal major-unit amount.
     *
     * The amount must be provided as an integer or decimal string. Floats are
     * deliberately rejected by the method signature so that binary floating-
     * point approximations never enter the Commerce domain.
     *
     * Examples with an exponent of 2:
     *
     * - "149.90" => 14990
     * - "149.9" => 14990
     * - 149 => 14900
     *
     * @param int|string $amountmajor Exact major-unit amount.
     * @param string $currency Currency code.
     * @param int $minorunitexponent Number of decimal minor-unit digits.
     * @return self
     */
    public static function from_major(
        int|string $amountmajor,
        string $currency,
        int $minorunitexponent = 2
    ): self {
        self::validate_minor_unit_exponent(
            $minorunitexponent
        );

        $amount = trim(
            (string)$amountmajor
        );

        if (!preg_match('/^(?:0|[1-9][0-9]*)(?:\.[0-9]+)?$/', $amount)) {
            throw new \coding_exception(
                'A Commerce money major amount must be a non-negative decimal value.'
            );
        }

        [$whole, $fraction] = array_pad(
            explode('.', $amount, 2),
            2,
            ''
        );

        if (strlen($fraction) > $minorunitexponent) {
            throw new \coding_exception(
                'A Commerce money major amount has too many decimal places.'
            );
        }

        $fraction = str_pad(
            $fraction,
            $minorunitexponent,
            '0'
        );

        $factor = self::power_of_ten(
            $minorunitexponent
        );

        $wholeamount = self::multiply_integers_safely(
            self::decimal_digits_to_int($whole),
            $factor
        );

        $fractionamount = $fraction === ''
            ? 0
            : self::decimal_digits_to_int($fraction);

        $amountminor = self::add_integers_safely(
            $wholeamount,
            $fractionamount
        );

        return new self(
            $amountminor,
            $currency
        );
    }

    /**
     * Return the amount in the smallest currency unit.
     *
     * @return int
     */
    public function get_amount_minor(): int {
        return $this->amountminor;
    }

    /**
     * Return the normalised currency code.
     *
     * @return string
     */
    public function get_currency(): string {
        return self::normalise_currency(
            $this->currency
        );
    }

    /**
     * Return an exact decimal representation in major currency units.
     *
     * @param int $minorunitexponent Number of decimal minor-unit digits.
     * @return string
     */
    public function get_amount_major(
        int $minorunitexponent = 2
    ): string {
        self::validate_minor_unit_exponent(
            $minorunitexponent
        );

        if ($minorunitexponent === 0) {
            return (string)$this->amountminor;
        }

        $factor = self::power_of_ten(
            $minorunitexponent
        );

        $whole = intdiv(
            $this->amountminor,
            $factor
        );

        $fraction = $this->amountminor % $factor;

        return $whole
            . '.'
            . str_pad(
                (string)$fraction,
                $minorunitexponent,
                '0',
                STR_PAD_LEFT
            );
    }

    /**
     * Add another monetary value expressed in the same currency.
     *
     * @param self $other Other value.
     * @return self
     */
    public function add(self $other): self {
        $this->require_same_currency(
            $other
        );

        return new self(
            self::add_integers_safely(
                $this->amountminor,
                $other->amountminor
            ),
            $this->currency
        );
    }

    /**
     * Subtract another monetary value expressed in the same currency.
     *
     * @param self $other Other value.
     * @return self
     */
    public function subtract(self $other): self {
        $this->require_same_currency(
            $other
        );

        if ($other->amountminor > $this->amountminor) {
            throw new \coding_exception(
                'A Commerce money subtraction cannot produce a negative amount.'
            );
        }

        return new self(
            $this->amountminor - $other->amountminor,
            $this->currency
        );
    }

    /**
     * Multiply the monetary value by a non-negative integer quantity.
     *
     * @param int $multiplier Multiplier.
     * @return self
     */
    public function multiply(int $multiplier): self {
        if ($multiplier < 0) {
            throw new \coding_exception(
                'A Commerce money multiplier cannot be negative.'
            );
        }

        return new self(
            self::multiply_integers_safely(
                $this->amountminor,
                $multiplier
            ),
            $this->currency
        );
    }

    /**
     * Compare two monetary values expressed in the same currency.
     *
     * @param self $other Other value.
     * @return int -1 when lower, 0 when equal, 1 when greater.
     */
    public function compare(self $other): int {
        $this->require_same_currency(
            $other
        );

        return $this->amountminor <=> $other->amountminor;
    }

    /**
     * Whether both the amount and currency are equal.
     *
     * @param self $other Other value.
     * @return bool
     */
    public function equals(self $other): bool {
        return $this->amountminor === $other->amountminor
            && $this->get_currency() === $other->get_currency();
    }

    /**
     * Whether the amount is zero.
     *
     * @return bool
     */
    public function is_zero(): bool {
        return $this->amountminor === 0;
    }

    /**
     * Whether this value is lower than another value.
     *
     * @param self $other Other value.
     * @return bool
     */
    public function is_less_than(self $other): bool {
        return $this->compare($other) < 0;
    }

    /**
     * Whether this value is greater than another value.
     *
     * @param self $other Other value.
     * @return bool
     */
    public function is_greater_than(self $other): bool {
        return $this->compare($other) > 0;
    }

    /**
     * Return a serialisable domain representation.
     *
     * @return array{amountminor: int, currency: string}
     */
    public function to_array(): array {
        return [
            'amountminor' => $this->amountminor,
            'currency' => $this->get_currency(),
        ];
    }

    /**
     * Ensure that an operation uses the same currency on both sides.
     *
     * @param self $other Other value.
     * @return void
     */
    private function require_same_currency(self $other): void {
        if ($this->get_currency() !== $other->get_currency()) {
            throw new \coding_exception(
                'Commerce money operations require matching currencies.'
            );
        }
    }

    /**
     * Normalise a currency code.
     *
     * @param string $currency Currency code.
     * @return string
     */
    private static function normalise_currency(string $currency): string {
        return strtoupper(
            trim($currency)
        );
    }

    /**
     * Validate the exponent used to represent minor units.
     *
     * @param int $minorunitexponent Exponent.
     * @return void
     */
    private static function validate_minor_unit_exponent(
        int $minorunitexponent
    ): void {
        if ($minorunitexponent < 0 || $minorunitexponent > 4) {
            throw new \coding_exception(
                'Unsupported Commerce money minor-unit exponent.'
            );
        }
    }

    /**
     * Calculate a supported power of ten without floating-point arithmetic.
     *
     * @param int $exponent Exponent.
     * @return int
     */
    private static function power_of_ten(int $exponent): int {
        $result = 1;

        for ($index = 0; $index < $exponent; $index++) {
            $result *= 10;
        }

        return $result;
    }

    /**
     * Convert decimal digits to an integer with overflow protection.
     *
     * @param string $digits Decimal digits.
     * @return int
     */
    private static function decimal_digits_to_int(string $digits): int {
        $normalised = ltrim(
            $digits,
            '0'
        );

        if ($normalised === '') {
            return 0;
        }

        $maximum = (string)PHP_INT_MAX;

        if (
            strlen($normalised) > strlen($maximum)
            || (
                strlen($normalised) === strlen($maximum)
                && strcmp($normalised, $maximum) > 0
            )
        ) {
            throw new \coding_exception(
                'A Commerce money amount exceeds the supported integer range.'
            );
        }

        return (int)$normalised;
    }

    /**
     * Add two non-negative integers with overflow protection.
     *
     * @param int $left Left value.
     * @param int $right Right value.
     * @return int
     */
    private static function add_integers_safely(
        int $left,
        int $right
    ): int {
        if ($right > PHP_INT_MAX - $left) {
            throw new \coding_exception(
                'A Commerce money operation exceeds the supported integer range.'
            );
        }

        return $left + $right;
    }

    /**
     * Multiply two non-negative integers with overflow protection.
     *
     * @param int $left Left value.
     * @param int $right Right value.
     * @return int
     */
    private static function multiply_integers_safely(
        int $left,
        int $right
    ): int {
        if (
            $left !== 0
            && $right > intdiv(PHP_INT_MAX, $left)
        ) {
            throw new \coding_exception(
                'A Commerce money operation exceeds the supported integer range.'
            );
        }

        return $left * $right;
    }
}
