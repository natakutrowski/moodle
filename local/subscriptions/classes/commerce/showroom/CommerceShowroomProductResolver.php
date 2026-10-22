<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom;

use local_subscriptions\commerce\showroom\cms\CommerceShowroomOfferConfig;
defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\assets\CommerceCatalogMediaManager;
use local_subscriptions\commerce\storefront\presentation\CommerceStorefrontPresenter;
use local_subscriptions\commerce\storefront\repository\CommerceStorefrontRepository;
use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontPrice;
use local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation;
use local_subscriptions\url\UrlFactory;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferShoppingContextService;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\donation\CommerceDonationAmountPolicy;
use local_subscriptions\commerce\donation\CommerceDonationIntentSession;
use local_subscriptions\commerce\cart\repository\CommerceSessionCartRepository;
use local_subscriptions\commerce\cart\service\CommerceCartSessionKeyResolver;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinOperation;

/** Resolves products attached to a Showroom while keeping incomplete catalogues non-fatal. */
final class CommerceShowroomProductResolver {
    public function __construct(private readonly CommerceStorefrontRepository $repository) {
    }

    public static function create(\moodle_database $db): self {
        return new self(CommerceStorefrontRepository::create($db));
    }

    /** @return array<int,array<string,mixed>> */
    public function resolve(
        CommerceShowroomDefinition $definition,
        string $language,
        string $currency
    ): array {
        $offers = [];
        $products = $definition->get_products();
        $roles = ['pdf', 'bundle', 'course'];

        foreach ($roles as $role) {
            if (!isset($products[$role])) {
                continue;
            }
            $offers[] = $this->resolve_offer(
                $definition,
                $role,
                $products[$role],
                $language,
                $currency
            );
            unset($products[$role]);
        }

        // Preserve forward compatibility for any future custom roles.
        foreach ($products as $role => $sku) {
            $offers[] = $this->resolve_offer(
                $definition,
                (string)$role,
                (string)$sku,
                $language,
                $currency
            );
        }
        $offers = $this->apply_bundle_merchandising_price($offers, $currency);
        $offers = $this->apply_bundle_ownership_rules($offers);
        $offers = $this->apply_personal_offer_pricing($definition, $offers, $currency);
        return $this->apply_donation_merchandising($definition, $offers);
    }

