<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\mail;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductPriceRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\personaloffer\repository\MoodleCommercePersonalOfferRepository;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferCheckoutPricingService;

/** Builds one server-authoritative price presentation for a Personal Offer email. */
final class CommercePersonalOfferMailPricingPresentationService {
    public function __construct(private readonly \moodle_database $db) {}
    public static function create(?\moodle_database $db = null): self { global $DB; return new self($db ?? $DB); }

    /** @return array<string,mixed>|null */
    public function resolve(string $offeruuid, string $language): ?array {
        $offers = new MoodleCommercePersonalOfferRepository($this->db);
        $offer = $offers->get_by_uuid($offeruuid);
        if ($offer === null) { return null; }

        $hydrator = new CommerceCatalogHydrator();
        $products = new CommerceProductRepository($this->db, $hydrator);
        $prices = new CommerceProductPriceRepository($this->db, $hydrator, $products);
        $product = $products->find_by_id($offer->get_target_product_id());
        if ($product === null) { return null; }

        $catalogue = [];
        foreach ($prices->find_by_product_sku($product->get_sku(), true) as $price) {
            $currency = strtoupper($price->get_currency());
            if (!in_array($currency, ['EUR', 'RUB'], true)) { continue; }
            // Match CommercePersonalOfferCheckoutService::prepare(): a provider-neutral
            // catalogue price wins; otherwise keep the first active candidate.
            if (!isset($catalogue[$currency]) || $price->get_provider() === null) {
                $catalogue[$currency] = $price;
            }
        }
        $available = [];
        foreach ($catalogue as $currency => $price) {
            try {
                $offerminor = CommercePersonalOfferCheckoutPricingService::create($this->db)->resolve_unit_minor(
                    $offeruuid, $product->get_sku(), $currency, $price->get_amount_minor()
                );
            } catch (\Throwable) { continue; }
            $available[$currency] = ['regularminor' => $price->get_amount_minor(), 'offerminor' => $offerminor];
        }
        if ($available === []) { return null; }

        $currency = '';
        if ($offer->get_source_purchase_id()) {
            $purchasecurrency = strtoupper((string)$this->db->get_field(
                'local_subscriptions_commerce_purchase', 'currency', ['id' => $offer->get_source_purchase_id()], IGNORE_MISSING
            ));
            if (isset($available[$purchasecurrency])) { $currency = $purchasecurrency; }
        }
        if ($currency === '') {
            $base = strtolower(explode('_', str_replace('-', '_', trim($language)), 2)[0]);
            $preferred = $base === 'ru' ? 'RUB' : 'EUR';
            $currency = isset($available[$preferred]) ? $preferred : (string)array_key_first($available);
        }

        return CommercePersonalOfferPricingPresentationBuilder::build($available, $currency);
    }
}
