<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\flow;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\currency\Currency;

/**
 * H13.3.2 — browser-session state for a Buy Now purchase isolated from the
 * normal Commerce cart.
 *
 * Only stable catalogue references and attribution metadata are stored.
 */
final class CommerceDirectPurchaseSession {
    private const SESSION_KEY =
        'local_subscriptions_direct_purchase';

    public static function store(
        string $currency,
        string $sku,
        int $priceid,
        int $quantity,
        array $metadata = [],
        ?string $cartuuid = null
    ): void {
        global $SESSION;

        $currency = Currency::sanitize($currency);
        $sku = strtoupper(trim($sku));

        if (
            $currency === ''
            || $sku === ''
            || $priceid <= 0
            || $quantity <= 0
        ) {
            throw new \coding_exception(
                'Invalid direct purchase session payload.'
            );
        }

        $cartuuid = $cartuuid !== null
            ? strtolower(trim($cartuuid))
            : null;
        if (
            $cartuuid !== null
            && !preg_match('/^[a-f0-9]{32}$/', $cartuuid)
        ) {
            throw new \coding_exception(
                'Invalid direct purchase cart UUID.'
            );
        }

        $SESSION->{self::SESSION_KEY} = [
            'currency' => $currency,
            'sku' => $sku,
            'priceid' => $priceid,
            'quantity' => $quantity,
            'metadata' => $metadata,
            'cartuuid' => $cartuuid,
            'storedat' => time(),
        ];
    }

    /**
     * @return array{
     *   currency:string,
     *   sku:string,
     *   priceid:int,
     *   quantity:int,
     *   metadata:array,
     *   cartuuid:?string,
     *   storedat:int
     * }|null
     */
    public static function current(
        string $currency
    ): ?array {
        $currency = Currency::sanitize($currency);
        $data = self::current_any_currency();

        if (
            $currency === ''
            || $data === null
            || $data['currency'] !== $currency
        ) {
            return null;
        }

        return $data;
    }

    /**
     * Returns the durable Buy Now payload regardless of the currently selected
     * Storefront/Showroom currency. This is intentionally session-local and is
     * used only to resume/rebind the same Direct Purchase from an authorised
     * currency-selection surface.
     *
     * @return array{
     *   currency:string,
     *   sku:string,
     *   priceid:int,
     *   quantity:int,
     *   metadata:array,
     *   cartuuid:?string,
     *   storedat:int
     * }|null
     */
    public static function current_any_currency(): ?array {
        global $SESSION;

        $data = $SESSION->{self::SESSION_KEY} ?? null;
        if (!is_array($data)) {
            return null;
        }

        $storedcurrency = Currency::sanitize(
            (string)($data['currency'] ?? '')
        );
        $sku = strtoupper(trim((string)($data['sku'] ?? '')));
        $priceid = (int)($data['priceid'] ?? 0);
        $quantity = (int)($data['quantity'] ?? 0);

        if (
            $storedcurrency === ''
            || $sku === ''
            || $priceid <= 0
            || $quantity <= 0
        ) {
            return null;
        }

        $cartuuid = isset($data['cartuuid'])
            ? strtolower(trim((string)$data['cartuuid']))
            : '';
        $cartuuid = preg_match('/^[a-f0-9]{32}$/', $cartuuid)
            ? $cartuuid
            : null;

        return [
            'currency' => $storedcurrency,
            'sku' => $sku,
            'priceid' => $priceid,
            'quantity' => $quantity,
            'metadata' => (array)($data['metadata'] ?? []),
            'cartuuid' => $cartuuid,
            'storedat' => (int)($data['storedat'] ?? 0),
        ];
    }

    public static function cart_uuid_for_sku(string $sku): ?string {
        $current = self::current_any_currency();
        $sku = strtoupper(trim($sku));

        if (
            $current === null
            || $sku === ''
            || $current['sku'] !== $sku
        ) {
            return null;
        }

        return $current['cartuuid'];
    }

    public static function clear(): void {
        global $SESSION;
        unset($SESSION->{self::SESSION_KEY});
    }
}