    /** @return array<string,mixed> */
    private function resolve_offer(
        CommerceShowroomDefinition $definition,
        string $role,
        string $sku,
        string $language,
        string $currency
    ): array {
        try {
            $product = $this->repository->find_by_sku($sku, $language, $currency, true);
        } catch (\Throwable) {
            $product = null;
        }

        $rolelabel = $this->role_label($role);
        $base = [
            'role' => $role,
            'rolelabel' => $rolelabel,
            'showroomkey' => $definition->get_key(),
            'sku' => $sku,
            'available' => false,
            'owned' => false,
            'canbuy' => false,
            'canaccess' => false,
            'name' => $rolelabel,
            'shortdescription' => get_string('commerce_showroom_offer_pending', 'local_subscriptions'),
            'hascover' => false,
            'coverurl' => '',
            'coverresponsive' => false,
            'coversrcset' => '',
            'placeholdericon' => match ($role) {
                'course' => 'fa-solid fa-graduation-cap',
                'pdf' => 'fa-solid fa-file-arrow-down',
                'bundle' => 'fa-solid fa-boxes-stacked',
                default => 'fa-solid fa-box',
            },
            'hasprice' => false,
            'amountminor' => null,
            'compareamountminor' => null,
            'priceformatted' => get_string('commerce_showroom_price_pending', 'local_subscriptions'),
            'compareformatted' => '',
            'hascompareprice' => false,
            'haspromotion' => false,
            'discountlabel' => '',
            'haspromotionend' => false,
            'promotionendlabel' => '',
            'priceid' => 0,
            'detailsurl' => UrlFactory::digital_catalog(['currency' => $currency])->out(false),
            'ownedactionurl' => '',
            'ownedactionlabel' => get_string('commerce_showroom_owned_access', 'local_subscriptions'),
            'componentskus' => [],
            'bundleblocked' => false,
            'bundleblockedmessage' => '',
            'cartaction' => (new \moodle_url('/local/subscriptions/cart_action.php'))->out(false),
            'sesskey' => sesskey(),
            'currency' => $currency,
            'returnurl' => CommerceShowroomUrl::make($definition, ['currency' => $currency])->out(false),
            'buylabel' => get_string('commerce_showroom_buy_now', 'local_subscriptions'),
            'detailslabel' => get_string('commerce_showroom_view_details', 'local_subscriptions'),
            'showdetails' => $definition->is_offer_details_enabled($role),
            'cataloguetype' => '',
            'isdonation' => false,
            'donationmaxunits' => 0,
            'donationdefaultunits' => 0,
            'donationpresets' => [],
            'hasdonationcompanion' => false,
            'donationcompanionoptional' => false,
            'donationcompanionrequired' => false,
            // Existing owners can buy the pedagogical accompaniment without
            // repurchasing the course. These fields mirror the authoritative
            // Storefront promotion_join contract.
            'haspromotionjoincontext' => false,
            'promotionjoineligible' => false,
            'promotionjoinreason' => null,
            'promotionjoinoperation' => CommercePedagogicalPromotionJoinOperation::OPERATION,
            'promotionjoinpromotionid' => null,
            'promotionjoinpromotionkey' => '',
            'promotionjoinpromotionname' => '',
            'promotionjoincourseid' => null,
            'promotionjoinownershipsource' => null,
            'promotionjoinremaining' => null,
            'promotionjoinpurchasable' => false,
            'promotionjoinpriceformatted' => '',
            'promotionjoinpriceid' => null,
            'promotionjoincatalogpriceid' => null,
            'promotionjoinactionlabel' => get_string(
                'commerce_promotion_join_action',
                'local_subscriptions'
            ),
            'promotionjoinactionpricelabel' => get_string(
                'commerce_promotion_join_action',
                'local_subscriptions'
            ),
            'promotionjoinownerpricelabel' => get_string(
                'commerce_m62_owner_price_label',
                'local_subscriptions'
            ),
            'promotionjoinownerpricebadge' => get_string(
                'commerce_m62_owner_price_badge',
                'local_subscriptions'
            ),
            'promotionjoinownerpricehelp' => get_string(
                'commerce_m62_owner_price_help',
                'local_subscriptions'
            ),
            'promotionjoinincart' => false,
            'carturl' => UrlFactory::cart(['currency' => $currency])->out(false),
            'cartlabel' => get_string('commerce_cart_title', 'local_subscriptions'),
            'hasaccesspromise' => false,
            'accesspromisekind' => '',
            'accesspromisepromotionlabel' => '',
            'hasaccesspromisepromotion' => false,
        ];

        if ($product === null) {
            return $base;
        }

        $card = CommerceStorefrontPresenter::card($product, $currency);
        $metadata = $product->get_metadata();
        $showroommedia = CommerceShowroomMediaService::create()->definition(
            is_array($metadata) ? $metadata : [],
            $language
        );
        $catalogshowroomurl = trim((string)$product->get_cover_url('showroom'));
        $legacyshowroomurl = !empty($showroommedia['hasimage'])
            ? trim((string)$showroommedia['imageurl'])
            : '';
        $wideurl = trim((string)$product->get_cover_url('product'));
        $storefronturl = trim((string)$product->get_cover_url('storefront'));
        $resolvedcoverurl = $catalogshowroomurl !== ''
            ? $catalogshowroomurl
            : ($legacyshowroomurl !== ''
                ? $legacyshowroomurl
                : ($wideurl !== '' ? $wideurl : $storefronturl));
        $responsivecover = null;
        $productid = (int)($product->get_id() ?? 0);
        if ($productid > 0 && $catalogshowroomurl !== '') {
            $responsivecover = (new CommerceCatalogMediaManager(\context_system::instance()))
                ->get_showroom_responsive_urls($productid);
        }
        $price = $this->matching_price((array)($card['prices'] ?? []), $currency);
        $pricemodel = $this->matching_price_model($product->get_prices(), $currency);
        $owned = !empty($card['owned']);
        $priceid = is_array($price) ? (int)($price['id'] ?? 0) : 0;

        return array_replace($base, [
            'available' => true,
            'owned' => $owned,
            'canbuy' => !$owned && $priceid > 0,
            'canaccess' => $owned && trim((string)($card['ownedactionurl'] ?? '')) !== '',
            'name' => (string)$card['name'],
            'shortdescription' => (string)$card['shortdescription'],
            'hascover' => $resolvedcoverurl !== '',
            'coverurl' => $responsivecover['src'] ?? $resolvedcoverurl,
            'coverresponsive' => $responsivecover !== null,
            'coversrcset' => $responsivecover['srcset'] ?? '',
            'coveralt' => $legacyshowroomurl !== '' && trim((string)$showroommedia['alt']) !== ''
                ? (string)$showroommedia['alt']
                : (string)$card['name'],
            'placeholdericon' => (string)($card['placeholdericon'] ?? $base['placeholdericon']),
            'hasprice' => $priceid > 0,
            'amountminor' => $pricemodel?->get_amount_minor(),
            'compareamountminor' => $pricemodel?->get_compare_amount_minor(),
            'priceformatted' => is_array($price)
                ? (string)($price['formatted'] ?? $base['priceformatted'])
                : $base['priceformatted'],
            'compareformatted' => is_array($price)
                ? (string)($price['compareformatted'] ?? '')
                : '',
            'hascompareprice' => is_array($price) && trim((string)($price['compareformatted'] ?? '')) !== '',
            'haspromotion' => is_array($price) && !empty($price['haspromotion']),
            'discountlabel' => is_array($price) ? (string)($price['discountlabel'] ?? '') : '',
            'haspromotionend' => is_array($price) && !empty($price['haspromotionend']),
            'promotionendlabel' => $pricemodel?->get_promotion_end() !== null
                ? $this->short_promotion_end_label($pricemodel->get_promotion_end())
                : '',
            'priceid' => $priceid,
            'detailsurl' => $this->tracking_url(
                \local_subscriptions\commerce\storefront\presentation\CommerceStorefrontUrlResolver::direct_storefront(
                    $product,
                    $currency
                )->out(false),
                $definition->get_key(),
                $role
            ),
            'ownedactionurl' => (string)($card['ownedactionurl'] ?? ''),
            'ownedactionlabel' => (string)($card['ownedactionlabel'] ?? $base['ownedactionlabel']),
            'cataloguetype' => $product->get_type(),
            'isdonation' => $product->get_type() === CommerceProductType::DONATION,
            'componentskus' => array_values(array_filter(array_map(
                static fn(array $component): string => strtoupper(trim((string)($component['sku'] ?? ''))),
                $product->get_components()
            ))),
            'haspromotionjoincontext' => !empty($card['haspromotionjoincontext']),
            'promotionjoineligible' => !empty($card['promotionjoineligible']),
            'promotionjoinreason' => $card['promotionjoinreason'] ?? null,
            'promotionjoinoperation' => (string)(
                $card['promotionjoinoperation']
                    ?? CommercePedagogicalPromotionJoinOperation::OPERATION
            ),
            'promotionjoinpromotionid' => $card['promotionjoinpromotionid'] ?? null,
            'promotionjoinpromotionkey' => (string)($card['promotionjoinpromotionkey'] ?? ''),
            'promotionjoinpromotionname' => (string)($card['promotionjoinpromotionname'] ?? ''),
            'promotionjoincourseid' => $card['promotionjoincourseid'] ?? null,
            'promotionjoinownershipsource' => $card['promotionjoinownershipsource'] ?? null,
            'promotionjoinremaining' => $card['promotionjoinremaining'] ?? null,
            'promotionjoinpurchasable' => !empty($card['promotionjoinpurchasable']),
            'promotionjoinpriceformatted' => (string)($card['promotionjoinpriceformatted'] ?? ''),
            'promotionjoinpriceid' => $card['promotionjoinpriceid'] ?? null,
            'promotionjoincatalogpriceid' => $card['promotionjoincatalogpriceid'] ?? null,
            'promotionjoinactionlabel' => (string)(
                $card['promotionjoinactionlabel']
                    ?? $base['promotionjoinactionlabel']
            ),
            'promotionjoinactionpricelabel' => (string)(
                $card['promotionjoinactionpricelabel']
                    ?? $base['promotionjoinactionpricelabel']
            ),
            'promotionjoinownerpricelabel' => (string)(
                $card['promotionjoinownerpricelabel']
                    ?? $base['promotionjoinownerpricelabel']
            ),
            'promotionjoinownerpricebadge' => (string)(
                $card['promotionjoinownerpricebadge']
                    ?? $base['promotionjoinownerpricebadge']
            ),
            'promotionjoinownerpricehelp' => (string)(
                $card['promotionjoinownerpricehelp']
                    ?? $base['promotionjoinownerpricehelp']
            ),
            'promotionjoinincart' => $this->promotion_join_in_cart(
                $sku,
                $currency
            ),
            'hasaccesspromise' => !empty($card['hasaccesspromise']),
            'accesspromisekind' => (string)($card['accesspromisekind'] ?? ''),
            'accesspromisepromotionlabel' => (string)($card['accesspromisepromotionlabel'] ?? ''),
            'hasaccesspromisepromotion' => !empty($card['hasaccesspromisepromotion']),
        ]);
    }




