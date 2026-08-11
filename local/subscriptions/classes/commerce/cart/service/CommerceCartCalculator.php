<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\cart\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\catalog\CommerceCartCatalogGateway;
use local_subscriptions\commerce\cart\domain\CommerceCalculatedCartItem;
use local_subscriptions\commerce\cart\domain\CommerceCart;
use local_subscriptions\commerce\cart\domain\CommerceCartMessage;
use local_subscriptions\commerce\cart\domain\CommerceCartSnapshot;
use local_subscriptions\commerce\cart\domain\CommerceCartTotals;
use local_subscriptions\commerce\domain\value\CommerceMoney;
use local_subscriptions\commerce\promotion\service\CommercePromotionEngine;
use local_subscriptions\commerce\trial\CommerceTrialCartPricingService;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferCheckoutPricingService;

/** Resolves current prices, quantities and promotion adjustments without mutating the cart. */
final class CommerceCartCalculator {
    public function __construct(
        private readonly CommerceCartCatalogGateway $catalog,
        private readonly ?CommercePromotionEngine $promotions = null,
        private readonly ?CommerceCartUpgradePricingService $upgrades = null,
        private readonly ?CommerceTrialCartPricingService $trialpricing = null,
        private readonly ?CommercePersonalOfferCheckoutPricingService $personaloffers = null
    ) {
    }

