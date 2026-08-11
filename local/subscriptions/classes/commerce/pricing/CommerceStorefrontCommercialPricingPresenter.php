<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\pricing;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation;
use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontProduct;
use local_subscriptions\commerce\trial\CommerceTrialCartPricingService;

/** Builds one canonical customer-facing Upgrade price breakdown. */
final class CommerceStorefrontCommercialPricingPresenter {
    /** @return array<string,mixed> */
    public static function upgrade(
        CommerceStorefrontProduct $product,
        int $userid,
        string $currency
    ): array {
        global $DB;

        $upgrade = $product->get_upgrade();
        if ($upgrade === null) {
            return [];
        }

        $currency = strtoupper(trim($currency));
        $price = null;

        foreach ($product->get_prices() as $candidate) {
            if ($candidate->get_currency() === $currency) {
                $price = $candidate;
                break;
            }
        }

        if ($price === null) {
            return [];
        }

        $initialminor = (int)(
            $price->get_compare_amount_minor()
            ?? $price->get_amount_minor()
        );
        $promotedminor = $price->get_amount_minor();
        $promotionminor = max(0, $initialminor - $promotedminor);
        $promotionpercent = (int)(
            $price->get_discount_percentage() ?? 0
        );

        $trialadjustedminor = $promotedminor;
        $trialminor = 0;
        $trialpercent = 0;

        if ($userid > 0) {
            $trial = CommerceTrialCartPricingService::create()->resolve(
                $userid,
                $product->get_sku(),
                $currency,
                $promotedminor
            );

            if ($trial !== null) {
                $trialadjustedminor = $trial->get_total_minor();
                $trialminor = $trial->get_discount_minor();
                $trialpercent = $trial->get_discount_percent();
            }
        }

        $fallbackcredit = max(
            0,
            $trialadjustedminor
            - (
                $trialpercent > 0
                    ? max(
                        0,
                        $upgrade->get_amount_minor()
                        - intdiv(
                            ($upgrade->get_amount_minor() * $trialpercent) + 50,
                            100
                        )
                    )
                    : $upgrade->get_amount_minor()
            )
        );

        $ownedcreditminor = (
            new CommerceOwnedProductCreditResolver($DB)
        )->resolve(
            $userid,
            $upgrade->get_from_plan_id(),
            $currency,
            $trialpercent,
            $fallbackcredit
        );

        $finalminor = max(
            0,
            $trialadjustedminor - $ownedcreditminor
        );

        return self::present(
            $initialminor,
            $promotionminor,
            $trialminor,
            $ownedcreditminor,
            $finalminor,
            $promotionpercent,
            $trialpercent,
            $currency,
            $upgrade->get_from_label()
        );
    }

    /** @return array<string,mixed> */
    private static function present(
        int $initialminor,
        int $promotionminor,
        int $trialminor,
        int $creditminor,
        int $finalminor,
        int $promotionpercent,
        int $trialpercent,
        string $currency,
        string $fromlabel
    ): array {
        return [
            'hascommercialupgradepricing' => true,
            'commercialdetailslabel' => get_string(
                'commerce_pricing_details',
                'local_subscriptions'
            ),
            'commercialinitiallabel' => get_string(
                'commerce_pricing_initial_product',
                'local_subscriptions'
            ),
            'commercialinitialformatted' =>
                CommercePurchasePresentation::money(
                    $initialminor,
                    $currency
                ),
            'hascommercialpromotion' => $promotionminor > 0,
            'commercialpromotionlabel' => $promotionpercent > 0
                ? get_string(
                    'commerce_pricing_initial_promotion_percent',
                    'local_subscriptions',
                    $promotionpercent
                )
                : get_string(
                    'commerce_pricing_initial_promotion',
                    'local_subscriptions'
                ),
            'commercialpromotionpercent' => $promotionpercent,
            'hascommercialpromotionpercent' => $promotionpercent > 0,
            'commercialtrialpercent' => $trialpercent,
            'hascommercialtrialpercent' => $trialpercent > 0,
            'commercialpromotionformatted' =>
                CommercePurchasePresentation::money(
                    $promotionminor,
                    $currency
                ),
            'hascommercialtrial' => $trialminor > 0,
            'commercialtriallabel' => get_string(
                'commerce_trial_storefront_discount',
                'local_subscriptions',
                $trialpercent
            ),
            'commercialtrialformatted' =>
                CommercePurchasePresentation::money(
                    $trialminor,
                    $currency
                ),
            'commercialcreditlabel' => get_string(
                'commerce_pricing_owned_credit',
                'local_subscriptions',
                $fromlabel
            ),
            'commercialcreditformatted' =>
                CommercePurchasePresentation::money(
                    $creditminor,
                    $currency
                ),
            'commercialofferlabel' => get_string(
                'commerce_pricing_upgrade_offer',
                'local_subscriptions'
            ),
            'commercialsavinglabel' => get_string(
                'commerce_pricing_you_save',
                'local_subscriptions'
            ),
            'commercialsavingformatted' =>
                CommercePurchasePresentation::money(
                    max(0, $initialminor - $finalminor),
                    $currency
                ),
            'commercialfinallabel' => get_string(
                'commerce_pricing_final_price',
                'local_subscriptions'
            ),
            'commercialfinalformatted' =>
                CommercePurchasePresentation::money(
                    $finalminor,
                    $currency
                ),
        ];
    }
}
