<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\presentation;

use local_subscriptions\commerce\catalog\assets\CommerceCatalogResponsiveImageService;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontPrice;
use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontProduct;
use local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation;
use local_subscriptions\commerce\catalog\visual\CommerceProductVisualAuditService;

/** Maps Storefront read models to public-safe Mustache data. */
final class CommerceStorefrontPresenter {
    /** @return array<string, mixed> */
    public static function card(CommerceStorefrontProduct $product, ?string $currency, string $covercontext = 'storefront'): array {
        $prices = array_map(
            static function(CommerceStorefrontPrice $price): array {
                $promotionend = $price->get_promotion_end();
                return [
                    'id' => $price->get_id(),
                    'canaddtocart' => $price->get_id() !== null,
                    'currency' => $price->get_currency(),
                    'formatted' => CommercePurchasePresentation::money(
                        $price->get_amount_minor(),
                        $price->get_currency()
                    ),
                    'haspromotion' => $price->has_active_promotion(),
                    'compareformatted' => $price->get_compare_amount_minor() === null
                        ? null
                        : CommercePurchasePresentation::money(
                            $price->get_compare_amount_minor(),
                            $price->get_currency()
                        ),
                    'discountlabel' => $price->get_discount_percentage() === null
                        ? null
                        : get_string(
                            'commerce_storefront_discount_percentage',
                            'local_subscriptions',
                            $price->get_discount_percentage()
                        ),
                    'haspromotionend' => $promotionend !== null,
                    'promotionendlabel' => $promotionend === null
                        ? null
                        : get_string(
                            'commerce_storefront_promotion_until',
                            'local_subscriptions',
                            userdate($promotionend, get_string('strftimedate', 'langconfig'))
                        ),
                ];
            },
            $product->get_prices()
        );

        $badges = [];
        foreach ($product->get_badges() as $key) {
            $badges[] = [
                'key' => $key,
                'label' => get_string('commerce_storefront_badge_' . $key, 'local_subscriptions'),
                'class' => self::badge_class($key),
                'icon' => self::badge_icon($key),
                'hascustomicon' => $key === 'gustave_choice' && self::gustave_icon_url() !== null,
                'customiconurl' => $key === 'gustave_choice' ? self::gustave_icon_url() : null,
                'isgustave' => $key === 'gustave_choice',
            ];
        }

        $haspromotion = array_reduce(
            $prices,
            static fn(bool $carry, array $price): bool => $carry || !empty($price['haspromotion']),
            false
        );
        if ($haspromotion && !in_array('limited_offer', $product->get_badges(), true)) {
            array_unshift($badges, [
                'key' => 'promotion',
                'label' => get_string('commerce_storefront_badge_promotion', 'local_subscriptions'),
                'class' => 'text-bg-danger',
                'icon' => self::badge_icon('promotion'),
                'hascustomicon' => false,
                'customiconurl' => null,
                'isgustave' => false,
            ]);
        }

        $trustitems = array_map(
            static fn(string $key): array => [
                'key' => $key,
                'label' => get_string('commerce_storefront_trust_' . $key, 'local_subscriptions'),
            ],
            $product->get_trust_items()
        );
        $owned = $product->is_owned();
        // A product already owned must never expose a simultaneous upgrade CTA.
        $upgrade = $product->is_owned() ? null : $product->get_upgrade();

        $upgradepriceid = null;
        if ($upgrade !== null) {
            foreach ($product->get_prices() as $candidateprice) {
                if ($candidateprice->get_currency() === $upgrade->get_currency() && $candidateprice->get_id() !== null) {
                    $upgradepriceid = (int)$candidateprice->get_id();
                    break;
                }
            }
        }

        $responsivecover = $product->get_id() === null
            ? null
            : CommerceCatalogResponsiveImageService::create()->resolve(
                $product->get_id(),
                $covercontext
            );

        return [
            'sku' => $product->get_sku(),
            'name' => format_string($product->get_name()),
            'shortdescription' => format_text(
                $product->get_short_description() !== ''
                    ? $product->get_short_description()
                    : $product->get_description(),
                FORMAT_HTML,
                ['para' => false]
            ),
            'typelabel' => self::type_label($product->get_type()),
            'showtypebadge' => true,
            'badgecontainerclass' => 'mb-3',
            'coverurl' => $responsivecover['src'] ?? $product->get_cover_url($covercontext),
            'coversrcset' => $responsivecover['srcset'] ?? '',
            'coverresponsive' => $responsivecover !== null,
            'coverwidth' => $responsivecover['width'] ?? 800,
            'coverheight' => $responsivecover['height'] ?? 600,
            'hascover' => ($responsivecover['src'] ?? $product->get_cover_url($covercontext)) !== null,
            'placeholderratio' => 'landscape',
            'placeholdericon' =>
                CommerceProductVisualAuditService::placeholder_icon(
                    $product->get_type()
                ),
            'producttype' => $product->get_type(),
            'prices' => $prices,
            'hasprices' => $prices !== [],
            'badges' => $badges,
            'hasbadges' => $badges !== [],
            'featured' => $product->is_featured(),
            'cardcolumnclass' => $product->is_featured() ? 'col-12' : 'col-md-6 col-xl-4',
            'detailsurl' => CommerceStorefrontUrlResolver::details($product, $currency)->out(false),
            'quickpurchaseurl' => CommerceStorefrontUrlResolver::quick_purchase($product, $currency)->out(false),
            'quickpurchaseeligible' => $product->is_quick_purchase_eligible(),
            'quickpurchaselabel' => get_string('commerce_storefront_buy_now', 'local_subscriptions'),
            'detailslabel' => get_string('commerce_storefront_discover', 'local_subscriptions'),
            'featuredlabel' => get_string('commerce_storefront_featured', 'local_subscriptions'),
            'group' => $product->get_group(),
            'quickfacts' => $product->get_quick_facts(),
            'hasquickfacts' => $product->get_quick_facts() !== [],
            'trustitems' => $trustitems,
            'hastrustitems' => $trustitems !== [],
            'owned' => $owned,
            'hasupgrade' => $upgrade !== null,
            'upgradepriceformatted' => $upgrade === null ? null : CommercePurchasePresentation::money(
                $upgrade->get_amount_minor(),
                $upgrade->get_currency()
            ),
            'upgradefromlabel' => $upgrade?->get_from_label(),
            'upgradetolabel' => $upgrade?->get_to_label(),
            'hasupgradepath' => $upgrade !== null
                && trim($upgrade->get_from_label()) !== ''
                && trim($upgrade->get_to_label()) !== '',
            'upgradesummary' => $upgrade?->get_summary(),
            'upgradelabel' => get_string('upgrade_badge', 'local_subscriptions'),
            'upgradeactionlabel' => get_string('upgrade_cta', 'local_subscriptions'),
            'upgradeactionurl' => null,
            'upgradepriceid' => $upgradepriceid,
            'canaddupgradetocart' => $upgrade !== null && $upgradepriceid !== null,
            'upgradetargetplanid' => $upgrade?->get_to_plan_id(),
            'ownedlabel' => get_string('commerce_storefront_owned', 'local_subscriptions'),
            'ownedactionlabel' =>
                self::owned_action_label($product->get_type()),
            'ownedactionurl' =>
                CommerceStorefrontUrlResolver::owned_access($product)
                    ->out(false),
            'ownediscourse' => in_array(
                $product->get_type(),
                ['subscription', 'course_access'],
                true
            ),
            'ownedisdigital' => in_array(
                $product->get_type(),
                ['digital', 'digital_download'],
                true
            ),
            'canpurchase' => !$owned && $upgrade === null,
            'addtocartlabel' => get_string('commerce_cart_add', 'local_subscriptions'),
            'alreadyownedlabel' => get_string('commerce_cart_already_owned', 'local_subscriptions'),
        ];
    }