    public function calculate(CommerceCart $cart, string $language, ?int $at = null): CommerceCartSnapshot {
        $calculatedat = $at ?? time();
        $subtotalminor = 0;
        $items = [];
        $promotionitems = [];
        $messages = [];

        foreach ($cart->get_items() as $item) {
            $quote = $this->catalog->quote(
                $item->get_product_sku(),
                $item->get_price_id(),
                $cart->get_currency(),
                $language,
                $calculatedat
            );
            $policy = $quote->get_quantity_policy();
            $policy->assert_allowed($item->get_quantity());
            $metadata = $item->get_metadata();
            $unitprice = $quote->get_unit_price();
            $displayname = $quote->get_display_name();
            $operation = strtolower(trim((string)($metadata['operation'] ?? '')));
            $isupgrade = $operation === 'upgrade';
            $istrialconversion = $operation === 'trialconversion'
                || (int)($metadata['trialdiscountpercent'] ?? 0) > 0;
            $ispersonaloffer = $operation === 'personaloffer';
            if ($ispersonaloffer) {
                if ($this->personaloffers === null || $item->get_quantity() !== 1) {
                    throw new \RuntimeException('Personal Offer checkout pricing is unavailable.');
                }
                $offeruuid = strtolower(trim((string)($metadata['personal_offer_uuid'] ?? '')));
                if ($offeruuid === '') {
                    throw new \RuntimeException('Personal Offer cart line has no offer identifier.');
                }
                $unitprice = CommerceMoney::from_minor(
                    $this->personaloffers->resolve_unit_minor(
                        $offeruuid, $item->get_product_sku(), $cart->get_currency(),
                        $unitprice->get_amount_minor(), $calculatedat
                    ),
                    $cart->get_currency()
                );
            }
            if ($isupgrade) {
                if ($this->upgrades === null || $item->get_quantity() !== 1) {
                    throw new \RuntimeException('The cart upgrade can no longer be validated.');
                }
                $upgrade = $this->upgrades->resolve(
                    $cart->get_customer_id(),
                    $item->get_product_sku(),
                    $cart->get_currency(),
                    isset($metadata['targetplanid']) ? (int)$metadata['targetplanid'] : null
                );
                if ($upgrade === null) {
                    $fallbackminor = max(0, (int)($metadata['upgradeamountminor'] ?? 0));
                    $unitprice = CommerceMoney::from_minor($fallbackminor, $cart->get_currency());
                    $messages[] = new CommerceCartMessage(
                        'upgrade_not_eligible',
                        CommerceCartMessage::LEVEL_WARNING,
                        ['productsku' => $item->get_product_sku()]
                    );
                } else {
                    $upgradeamountminor = $upgrade->get_amount_minor();

                    // The resolved Upgrade amount already comes from the
                    // active promoted target price minus the owned source
                    // value. Never apply the target promotion percentage a
                    // second time to the differential amount.

                    $unitprice = CommerceMoney::from_minor(
                        $upgradeamountminor,
                        $upgrade->get_currency()
                    );
                    $displayname = trim($upgrade->get_from_label()) !== '' && trim($upgrade->get_to_label()) !== ''
                        ? $upgrade->get_from_label() . ' → ' . $upgrade->get_to_label()
                        : $displayname;
                }
            }

            $trialprice = null;
            if ($istrialconversion && !$isupgrade) {
                if ($this->trialpricing === null || $item->get_quantity() !== 1) {
                    throw new \RuntimeException('The Trial conversion can no longer be validated.');
                }

                $trialprice = $this->trialpricing->resolve(
                    $cart->get_customer_id(),
                    $item->get_product_sku(),
                    $cart->get_currency(),
                    $unitprice->get_amount_minor(),
                    $calculatedat
                );
                if ($trialprice === null) {
                    $messages[] = new CommerceCartMessage(
                        'trial_conversion_not_eligible',
                        CommerceCartMessage::LEVEL_WARNING,
                        ['productsku' => $item->get_product_sku()]
                    );
                } else {
                    // Trial pricing belongs to this course line, not to the whole cart.
                    $unitprice = CommerceMoney::from_minor(
                        $trialprice->get_total_minor(),
                        $trialprice->get_currency()
                    );
                }
            }

            $lineminor = $unitprice->get_amount_minor() * $item->get_quantity();
            $subtotalminor += $lineminor;

            $items[] = new CommerceCalculatedCartItem(
                $item,
                $displayname,
                $unitprice,
                CommerceMoney::from_minor($lineminor, $cart->get_currency()),
                $policy->get_minimum(),
                $policy->get_maximum(),
                $policy->get_step(),
                $quote->get_product_type()
            );
            // Upgrade prices returned by SubscriptionAdvisor already include
            // the active Trial discount and are final differential prices.
            // They must receive neither Trial nor cart promotion a second time.
            if (!$isupgrade && !$istrialconversion && !$ispersonaloffer) {
                $promotionitems[] = [
                    'sku' => $item->get_product_sku(),
                    'type' => $quote->get_product_type(),
                    'subtotalminor' => $lineminor,
                ];
            }
        }

        $adjustments = [];
        $discountminor = 0;
        if ($this->promotions !== null && $subtotalminor > 0) {
            $metadata = $cart->get_metadata();
            $manualcode = isset($metadata['promotion_code']) ? (string)$metadata['promotion_code'] : null;
            $calculation = $this->promotions->calculate(
                $subtotalminor,
                $cart->get_currency(),
                $cart->get_customer_id() > 0 ? $cart->get_customer_id() : null,
                $promotionitems,
                $manualcode,
                $calculatedat
            );
            $adjustments = array_merge($adjustments, $calculation->get_adjustments());
            foreach ($calculation->get_rejections() as $rejection) {
                $messages[] = new CommerceCartMessage(
                    'promotion_' . $rejection['reason'],
                    CommerceCartMessage::LEVEL_WARNING,
                    ['code' => $rejection['code']] + $rejection['context']
                );
            }
        }

        foreach ($adjustments as $adjustment) {
            $discountminor += $adjustment->get_amount()->get_amount_minor();
        }

        $subtotal = CommerceMoney::from_minor($subtotalminor, $cart->get_currency());
        $discount = CommerceMoney::from_minor($discountminor, $cart->get_currency());
        $zero = CommerceMoney::zero($cart->get_currency());
        $total = CommerceMoney::from_minor(max(0, $subtotalminor - $discountminor), $cart->get_currency());

        return new CommerceCartSnapshot(
            $cart,
            $items,
            new CommerceCartTotals($subtotal, $discount, $zero, $total),
            $calculatedat,
            $messages,
            $adjustments
        );
    }
}