    /**
     * Adds generic Donation controls without knowing any product/showroom key.
     *
     * A Donation offer gets its own free-amount selector. When the same
     * Showroom also contains a normal purchasable offer, that offer receives an
     * optional Donation companion configuration so "course + contribution" can
     * be submitted as two independent Commerce lines.
     *
     * @param array<int,array<string,mixed>> $offers
     * @return array<int,array<string,mixed>>
     */
    private function apply_donation_merchandising(CommerceShowroomDefinition $definition, array $offers): array {
        $policy = new CommerceDonationAmountPolicy();
        $intent = new CommerceDonationIntentSession($policy);
        $maxunits = $policy->max_units();
        $presets = $policy->preset_units();

        $donation = null;
        foreach ($offers as &$offer) {
            if (empty($offer['isdonation'])) {
                continue;
            }
            $offer['donationmaxunits'] = $maxunits;
            $offer['donationdefaultunits'] = $intent->resolve((string)$offer['sku']);
            $offer['donationpresets'] = array_map(
                static fn(int $units): array => ['units' => $units],
                $presets
            );
            $offer['buylabel'] = get_string('commerce_donation_contribute', 'local_subscriptions');
            if (!empty($offer['available']) && !empty($offer['priceid'])) {
                $donation = $offer;
            }
        }
        unset($offer);

        if ($donation === null) {
            return $offers;
        }

        foreach ($offers as &$offer) {
            if (!empty($offer['isdonation']) || empty($offer['canbuy'])) {
                continue;
            }
            $mode = $definition->get_offer_donation_mode((string)$offer['role']);
            if ($mode === CommerceShowroomOfferConfig::DONATION_MODE_NONE) {
                continue;
            }
            $offer['hasdonationcompanion'] = true;
            $offer['donationcompanionoptional'] = $mode === CommerceShowroomOfferConfig::DONATION_MODE_OPTIONAL;
            $offer['donationcompanionrequired'] = $mode === CommerceShowroomOfferConfig::DONATION_MODE_REQUIRED;
            $offer['donationcompanionsku'] = (string)$donation['sku'];
            $offer['donationcompanionpriceid'] = (int)$donation['priceid'];
            $offer['donationcompanionrole'] = (string)$donation['role'];
            $offer['donationcompanionamountminor'] = (int)($donation['amountminor'] ?? 0);
            $offer['donationcompanioncurrency'] = (string)$donation['currency'];
            $offer['donationmaxunits'] = $maxunits;
            $offer['donationdefaultunits'] = $intent->resolve((string)$donation['sku']);
            $offer['donationpresets'] = array_map(
                static fn(int $units): array => ['units' => $units],
                $presets
            );
        }
        unset($offer);
        return $offers;
    }

