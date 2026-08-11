<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\cart\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontUpgrade;
use local_subscriptions\commerce\storefront\upgrade\CommerceStorefrontUpgradeResolver;

/** Revalidates Native cart upgrade eligibility and price entirely on the server. */
final class CommerceCartUpgradePricingService {
    public function __construct(
        private readonly CommerceProductRepository $products,
        private readonly CommerceStorefrontUpgradeResolver $upgrades
    ) {}

    public function resolve(
        int $userid,
        string $productsku,
        string $currency,
        ?int $targetplanid = null
    ): ?CommerceStorefrontUpgrade {
        if ($userid <= 0) {
            return null;
        }

        $product = $this->products->find_by_sku(strtoupper(trim($productsku)));
        if ($product === null || $product->get_id() === null) {
            return null;
        }

        return $this->upgrades->resolve(
            $userid,
            (int)$product->get_id(),
            strtoupper(trim($currency)),
            $targetplanid
        );
    }

    /** @return array<string, mixed> */
    public function canonical_metadata(
        int $userid,
        string $productsku,
        string $currency,
        ?int $targetplanid = null
    ): array {
        $upgrade = $this->resolve($userid, $productsku, $currency, $targetplanid);
        if ($upgrade === null) {
            throw new \moodle_exception('commerce_cart_upgrade_not_eligible', 'local_subscriptions');
        }

        return [
            'operation' => 'upgrade',
            'sourceplanid' => $upgrade->get_from_plan_id(),
            'targetplanid' => $upgrade->get_to_plan_id(),
            'upgradepricingmode' => 'difference',
            'upgradeamountminor' => $upgrade->get_amount_minor(),
            'upgradecurrency' => $upgrade->get_currency(),
            'upgradefromlabel' => $upgrade->get_from_label(),
            'upgradetolabel' => $upgrade->get_to_label(),
            'upgradesummary' => $upgrade->get_summary(),
        ];
    }
}
