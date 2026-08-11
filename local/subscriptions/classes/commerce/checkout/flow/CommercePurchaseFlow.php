<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\flow;

defined('MOODLE_INTERNAL') || die();

/** Canonical purchase-flow descriptor shared by cart, checkout and order result. */
final class CommercePurchaseFlow {
    public const CART = 'cart';
    public const DIRECT = 'direct';

    public static function normalise(string $flow): string {
        return strtolower(trim($flow)) === self::DIRECT ? self::DIRECT : self::CART;
    }

    public static function is_direct(string $flow): bool {
        return self::normalise($flow) === self::DIRECT;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function checkout_steps(string $flow, string $carturl): array {
        if (self::is_direct($flow)) {
            return [
                self::step(1, 'commerce_checkout_step_review', 'is-current', true),
                self::step(2, 'commerce_checkout_step_payment', '', false),
                self::step(3, 'commerce_checkout_step_confirmation', '', false),
            ];
        }

        return [
            self::step(1, 'commerce_checkout_step_cart', 'is-complete', false, true, $carturl),
            self::step(2, 'commerce_checkout_step_review', 'is-current', true),
            self::step(3, 'commerce_checkout_step_payment', '', false),
            self::step(4, 'commerce_checkout_step_confirmation', '', false),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function result_steps(string $flow, string $confirmationstate, bool $confirmationcurrent): array {
        if (self::is_direct($flow)) {
            return [
                self::step(1, 'commerce_checkout_step_review', 'is-complete', false),
                self::step(2, 'commerce_checkout_step_payment', 'is-complete', false),
                self::step(3, 'commerce_checkout_step_confirmation', $confirmationstate, $confirmationcurrent),
            ];
        }

        return [
            self::step(1, 'commerce_checkout_step_cart', 'is-complete', false),
            self::step(2, 'commerce_checkout_step_review', 'is-complete', false),
            self::step(3, 'commerce_checkout_step_payment', 'is-complete', false),
            self::step(4, 'commerce_checkout_step_confirmation', $confirmationstate, $confirmationcurrent),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function step(
        int $number,
        string $stringkey,
        string $state,
        bool $current,
        bool $clickable = false,
        string $url = ''
    ): array {
        return [
            'number' => $number,
            'label' => get_string($stringkey, 'local_subscriptions'),
            'state' => $state,
            'current' => $current,
            'clickable' => $clickable,
            'url' => $url,
        ];
    }
}