    /**
     * Human-readable fallback for arbitrary Showroom product roles.
     *
     * Legacy roles keep their translated labels. New CMS-defined roles must
     * not require a PHP language string merely to pass through the generic
     * Showroom engine; the Builder can still override rolelabel editorially.
     */
    private function role_label(string $role): string {
        $role = strtolower(trim($role));
        return match ($role) {
            'course', 'pdf', 'bundle' => get_string(
                'commerce_showroom_offer_' . $role,
                'local_subscriptions'
            ),
            default => ucwords(str_replace(['_', '-'], ' ', $role)),
        };
    }

    /**
     * Compact deadline used only on showroom cards; the storefront keeps its full wording.
     */
    private function short_promotion_end_label(int $promotionend): string {
        $timezone = 'Europe/Paris';
        $promotionyear = (int)userdate($promotionend, '%Y', $timezone);
        $currentyear = (int)userdate(time(), '%Y', $timezone);
        $dateformat = $promotionyear === $currentyear ? '%e %b' : '%e %b %Y';

        return get_string(
            'commerce_showroom_promotion_until_short',
            'local_subscriptions',
            (object)[
                'date' => trim(userdate($promotionend, $dateformat, $timezone)),
                'time' => userdate($promotionend, '%H:%M', $timezone),
            ]
        );
    }

