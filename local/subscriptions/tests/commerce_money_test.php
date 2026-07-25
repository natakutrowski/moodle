<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\domain\value\CommerceMoney;

/**
 * Tests for the immutable Commerce monetary value object.
 *
 * @covers \local_subscriptions\commerce\domain\value\CommerceMoney
 */
final class commerce_money_test extends advanced_testcase {

    public function test_money_normalises_currency_and_preserves_minor_amount(): void {
        $money = new CommerceMoney(
            14990,
            ' eur '
        );

        $this->assertSame(
            14990,
            $money->get_amount_minor()
        );

        $this->assertSame(
            'EUR',
            $money->get_currency()
        );
    }

    public function test_money_can_be_created_from_exact_major_amount(): void {
        $money = CommerceMoney::from_major(
            '149.90',
            'EUR'
        );

        $this->assertSame(
            14990,
            $money->get_amount_minor()
        );

        $this->assertSame(
            '149.90',
            $money->get_amount_major()
        );
    }

    public function test_major_amount_is_padded_to_currency_exponent(): void {
        $money = CommerceMoney::from_major(
            '149.9',
            'EUR'
        );

        $this->assertSame(
            14990,
            $money->get_amount_minor()
        );
    }

    public function test_integer_major_amount_is_supported_without_float(): void {
        $money = CommerceMoney::from_major(
            149,
            'EUR'
        );

        $this->assertSame(
            14900,
            $money->get_amount_minor()
        );
    }

    public function test_zero_exponent_currency_is_supported(): void {
        $money = CommerceMoney::from_major(
            '149',
            'JPY',
            0
        );

        $this->assertSame(
            149,
            $money->get_amount_minor()
        );

        $this->assertSame(
            '149',
            $money->get_amount_major(0)
        );
    }

    public function test_four_digit_minor_unit_exponent_is_supported(): void {
        $money = CommerceMoney::from_major(
            '1.2345',
            'CLF',
            4
        );

        $this->assertSame(
            12345,
            $money->get_amount_minor()
        );

        $this->assertSame(
            '1.2345',
            $money->get_amount_major(4)
        );
    }

    public function test_money_values_with_same_currency_can_be_added(): void {
        $left = CommerceMoney::from_minor(
            12000,
            'EUR'
        );

        $right = CommerceMoney::from_minor(
            2990,
            'EUR'
        );

        $result = $left->add($right);

        $this->assertSame(
            14990,
            $result->get_amount_minor()
        );

        $this->assertSame(
            12000,
            $left->get_amount_minor()
        );
    }

    public function test_money_values_with_same_currency_can_be_subtracted(): void {
        $result = CommerceMoney::from_minor(
            14990,
            'EUR'
        )->subtract(
            CommerceMoney::from_minor(
                2990,
                'EUR'
            )
        );

        $this->assertSame(
            12000,
            $result->get_amount_minor()
        );
    }

    public function test_money_can_be_multiplied_by_quantity(): void {
        $result = CommerceMoney::from_minor(
            1900,
            'EUR'
        )->multiply(3);

        $this->assertSame(
            5700,
            $result->get_amount_minor()
        );
    }

    public function test_money_values_can_be_compared(): void {
        $lower = CommerceMoney::from_minor(
            1900,
            'EUR'
        );

        $greater = CommerceMoney::from_minor(
            2900,
            'EUR'
        );

        $this->assertSame(
            -1,
            $lower->compare($greater)
        );

        $this->assertTrue(
            $lower->is_less_than($greater)
        );

        $this->assertTrue(
            $greater->is_greater_than($lower)
        );
    }

    public function test_money_equality_uses_amount_and_currency(): void {
        $money = CommerceMoney::from_minor(
            1900,
            'eur'
        );

        $this->assertTrue(
            $money->equals(
                CommerceMoney::from_minor(
                    1900,
                    'EUR'
                )
            )
        );

        $this->assertFalse(
            $money->equals(
                CommerceMoney::from_minor(
                    1900,
                    'RUB'
                )
            )
        );
    }

    public function test_zero_money_is_detected(): void {
        $money = CommerceMoney::zero(
            'EUR'
        );

        $this->assertTrue(
            $money->is_zero()
        );

        $this->assertSame(
            [
                'amountminor' => 0,
                'currency' => 'EUR',
            ],
            $money->to_array()
        );
    }

    public function test_negative_amount_is_rejected(): void {
        $this->expectException(
            \coding_exception::class
        );

        CommerceMoney::from_minor(
            -1,
            'EUR'
        );
    }

    public function test_invalid_currency_is_rejected(): void {
        $this->expectException(
            \coding_exception::class
        );

        CommerceMoney::from_minor(
            1000,
            'EURO'
        );
    }

    public function test_major_amount_with_too_many_decimals_is_rejected(): void {
        $this->expectException(
            \coding_exception::class
        );

        CommerceMoney::from_major(
            '19.999',
            'EUR'
        );
    }

    public function test_non_decimal_major_amount_is_rejected(): void {
        $this->expectException(
            \coding_exception::class
        );

        CommerceMoney::from_major(
            '19,90',
            'EUR'
        );
    }

    public function test_addition_rejects_different_currencies(): void {
        $this->expectException(
            \coding_exception::class
        );

        CommerceMoney::from_minor(
            1000,
            'EUR'
        )->add(
            CommerceMoney::from_minor(
                1000,
                'RUB'
            )
        );
    }

    public function test_subtraction_rejects_negative_result(): void {
        $this->expectException(
            \coding_exception::class
        );

        CommerceMoney::from_minor(
            1000,
            'EUR'
        )->subtract(
            CommerceMoney::from_minor(
                1001,
                'EUR'
            )
        );
    }

    public function test_negative_multiplier_is_rejected(): void {
        $this->expectException(
            \coding_exception::class
        );

        CommerceMoney::from_minor(
            1000,
            'EUR'
        )->multiply(-1);
    }

    public function test_unsupported_minor_unit_exponent_is_rejected(): void {
        $this->expectException(
            \coding_exception::class
        );

        CommerceMoney::from_major(
            '1.00',
            'EUR',
            5
        );
    }

    public function test_addition_overflow_is_rejected(): void {
        $this->expectException(
            \coding_exception::class
        );

        CommerceMoney::from_minor(
            PHP_INT_MAX,
            'EUR'
        )->add(
            CommerceMoney::from_minor(
                1,
                'EUR'
            )
        );
    }

    public function test_multiplication_overflow_is_rejected(): void {
        $this->expectException(
            \coding_exception::class
        );

        CommerceMoney::from_minor(
            PHP_INT_MAX,
            'EUR'
        )->multiply(2);
    }
}
