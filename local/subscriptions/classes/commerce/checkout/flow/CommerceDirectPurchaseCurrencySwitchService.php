<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\flow;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\domain\CommerceCartOperationResult;
use local_subscriptions\commerce\cart\service\CommerceCartRuntimeFactory;
use local_subscriptions\commerce\cart\service\CommerceCartService;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductPriceRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationService;
use local_subscriptions\currency\Currency;

/**
 * L8.2 — switches one isolated Buy Now purchase without touching the normal cart.
 *
 * The target direct cart is validated first without reserving a second seat.
 * Only after the target currency/price/cart is known to be valid do we atomically
 * rebind any pedagogical hold from the source direct-cart UUID to the target UUID.
 */
final class CommerceDirectPurchaseCurrencySwitchService {
    public static function create(): self {
        global $DB;

        $hydrator = new CommerceCatalogHydrator();
        $products = new CommerceProductRepository($DB, $hydrator);

        return new self(
            CommerceCartRuntimeFactory::create(),
            new CommerceProductPriceRepository($DB, $hydrator, $products),
            CommercePedagogicalSeatReservationService::create($DB)
        );
    }

    public function __construct(
        private readonly CommerceCartService $carts,
        private readonly CommerceProductPriceRepository $prices,
        private readonly CommercePedagogicalSeatReservationService $reservations
    ) {
    }

    /**
     * @param array{
     *   currency:string,
     *   sku:string,
     *   priceid:int,
     *   quantity:int,
     *   metadata:array,
     *   cartuuid:?string
     * } $sourcepurchase
     */
    public function switch(
        int $customerid,
        array $sourcepurchase,
        string $targetcurrency,
        string $language,
        ?int $at = null
    ): CommerceCartOperationResult {
        $sourcecurrency = Currency::sanitize(
            (string)($sourcepurchase['currency'] ?? '')
        );
        $targetcurrency = Currency::sanitize($targetcurrency);
        $sku = strtoupper(trim((string)($sourcepurchase['sku'] ?? '')));
        $sourcepriceid = (int)($sourcepurchase['priceid'] ?? 0);
        $quantity = (int)($sourcepurchase['quantity'] ?? 0);
        $metadata = (array)($sourcepurchase['metadata'] ?? []);
        $sourcecartuuid = strtolower(trim((string)($sourcepurchase['cartuuid'] ?? '')));

        if (
            $customerid < 0
            || $sourcecurrency === ''
            || $targetcurrency === ''
            || $sourcecurrency === $targetcurrency
            || $sku === ''
            || $sourcepriceid <= 0
            || $quantity <= 0
            || !preg_match('/^[a-f0-9]{32}$/', $sourcecartuuid)
        ) {
            throw new \coding_exception(
                'Invalid direct purchase currency switch payload.'
            );
        }

        // The session payload was originally written by the server, but verify
        // its source price again before moving a capacity hold.
        $sourceprice = $this->prices->find_by_id($sourcepriceid);
        if (
            $sourceprice === null
            || $sourceprice->get_product_sku() !== $sku
            || $sourceprice->get_currency() !== $sourcecurrency
        ) {
            throw new \coding_exception(
                'Direct purchase source price no longer matches the session payload.'
            );
        }

        $targetprice = $this->find_target_price($sku, $targetcurrency);
        if ($targetprice === null || $targetprice->get_id() === null) {
            throw new \moodle_exception('invalidparameter');
        }

        $now = $at ?? time();

        // Build and validate a fresh isolated direct cart, but deliberately do
        // not reserve a second pedagogical seat. The source hold remains active
        // until the atomic cart-UUID rebind below.
        $prepared = $this->carts->prepare_direct_product(
            $customerid,
            $targetcurrency,
            $language,
            $sku,
            (int)$targetprice->get_id(),
            $quantity,
            $metadata,
            $now,
            null,
            null,
            false,
            $sourcecartuuid
        );

        if (!$prepared->has_changed()) {
            return $prepared;
        }

        $targetcart = $prepared->get_cart();
        $targetitems = $targetcart->get_items();
        if (count($targetitems) !== 1) {
            throw new \coding_exception(
                'A direct purchase currency switch must produce exactly one line.'
            );
        }

        $targetitem = reset($targetitems);
        if ($targetitem === false) {
            throw new \coding_exception(
                'A direct purchase currency switch produced no target line.'
            );
        }

        // Force the complete target pricing/calculation path before moving the
        // hold. This catches currency-specific Personal Offer/Trial/upgrade
        // incompatibilities while the original reservation is still untouched.
        $this->carts->direct_snapshot(
            $customerid,
            $targetcurrency,
            $language,
            $targetitem->get_product_sku(),
            $targetitem->get_price_id(),
            $targetitem->get_quantity(),
            $targetitem->get_metadata(),
            $now,
            $targetcart->get_uuid(),
            null,
            false,
            $sourcecartuuid
        );

        $linked = $this->reservations->is_pedagogically_linked($sku);
        if (
            $linked
            && !$this->reservations->has_active_hold(
                $sku,
                $sourcecartuuid,
                $now
            )
        ) {
            throw new \moodle_exception(
                'commerce_cart_checkout_seat_expired',
                'local_subscriptions'
            );
        }

        $transferred = $this->reservations->transfer_cart(
            $sourcecartuuid,
            $targetcart->get_uuid(),
            $customerid,
            $now
        );

        if ($linked && $transferred !== 1) {
            throw new \coding_exception(
                'Direct Purchase pedagogical hold was not transferred exactly once.'
            );
        }

        return $prepared;
    }

    private function find_target_price(
        string $sku,
        string $currency
    ): ?\local_subscriptions\commerce\catalog\domain\CommerceProductPrice {
        $fallback = null;

        foreach ($this->prices->find_by_product_sku($sku, true) as $price) {
            if ($price->get_currency() !== $currency) {
                continue;
            }

            if (
                $price->get_provider() === null
                || trim((string)$price->get_provider()) === ''
            ) {
                return $price;
            }

            $fallback ??= $price;
        }

        return $fallback;
    }
}
