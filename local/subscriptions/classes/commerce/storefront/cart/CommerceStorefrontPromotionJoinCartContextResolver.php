<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\cart;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\repository\CommerceSessionCartRepository;
use local_subscriptions\commerce\cart\service\CommerceCartSessionKeyResolver;
use local_subscriptions\commerce\checkout\flow\CommerceDirectPurchaseSession;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinOperation;
use local_subscriptions\currency\Currency;

/**
 * Resolves the browser-session cart whose pedagogical hold belongs to the
 * current promotion-join journey for one product.
 *
 * Storefront eligibility must ignore the customer's own active hold. Without
 * this bridge, returning from checkout (or simply adding the accompaniment to
 * the normal cart) makes the owner look temporarily ineligible via
 * JOIN_IN_PROGRESS, which in turn hides the product behind the "owned" filter.
 */
final class CommerceStorefrontPromotionJoinCartContextResolver {
    public function __construct(
        private readonly CommerceSessionCartRepository $carts,
        private readonly CommerceCartSessionKeyResolver $keys
    ) {
    }

    public static function create(): self {
        return new self(
            new CommerceSessionCartRepository(),
            new CommerceCartSessionKeyResolver()
        );
    }

    public function excluded_cart_uuid(
        int $customerid,
        ?string $currency,
        string $productsku
    ): ?string {
        $sku = strtoupper(trim($productsku));
        if ($sku === '') {
            return null;
        }

        // Buy Now is isolated from the normal cart. Its durable session is the
        // authoritative resume token after an unpaid checkout is abandoned.
        $direct = CommerceDirectPurchaseSession::current_any_currency();
        if (
            $direct !== null
            && $direct['sku'] === $sku
            && $direct['cartuuid'] !== null
            && $this->is_promotion_join_metadata($direct['metadata'])
        ) {
            return $direct['cartuuid'];
        }

        if ($customerid <= 0) {
            return null;
        }

        $currency = Currency::sanitize((string)$currency);
        if ($currency === '') {
            return null;
        }

        $cart = $this->carts->find(
            $this->keys->resolve($customerid, $currency)
        );
        if ($cart === null) {
            return null;
        }

        foreach ($cart->get_items() as $item) {
            if ($item->get_product_sku() !== $sku) {
                continue;
            }
            if ($this->is_promotion_join_metadata($item->get_metadata())) {
                return $cart->get_uuid();
            }
        }

        return null;
    }

    /** @param array<string,mixed> $metadata */
    private function is_promotion_join_metadata(array $metadata): bool {
        return strtolower(trim((string)($metadata['operation'] ?? '')))
            === CommercePedagogicalPromotionJoinOperation::OPERATION;
    }
}