    /**
     * Presents the bundle saving against the current sum of its course and PDF.
     *
     * @param array<int,array<string,mixed>> $offers
     * @return array<int,array<string,mixed>>
     */
    private function apply_bundle_merchandising_price(array $offers, string $currency): array {
        $indexes = [];
        foreach ($offers as $index => $offer) {
            $role = (string)($offer['role'] ?? '');
            if ($role !== '') {
                $indexes[$role] = $index;
            }
        }

        if (!isset($indexes['course'], $indexes['pdf'], $indexes['bundle'])) {
            return $offers;
        }

        $courseamount = $offers[$indexes['course']]['amountminor'] ?? null;
        $pdfamount = $offers[$indexes['pdf']]['amountminor'] ?? null;
        $bundleamount = $offers[$indexes['bundle']]['amountminor'] ?? null;
        if (!is_int($courseamount) || !is_int($pdfamount) || !is_int($bundleamount)) {
            return $offers;
        }

        $combinedamount = $courseamount + $pdfamount;
        $bundleindex = $indexes['bundle'];

        // A bundle can have two honest comparison references:
        // 1) its own active Storefront compare price;
        // 2) the current sum of the component products.
        // Always expose the reference producing the largest real discount. Because the sale
        // price is identical for both candidates, that is simply the highest valid reference.
        $configuredcompare = $offers[$bundleindex]['compareamountminor'] ?? null;
        $candidates = [];
        if (is_int($configuredcompare) && $configuredcompare > $bundleamount) {
            $candidates[] = $configuredcompare;
        }
        if ($combinedamount > $bundleamount) {
            $candidates[] = $combinedamount;
        }

        if ($candidates === []) {
            $offers[$bundleindex]['compareformatted'] = '';
            $offers[$bundleindex]['compareamountminor'] = null;
            $offers[$bundleindex]['hascompareprice'] = false;
            $offers[$bundleindex]['haspromotion'] = false;
            $offers[$bundleindex]['discountlabel'] = '';
            return $offers;
        }

        $bestcompare = max($candidates);
        $discount = (int)round((($bestcompare - $bundleamount) / $bestcompare) * 100);
        $offers[$bundleindex]['compareamountminor'] = $bestcompare;
        $offers[$bundleindex]['compareformatted'] = CommercePurchasePresentation::money(
            $bestcompare,
            $currency
        );
        $offers[$bundleindex]['hascompareprice'] = true;
        $offers[$bundleindex]['haspromotion'] = $discount > 0;
        $offers[$bundleindex]['discountlabel'] = $discount > 0
            ? get_string('commerce_storefront_discount_percentage', 'local_subscriptions', $discount)
            : '';
        return $offers;
    }