    public static function group_label(string $group): string {
        return get_string('commerce_storefront_group_' . $group, 'local_subscriptions');
    }

    public static function group_intro(string $group): string {
        return get_string('commerce_storefront_group_' . $group . '_intro', 'local_subscriptions');
    }

    private static function owned_action_label(string $type): string {
        return match ($type) {
            'subscription', 'course_access' => get_string(
                'commerce_storefront_access_course',
                'local_subscriptions'
            ),
            'digital', 'digital_download' => get_string(
                'commerce_storefront_view_my_products',
                'local_subscriptions'
            ),
            'bundle' => get_string(
                'commerce_storefront_access_bundle_contents',
                'local_subscriptions'
            ),
            default => get_string(
                'commerce_storefront_access_purchase',
                'local_subscriptions'
            ),
        };
    }

    private static function gustave_icon_url(): ?string {
        global $CFG;
        $path = $CFG->dirroot . '/local/subscriptions/pix/storefront/badges/gustave_choice.svg';
        if (!is_file($path)) {
            return null;
        }
        return (new \moodle_url('/local/subscriptions/pix/storefront/badges/gustave_choice.svg'))->out(false);
    }

    private static function badge_icon(string $key): string {
        return match ($key) {
            'new' => '✨', 'best_seller' => '🏆', 'popular' => '🔥',
            'limited' => '⏳', 'promotion' => '−%', 'premium' => '◆',
            'lifetime' => '∞', 'complete' => '🎓', 'gustave_choice' => '🦒',
            default => '•',
        };
    }

    private static function badge_class(string $key): string {
        return match ($key) {
            'new' => 'text-bg-info',
            'bestseller', 'popular' => 'text-bg-warning',
            'limited_offer' => 'text-bg-danger',
            'gustave_choice' => 'text-bg-success',
            'premium' => 'text-bg-dark',
            'lifetime_access', 'complete_course' => 'text-bg-primary',
            default => 'text-bg-secondary',
        };
    }

    private static function type_label(string $type): string {
        return match ($type) {
            'subscription', 'course_access' => get_string('commerce_product_type_course_access', 'local_subscriptions'),
            'digital', 'digital_download' => get_string('commerce_product_type_digital_download', 'local_subscriptions'),
            'bundle' => get_string('commerce_product_type_bundle', 'local_subscriptions'),
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }
}
