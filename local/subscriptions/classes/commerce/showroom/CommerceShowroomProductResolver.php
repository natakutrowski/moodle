<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\assets\CommerceCatalogMediaManager;
use local_subscriptions\commerce\storefront\presentation\CommerceStorefrontPresenter;
use local_subscriptions\commerce\storefront\repository\CommerceStorefrontRepository;
use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontPrice;
use local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation;
use local_subscriptions\url\UrlFactory;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferShoppingContextService;

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
        return $this->apply_personal_offer_pricing($definition, $offers, $currency);
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

        $base = [
            'role' => $role,
            'rolelabel' => get_string('commerce_showroom_offer_' . $role, 'local_subscriptions'),
            'showroomkey' => $definition->get_key(),
            'sku' => $sku,
            'available' => false,
            'owned' => false,
            'canbuy' => false,
            'canaccess' => false,
            'name' => get_string('commerce_showroom_offer_' . $role, 'local_subscriptions'),
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
            'promotionendlabel' => is_array($price) ? (string)($price['promotionendlabel'] ?? '') : '',
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
            'componentskus' => array_values(array_filter(array_map(
                static fn(array $component): string => strtoupper(trim((string)($component['sku'] ?? ''))),
                $product->get_components()
            ))),
        ]);
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
