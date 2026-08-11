<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\cart\presentation;

use local_subscriptions\commerce\catalog\assets\CommerceCatalogResponsiveImageService;
defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\domain\CommerceCartMessage;
use local_subscriptions\commerce\cart\domain\CommerceCartSnapshot;
use local_subscriptions\commerce\catalog\visual\CommerceProductVisualAuditService;
use local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation;
use local_subscriptions\commerce\storefront\presentation\CommerceStorefrontUrlResolver;
use local_subscriptions\commerce\storefront\repository\CommerceStorefrontRepository;
use local_subscriptions\commerce\pricing\CommerceCommercialPriceResolver;

/** Maps a calculated cart snapshot to public-safe Mustache data. */
final class CommerceCartPresenter {
    public static function present(CommerceCartSnapshot $snapshot, ?string $language = null): array {
        global $DB;

        $language = $language !== null && trim($language) !== '' ? $language : current_language();
        $currency = $snapshot->get_cart()->get_currency();
        $storefront = CommerceStorefrontRepository::create($DB);
        $items = [];
        $quantitytotal = 0;
        $listtotalminor = 0;
        $productpromotiontotalminor = 0;
        $trialdiscounttotalminor = 0;
        $upgradecredittotalminor = 0;

        foreach ($snapshot->get_items() as $calculated) {
            $item = $calculated->get_item();
            $quantitytotal += $item->get_quantity();
            $maximum = $calculated->get_maximum_quantity();
            $product = $storefront->find_by_sku(
                $item->get_product_sku(),
                $language,
                $currency,
                true
            );

            $metadata = $item->get_metadata();
            $operation = strtolower(trim((string)($metadata['operation'] ?? '')));
            $isupgrade = $operation === 'upgrade';
            $istrialconversion = $operation === 'trialconversion';
            $ispersonaloffer = $operation === 'personaloffer';
            $trialdiscountpercent = max(
                0,
                min(100, (int)($metadata['trialdiscountpercent'] ?? 0))
            );

            $commercialpricing = $isupgrade
                ? (new CommerceCommercialPriceResolver($DB))->resolve(
                    $calculated,
                    $currency,
                    $calculated->get_subtotal()->get_amount_minor(),
                    $snapshot->get_cart()->get_customer_id()
                )
                : null;
            // The calculated cart price is the final Trial price. Rebuild the
            // pre-Trial commercial price from the canonical percentage, then
            // read the catalogue comparison price when a product promotion
            // existed before Trial.
            $trialpromotedminor = $istrialconversion && $trialdiscountpercent > 0
                ? (int)round(
                    $calculated->get_unit_price()->get_amount_minor()
                    * 100
                    / (100 - $trialdiscountpercent)
                )
                : 0;

            $catalogueprice = null;
            if ($product !== null) {
                foreach ($product->get_prices() as $candidateprice) {
                    if (
                        (int)$candidateprice->get_id() === $item->get_price_id()
                        && $candidateprice->get_currency() === $currency
                    ) {
                        $catalogueprice = $candidateprice;
                        break;
                    }
                }
            }

            $hascataloguepromotion = $catalogueprice !== null
                && $catalogueprice->has_active_promotion()
                && $catalogueprice->get_compare_amount_minor() !== null;

            $hasproductpromotion = $istrialconversion
                && $hascataloguepromotion;

            $cataloguelistminor = $hascataloguepromotion
                ? (int)$catalogueprice->get_compare_amount_minor()
                : ($istrialconversion && $trialpromotedminor > 0
                    ? $trialpromotedminor
                    : $calculated->get_unit_price()->get_amount_minor());

            $cataloguepromotedminor = $hascataloguepromotion
                ? $catalogueprice->get_amount_minor()
                : $cataloguelistminor;

            $listtotalminor += $cataloguelistminor * $item->get_quantity();
            if ($hascataloguepromotion) {
                $productpromotiontotalminor +=
                    ($cataloguelistminor - $cataloguepromotedminor)
                    * $item->get_quantity();
            }

            $trialinitialminor = $hasproductpromotion
                ? (int)$catalogueprice->get_compare_amount_minor()
                : $trialpromotedminor;

            $promotionpercent = $commercialpricing !== null
                && $commercialpricing->get_initial_unit_minor() > 0
                ? (int)round(
                    (
                        $commercialpricing->get_promotion_unit_minor()
                        * 100
                    )
                    / $commercialpricing->get_initial_unit_minor()
                )
                : 0;

            $cartfinalunitminor = $commercialpricing !== null
                ? $commercialpricing->get_final_unit_minor()
                : $calculated->get_unit_price()->get_amount_minor();

            $cartinitialunitminor = $commercialpricing !== null
                ? $commercialpricing->get_initial_unit_minor()
                : $cataloguelistminor;

            $cartpricecompareformatted =
                $cartinitialunitminor > $cartfinalunitminor
                    ? CommercePurchasePresentation::money(
                        $cartinitialunitminor,
                        $currency
                    )
                    : '';

            $cartpricefinalformatted =
                CommercePurchasePresentation::money(
                    $cartfinalunitminor,
                    $currency
                );

            if ($commercialpricing !== null) {
                $trialdiscounttotalminor +=
                    $commercialpricing->get_trial_discount_total_minor();
                $upgradecredittotalminor +=
                    $commercialpricing->get_owned_credit_total_minor();
            } else if ($istrialconversion) {
                $trialdiscounttotalminor += max(
                    0,
                    ($trialpromotedminor
                        - $calculated->get_unit_price()->get_amount_minor())
                    * $item->get_quantity()
                );
            }

            $items[] = [
                // Technical identifiers are retained for POST actions only and are never rendered as customer content.
                'productsku' => $item->get_product_sku(),
                'priceid' => $item->get_price_id(),
                'name' => format_string($calculated->get_name()),
                // Reuse the exact product-type badge contract displayed by
                // Boutique and Storefront. Trial remains a pricing benefit,
                // not a replacement product type.
                'typelabel' => $product === null
                    ? ''
                    : self::type_label($product->get_type()),
                'hastype' => $product !== null,
                'istrialconversion' => $istrialconversion,
                'ispersonaloffer' => $ispersonaloffer,
                'personalofferbadge' => $ispersonaloffer
                    ? get_string('commerce_personal_offer_checkout_badge', 'local_subscriptions')
                    : '',
                'trialdiscountpercent' => $trialdiscountpercent,
                'triallabel' => get_string(
                    'commerce_trial_storefront_discount',
                    'local_subscriptions',
                    $trialdiscountpercent
                ),
                'trialoffertype' => get_string(
                    'commerce_trial_storefront_badge',
                    'local_subscriptions'
                ),
                'trialinitiallabel' => get_string(
                    'commerce_trial_storefront_initial_price',
                    'local_subscriptions'
                ),
                'trialpromotionlabel' => get_string(
                    'commerce_pricing_initial_promotion',
                    'local_subscriptions'
                ),
                'trialfinallabel' => get_string(
                    'commerce_trial_storefront_final_price',
                    'local_subscriptions'
                ),
                'hasproductpromotionbeforetrial' => $hasproductpromotion,
                'trialinitialpriceformatted' => $trialinitialminor > 0
                    ? CommercePurchasePresentation::money(
                        $trialinitialminor,
                        $currency
                    )
                    : '',
                'trialpromotedpriceformatted' => $hasproductpromotion
                    ? CommercePurchasePresentation::money(
                        $trialpromotedminor,
                        $currency
                    )
                    : '',
                'trialproductdiscountlabel' => $hasproductpromotion
                    && $catalogueprice->get_discount_percentage() !== null
                        ? get_string(
                            'commerce_storefront_discount_percentage',
                            'local_subscriptions',
                            $catalogueprice->get_discount_percentage()
                        )
                        : '',
                'trialfinalpriceformatted' => CommercePurchasePresentation::money(
                    $calculated->get_unit_price()->get_amount_minor(),
                    $currency
                ),
                'hascataloguepromotion' => $hascataloguepromotion,
                'catalogueinitialpriceformatted' => $hascataloguepromotion
                    ? CommercePurchasePresentation::money(
                        $cataloguelistminor,
                        $currency
                    )
                    : '',
                'cataloguepromotedpriceformatted' => $hascataloguepromotion
                    ? CommercePurchasePresentation::money(
                        $cataloguepromotedminor,
                        $currency
                    )
                    : '',
                'cataloguediscountlabel' => $hascataloguepromotion
                    && $catalogueprice->get_discount_percentage() !== null
                        ? get_string(
                            'commerce_storefront_discount_percentage',
                            'local_subscriptions',
                            $catalogueprice->get_discount_percentage()
                        )
                        : '',
                'cataloguepromotionlabel' => get_string(
                    'commerce_trial_storefront_product_promotion',
                    'local_subscriptions'
                ),
                'isupgrade' => $isupgrade,
                'hascommercialupgradepricing' =>
                    $commercialpricing !== null,
                'commercialdetailslabel' => get_string(
                    'commerce_pricing_details',
                    'local_subscriptions'
                ),
                'commercialinitiallabel' => get_string(
                    'commerce_pricing_initial_product',
                    'local_subscriptions'
                ),
                'commercialinitialformatted' =>
                    $commercialpricing !== null
                        ? CommercePurchasePresentation::money(
                            $commercialpricing->get_initial_unit_minor(),
                            $currency
                        )
                        : '',
                'hascommercialpromotion' =>
                    $commercialpricing !== null
                    && $commercialpricing->get_promotion_unit_minor() > 0,
                'commercialpromotionlabel' => get_string(
                    'commerce_pricing_initial_promotion',
                    'local_subscriptions'
                ),
                'commercialpromotionformatted' =>
                    $commercialpricing !== null
                        ? CommercePurchasePresentation::money(
                            $commercialpricing->get_promotion_unit_minor(),
                            $currency
                        )
                        : '',
                'hascommercialtrial' =>
                    $commercialpricing !== null
                    && $commercialpricing->get_trial_discount_unit_minor() > 0,
                'commercialtriallabel' => get_string(
                    'commerce_trial_storefront_discount',
                    'local_subscriptions',
                    $trialdiscountpercent
                ),
                'commercialtrialformatted' =>
                    $commercialpricing !== null
                        ? CommercePurchasePresentation::money(
                            $commercialpricing->get_trial_discount_unit_minor(),
                            $currency
                        )
                        : '',
                'commercialcreditlabel' => get_string(
                    'commerce_pricing_owned_credit',
                    'local_subscriptions',
                    (string)($metadata['upgradefromlabel'] ?? '')
                ),
                'commercialcreditformatted' =>
                    $commercialpricing !== null
                        ? CommercePurchasePresentation::money(
                            $commercialpricing->get_owned_credit_unit_minor(),
                            $currency
                        )
                        : '',
                'commercialofferlabel' => get_string(
                    'commerce_pricing_upgrade_offer',
                    'local_subscriptions'
                ),
                'commercialsavinglabel' => get_string(
                    'commerce_pricing_you_save',
                    'local_subscriptions'
                ),
                'commercialsavingformatted' =>
                    $commercialpricing !== null
                        ? CommercePurchasePresentation::money(
                            $commercialpricing->get_total_reduction_minor(),
                            $currency
                        )
                        : '',
                'commercialfinallabel' => get_string(
                    'commerce_pricing_final_price',
                    'local_subscriptions'
                ),
                'commercialfinalformatted' =>
                    $commercialpricing !== null
                        ? CommercePurchasePresentation::money(
                            $commercialpricing->get_final_unit_minor(),
                            $currency
                        )
                        : '',
                'upgradepath' => $isupgrade ? trim((string)($metadata['upgradefromlabel'] ?? '')) . ' → ' . trim((string)($metadata['upgradetolabel'] ?? '')) : '',
                'hasupgradepath' => $isupgrade
                    && trim((string)($metadata['upgradefromlabel'] ?? '')) !== ''
                    && trim((string)($metadata['upgradetolabel'] ?? '')) !== '',
                'cartpriceisupgrade' => $isupgrade,
                'cartpriceistrial' => !$isupgrade && $istrialconversion,
                'cartpricehaspromotion' =>
                    !$isupgrade
                    && !$istrialconversion
                    && $hascataloguepromotion,
                'cartpriceisstandard' =>
                    !$isupgrade
                    && !$istrialconversion
                    && !$hascataloguepromotion,
                'cartpricelabel' => $isupgrade
                    ? get_string(
                        'commerce_storefront_price_upgrade',
                        'local_subscriptions'
                    )
                    : (
                        $istrialconversion
                            ? get_string(
                                'commerce_storefront_price_discovery',
                                'local_subscriptions'
                            )
                            : (
                                $hascataloguepromotion
                                    ? get_string(
                                        'commerce_storefront_price_promotional',
                                        'local_subscriptions'
                                    )
                                    : get_string(
                                        'commerce_storefront_price_standard',
                                        'local_subscriptions'
                                    )
                            )
                    ),
                'cartpricefinalformatted' => $cartpricefinalformatted,
                'cartpricecompareformatted' =>
                    $cartpricecompareformatted,
                'cartpricehascompare' =>
                    $cartpricecompareformatted !== '',
                'cartpriceupgradebadge' => get_string(
                    'commerce_storefront_upgrade_offer_badge',
                    'local_subscriptions'
                ),
                'cartpricetrialbadge' => get_string(
                    'commerce_trial_storefront_badge',
                    'local_subscriptions'
                ),
                'cartpricetrialdiscountbadge' =>
                    $trialdiscountpercent > 0
                        ? get_string(
                            'commerce_trial_storefront_discount',
                            'local_subscriptions',
                            $trialdiscountpercent
                        )
                        : '',
                'cartpricehastrialbadge' =>
                    $trialdiscountpercent > 0,
                'cartpricepromotionbadge' =>
                    $isupgrade && $promotionpercent > 0
                        ? get_string(
                            'commerce_storefront_discount_percentage',
                            'local_subscriptions',
                            $promotionpercent
                        )
                        : (
                            $hascataloguepromotion
                            && $catalogueprice->get_discount_percentage() !== null
                                ? get_string(
                                    'commerce_storefront_discount_percentage',
                                    'local_subscriptions',
                                    $catalogueprice->get_discount_percentage()
                                )
                                : ''
                        ),
                'cartpricehaspromotionbadge' =>
                    ($isupgrade && $promotionpercent > 0)
                    || $hascataloguepromotion,
                'commercialpromotionpercent' => $promotionpercent,
                'commercialpromotionlabel' =>
                    $promotionpercent > 0
                        ? get_string(
                            'commerce_pricing_initial_promotion_percent',
                            'local_subscriptions',
                            $promotionpercent
                        )
                        : get_string(
                            'commerce_pricing_initial_promotion',
                            'local_subscriptions'
                        ),
                'coverurl' => ($responsivecover = ($product?->get_id() === null
                    ? null
                    : CommerceCatalogResponsiveImageService::create()->resolve($product->get_id(), 'checkout')))['src']
                    ?? $product?->get_cover_url('checkout'),
                'coversrcset' => $responsivecover['srcset'] ?? '',
                'coverresponsive' => $responsivecover !== null,
                'coverwidth' => $responsivecover['width'] ?? 320,
                'coverheight' => $responsivecover['height'] ?? 320,
                'hascover' => ($responsivecover['src'] ?? $product?->get_cover_url('checkout')) !== null,
                'placeholdericon' => CommerceProductVisualAuditService::placeholder_icon(
                    $product?->get_type() ?? match (strtolower(trim((string)(
                        $metadata['producttype'] ?? $metadata['itemtype'] ?? ''
                    )))) {
                        'subscription', 'course', 'course_access' => 'course_access',
                        'digital', 'digital_download' => 'digital_download',
                        'bundle' => 'bundle',
                        default => 'unknown',
                    }
                ),
                'detailsurl' => $product === null
                    ? null
                    : CommerceStorefrontUrlResolver::details($product, $currency)->out(false),
                'hasdetailsurl' => $product !== null,
                'quantity' => $item->get_quantity(),
                'minimumquantity' => $calculated->get_minimum_quantity(),
                'maximumquantity' => $maximum,
                'hasmaximumquantity' => $maximum !== null,
                'quantitystep' => $calculated->get_quantity_step(),
                'quantitylocked' => $maximum === 1,
                'unitpriceformatted' => CommercePurchasePresentation::money(
                    $calculated->get_unit_price()->get_amount_minor(),
                    $currency
                ),
                'subtotalformatted' => CommercePurchasePresentation::money(
                    $calculated->get_subtotal()->get_amount_minor(),
                    $currency
                ),
            ];
        }

        $totals = $snapshot->get_totals();
        $discountminor = $totals->get_discount()->get_amount_minor();
        $metadata = $snapshot->get_cart()->get_metadata();
        $promotioncode = isset($metadata['promotion_code']) ? (string)$metadata['promotion_code'] : '';
        $finaltotalminor = $totals->get_total()->get_amount_minor();
        $totalreductionsminor = max(0, $listtotalminor - $finaltotalminor);

        $adjustments = array_map(static fn($adjustment): array => [
            'name' => format_string($adjustment->get_name()),
            'code' => $adjustment->get_code(),
            'hascode' => $adjustment->get_code() !== null,
            'amountformatted' => CommercePurchasePresentation::money(
                $adjustment->get_amount()->get_amount_minor(),
                $currency
            ),
            'automatic' => $adjustment->is_automatic(),
        ], $snapshot->get_promotion_adjustments());

        return [
            'items' => $items,
            'hasitems' => $items !== [],
            'linecount' => count($items),
            'quantitytotal' => $quantitytotal,
            'currency' => $currency,
            'subtotalformatted' => CommercePurchasePresentation::money(
                $totals->get_subtotal()->get_amount_minor(),
                $currency
            ),
            'listtotalformatted' => CommercePurchasePresentation::money(
                $listtotalminor,
                $currency
            ),
            'totalreductionsformatted' => CommercePurchasePresentation::money(
                $totalreductionsminor,
                $currency
            ),
            'hastotalreductions' => $totalreductionsminor > 0,
            'productpromotiontotalformatted' => CommercePurchasePresentation::money(
                $productpromotiontotalminor,
                $currency
            ),
            'hasproductpromotiontotal' =>
                $productpromotiontotalminor > 0,
            'trialdiscounttotalformatted' =>
                CommercePurchasePresentation::money(
                    $trialdiscounttotalminor,
                    $currency
                ),
            'hastrialdiscounttotal' =>
                $trialdiscounttotalminor > 0,
            'upgradecredittotalformatted' =>
                CommercePurchasePresentation::money(
                    $upgradecredittotalminor,
                    $currency
                ),
            'hasupgradecredittotal' =>
                $upgradecredittotalminor > 0,
            'discountformatted' => CommercePurchasePresentation::money(
                $discountminor,
                $currency
            ),
            'hasdiscount' => $discountminor !== 0,
            'promotionadjustments' => $adjustments,
            'haspromotionadjustments' => $adjustments !== [],
            'promotioncode' => $promotioncode,
            'haspromotioncode' => $promotioncode !== '',
            'totalformatted' => CommercePurchasePresentation::money(
                $totals->get_total()->get_amount_minor(),
                $currency
            ),
            'messages' => array_map(static fn(CommerceCartMessage $message): array => [
                'code' => $message->get_code(),
                'level' => $message->get_level(),
                'text' => get_string('commerce_cart_message_' . $message->get_code(), 'local_subscriptions'),
                'iserror' => $message->get_level() === CommerceCartMessage::LEVEL_ERROR,
                'iswarning' => $message->get_level() === CommerceCartMessage::LEVEL_WARNING,
                'isnotice' => $message->get_level() === CommerceCartMessage::LEVEL_NOTICE,
            ], $snapshot->get_messages()),
        ];
    }

    private static function type_label(string $type): string {
        return match (strtolower(trim($type))) {
            'subscription', 'course_access' => get_string(
                'commerce_product_type_course_access',
                'local_subscriptions'
            ),
            'digital', 'digital_download' => get_string(
                'commerce_product_type_digital_download',
                'local_subscriptions'
            ),
            'bundle' => get_string(
                'commerce_product_type_bundle',
                'local_subscriptions'
            ),
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }
}