    /**
     * Prevents a customer from buying a bundle containing an item already owned.
     *
     * @param array<int,array<string,mixed>> $offers
     * @return array<int,array<string,mixed>>
     */
    private function apply_bundle_ownership_rules(array $offers): array {
        $ownedskus = [];
        foreach ($offers as $offer) {
            if (!empty($offer['owned'])) {
                $ownedskus[] = strtoupper(trim((string)($offer['sku'] ?? '')));
            }
        }
        $ownedskus = array_values(array_filter($ownedskus));

        foreach ($offers as &$offer) {
            if ((string)($offer['role'] ?? '') !== 'bundle' || !empty($offer['owned'])) {
                continue;
            }
            $components = array_map('strtoupper', (array)($offer['componentskus'] ?? []));
            $ownedcomponents = array_values(array_intersect($components, $ownedskus));
            if ($ownedcomponents === []) {
                continue;
            }
            $offer['canbuy'] = false;
            $offer['cannotbuy'] = true;
            $offer['bundleblocked'] = true;
            $offer['bundleblockedmessage'] = get_string(
                'commerce_showroom_bundle_partial_owned',
                'local_subscriptions'
            );
        }
        unset($offer);
        return $offers;
    }



    /** @param array<int,array<string,mixed>> $offers @return array<int,array<string,mixed>> */
    private function apply_personal_offer_pricing(
        CommerceShowroomDefinition $definition,
        array $offers,
        string $currency
    ): array {
        $service = CommercePersonalOfferShoppingContextService::create();
        foreach ($offers as &$offer) {
            if (empty($offer['available']) || empty($offer['priceid'])) {
                continue;
            }
            $personal = $service->resolve((string)($offer['sku'] ?? ''), $currency, $definition->get_key());
            if ($personal === null) {
                continue;
            }
            $offer['amountminor'] = $personal['offeramountminor'];
            $offer['compareamountminor'] = $personal['regularamountminor'];
            $offer['priceformatted'] = $personal['offerformatted'];
            $offer['compareformatted'] = $personal['hasdiscount'] ? $personal['regularformatted'] : '';
            $offer['hascompareprice'] = $personal['hasdiscount'];
            $offer['haspromotion'] = $personal['hasdiscount'];
            $offer['discountlabel'] = $personal['discountlabel'];
            $offer['ispersonaloffer'] = true;
            $offer['personalofferbadge'] = get_string(
                'commerce_personal_offer_checkout_badge',
                'local_subscriptions'
            );
        }
        unset($offer);
        return $offers;
    }

    private function tracking_url(string $url, string $showroomkey, string $role): string {
        $target = new \moodle_url($url);
        $target->params([
            'source' => 'showroom',
            'showroom' => $showroomkey,
            'showroomoffer' => $role,
        ]);
        return $target->out(false);
    }

    private function promotion_join_in_cart(string $sku, string $currency): bool {
        global $USER;

        if (!isloggedin() || isguestuser()) {
            return false;
        }

        $currency = strtoupper(trim($currency));
        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            return false;
        }

        $cart = (new CommerceSessionCartRepository())->find(
            (new CommerceCartSessionKeyResolver())->resolve(
                (int)$USER->id,
                $currency
            )
        );
        if ($cart === null) {
            return false;
        }

        $sku = strtoupper(trim($sku));
        foreach ($cart->get_items() as $item) {
            if ($item->get_product_sku() !== $sku) {
                continue;
            }
            if (
                strtolower(trim((string)(
                    $item->get_metadata()['operation'] ?? ''
                ))) === CommercePedagogicalPromotionJoinOperation::OPERATION
            ) {
                return true;
            }
        }

        return false;
    }

    /** @param array<int,CommerceStorefrontPrice> $prices */
    private function matching_price_model(array $prices, string $currency): ?CommerceStorefrontPrice {
        foreach ($prices as $price) {
            if (strtoupper($price->get_currency()) === strtoupper($currency)) {
                return $price;
            }
        }
        return null;
    }

    /** @param array<int,array<string,mixed>> $prices @return array<string,mixed>|null */
    private function matching_price(array $prices, string $currency): ?array {
        foreach ($prices as $price) {
            if (strtoupper((string)($price['currency'] ?? '')) === strtoupper($currency)) {
                return $price;
            }
        }
        return null;
    }
}
