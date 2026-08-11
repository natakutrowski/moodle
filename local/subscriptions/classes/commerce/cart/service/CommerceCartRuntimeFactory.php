<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\cart\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\catalog\MoodleCommerceCartCatalogGateway;
use local_subscriptions\commerce\cart\ownership\MoodleCommerceCartOwnershipGateway;
use local_subscriptions\commerce\cart\ownership\CommerceBundlePurchaseEligibilityService;
use local_subscriptions\commerce\cart\policy\CommerceQuantityPolicyResolver;
use local_subscriptions\commerce\cart\repository\CommerceSessionCartRepository;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductPriceRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductComponentRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductTranslationRepository;
use local_subscriptions\commerce\storefront\ownership\CommerceStorefrontOwnershipResolver;
use local_subscriptions\commerce\storefront\upgrade\CommerceStorefrontUpgradeResolver;
use local_subscriptions\commerce\promotion\repository\MoodleCommercePromotionRepository;
use local_subscriptions\commerce\promotion\service\CommercePromotionEligibilityEvaluator;
use local_subscriptions\commerce\promotion\service\CommercePromotionEngine;
use local_subscriptions\commerce\promotion\eligibility\CommercePromotionCustomerEligibilityEvaluator;
use local_subscriptions\commerce\trial\CommerceTrialCartPricingService;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferCheckoutPricingService;

/** Builds the production G2-G3 session-cart service graph. */
final class CommerceCartRuntimeFactory {
    public static function create(): CommerceCartService {
        global $DB;

        $hydrator = new CommerceCatalogHydrator();
        $products = new CommerceProductRepository($DB, $hydrator);
        $prices = new CommerceProductPriceRepository($DB, $hydrator, $products);
        $translations = new CommerceProductTranslationRepository($DB, $hydrator, $products);
        $catalog = new MoodleCommerceCartCatalogGateway(
            $products,
            $prices,
            $translations,
            new CommerceQuantityPolicyResolver()
        );

        $promotions = new MoodleCommercePromotionRepository();

        $upgradepricing = new CommerceCartUpgradePricingService(
            $products,
            new CommerceStorefrontUpgradeResolver($DB)
        );
        $ownership = new MoodleCommerceCartOwnershipGateway(
            new CommerceStorefrontOwnershipResolver($DB)
        );
        $trialpricing = CommerceTrialCartPricingService::create();

        $bundleeligibility = new CommerceBundlePurchaseEligibilityService(
            new CommerceProductComponentRepository($DB, $hydrator, $products),
            $ownership
        );

        return new CommerceCartService(
            new CommerceSessionCartRepository(),
            new CommerceCartSessionKeyResolver(),
            new CommerceCartFactory(),
            new CommerceCartCalculator(
                $catalog,
                new CommercePromotionEngine(
                    $promotions,
                    new CommercePromotionEligibilityEvaluator(
                        $promotions,
                        new CommercePromotionCustomerEligibilityEvaluator($ownership)
                    )
                ),
                $upgradepricing,
                $trialpricing,
                CommercePersonalOfferCheckoutPricingService::create($DB)
            ),
            $catalog,
            $ownership,
            $upgradepricing,
            $bundleeligibility,
            $trialpricing
        );
    }
}
