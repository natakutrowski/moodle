<?php

declare(strict_types=1);
namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

/** Strict, side-effect-free Native checkout and settled payment routing. */
final class CommerceGiftNativeProviderGuard {
    public static function assert_checkout(string $currency, string $provider): void {
        $expected = CommerceGiftPaymentProviderReadiness::route_for_currency($currency);
        if (!in_array($currency, ['EUR', 'RUB'], true)
            || strtolower(trim($provider)) !== $expected) {
            throw new \DomainException('Gift Native checkout provider/currency mismatch.');
        }
    }

    public static function assert_paid(string $purchasecurrency, string $itemcurrency,
        string $paymentcurrency, string $paymentprovider): void {
        self::assert_checkout($purchasecurrency, $paymentprovider);
        if ($itemcurrency !== $purchasecurrency || $paymentcurrency !== $purchasecurrency) {
            throw new \DomainException('Gift Native paid currency differs from checkout.');
        }
    }
}
