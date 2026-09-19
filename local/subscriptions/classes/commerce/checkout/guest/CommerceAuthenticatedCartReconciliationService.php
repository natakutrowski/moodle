<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\guest;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\domain\CommerceCartItem;
use local_subscriptions\commerce\cart\service\CommerceCartService;

/**
 * Rebuilds an authenticated cart through the certified Cart mutation boundary.
 *
 * Guest-cart transfer deliberately preserves the customer's intent. Once the
 * real Moodle account is authenticated, this service replays every cart item
 * through CommerceCartService::add_product(), which is already authoritative
 * for effective ownership (Native + Legacy), bundles, upgrades, trials,
 * quantity policy and canonical pricing metadata.
 */
final class CommerceAuthenticatedCartReconciliationService {
    public function __construct(
        private readonly CommerceCartService $carts
    ) {
    }

    /**
     * @return array{
     *     before:int,
     *     after:int,
     *     removed:int,
     *     removedskus:string[]
     * }
     */
    public function reconcile(
        int $userid,
        string $currency,
        string $language
    ): array {
        if ($userid <= 0) {
            throw new \coding_exception(
                'Authenticated cart reconciliation requires a Moodle user.'
            );
        }

        $cart = $this->carts->open(
            $userid,
            $currency
        );
        $items = $cart->get_items();

        if ($items === []) {
            return [
                'before' => 0,
                'after' => 0,
                'removed' => 0,
                'removedskus' => [],
            ];
        }

        // Keep cart-level metadata (promotion code, source, etc.) but force all
        // items back through the normal Cart admission rules.
        $this->carts->clear_cart(
            $userid,
            $currency
        );

        $removed = [];

        foreach ($items as $item) {
            $result = $this->carts->add_product(
                $userid,
                $currency,
                $language,
                $item->get_product_sku(),
                $item->get_price_id(),
                $item->get_quantity(),
                $item->get_metadata()
            );

            if (!$result->has_changed()) {
                $removed[] =
                    $item->get_product_sku();
            }
        }

        $snapshot = $this->carts->snapshot(
            $userid,
            $currency,
            $language
        );

        $removed = array_values(
            array_unique($removed)
        );

        return [
            'before' => count($items),
            'after' => count($snapshot->get_items()),
            'removed' => count($removed),
            'removedskus' => $removed,
        ];
    }
}
