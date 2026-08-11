<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\trial;

defined('MOODLE_INTERNAL') || die();

/**
 * Revalidates and prices a Trial conversion entirely on the server.
 *
 * No percentage or amount supplied by the browser is trusted.
 */
final class CommerceTrialCartPricingService {
    public function __construct(private readonly CommerceTrialConversionBridge $bridge) {
    }

    public static function create(): self {
        return new self(CommerceTrialConversionBridge::create());
    }

    public function canonical_metadata(
        int $userid,
        string $productsku,
        string $currency,
        ?int $at = null
    ): array {
        $offer = $this->require_offer($userid, $productsku, $currency, $at);

        return [
            'operation' => 'trialconversion',
            'trialconversion' => 1,
            'trialdiscountpercent' => $offer->get_discount_percent(),
            'trialdiscountexpiresat' => $offer->get_expires_at(),
            'trialproductsku' => $offer->get_product_sku(),
        ];
    }

    public function resolve(
        int $userid,
        string $productsku,
        string $currency,
        int $originalminor,
        ?int $at = null
    ): ?CommerceTrialCartPrice {
        try {
            $offer = $this->require_offer($userid, $productsku, $currency, $at);
        } catch (\Throwable) {
            return null;
        }

        $originalminor = max(0, $originalminor);
        $discountminor = intdiv(
            ($originalminor * $offer->get_discount_percent()) + 50,
            100
        );
        $discountminor = min($originalminor, $discountminor);

        return new CommerceTrialCartPrice(
            strtoupper(trim($productsku)),
            strtoupper(trim($currency)),
            $originalminor,
            $discountminor,
            $originalminor - $discountminor,
            $offer->get_discount_percent(),
            $offer->get_expires_at()
        );
    }

    private function require_offer(
        int $userid,
        string $productsku,
        string $currency,
        ?int $at
    ): CommerceTrialConversionOffer {
        if ($userid <= 0) {
            throw new \RuntimeException('A Trial conversion requires an authenticated Trial user.');
        }

        $offer = $this->bridge->resolve_for_user(
            $userid,
            $currency,
            $productsku
        );
        if ($offer === null || !$offer->targets_product()) {
            throw new \RuntimeException('No targeted Trial conversion offer is available.');
        }

        if ($at !== null && $offer->get_expires_at() < $at) {
            throw new \RuntimeException('The Trial conversion offer has expired.');
        }

        if ($offer->get_product_sku() !== strtoupper(trim($productsku))) {
            throw new \RuntimeException('The requested product is not the Trial conversion target.');
        }

        return $offer;
    }
}
