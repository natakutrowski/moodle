<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\repository;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\cover\CommerceProductCoverContext;
use local_subscriptions\commerce\catalog\cover\CommerceProductCoverService;
use local_subscriptions\commerce\catalog\readmodel\CommerceCatalogProductDetails;
use local_subscriptions\commerce\catalog\readmodel\CommerceCatalogProductSummary;
use local_subscriptions\commerce\catalog\readmodel\CommerceCatalogReadRepository;
use local_subscriptions\commerce\catalog\status\CommerceCatalogAvailability;
use local_subscriptions\commerce\catalog\status\CommerceCatalogEditorialStatus;
use local_subscriptions\commerce\catalog\status\CommerceCatalogTechnicalState;
use local_subscriptions\commerce\catalog\status\CommerceCatalogVisibility;
use local_subscriptions\commerce\storefront\merchandising\CommerceStorefrontMerchandisingResolver;
use local_subscriptions\commerce\storefront\merchandising\CommerceStorefrontPromotionResolver;
use local_subscriptions\commerce\storefront\experience\CommerceStorefrontExperienceResolver;
use local_subscriptions\commerce\storefront\localisation\CommerceStorefrontComponentLocaliser;
use local_subscriptions\commerce\storefront\ownership\CommerceStorefrontOwnershipResolver;
use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontListFilter;
use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontListResult;
use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontPrice;
use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontProduct;
use local_subscriptions\commerce\storefront\upgrade\CommerceStorefrontUpgradeResolver;

/** Native-first read boundary for the future public boutique. */
final class CommerceStorefrontRepository {
    public function __construct(
        private readonly CommerceCatalogReadRepository $catalogue,
        private readonly \moodle_database $db
    ) {
    }

    public static function create(\moodle_database $db): self {
        return new self(new CommerceCatalogReadRepository($db), $db);
    }

    public function search(
        CommerceStorefrontListFilter $filter,
        int $page = 0,
        int $perpage = 24
    ): CommerceStorefrontListResult {
        $page = max(0, $page);
        $perpage = min(100, max(1, $perpage));
        $products = [];

        foreach ($this->catalogue->find_all() as $summary) {
            if (!$this->is_publicly_listable($summary)) {
                continue;
            }
            if ($filter->get_type() !== null && $summary->get_type() !== $filter->get_type()) {
                continue;
            }

            $details = $this->catalogue->find_by_sku($summary->get_sku());
            if ($details === null) {
                continue;
            }

            $product = $this->project($details, $filter->get_language(), $filter->get_currency());
            if ($product === null || !$this->matches_query($product, $filter->get_query())) {
                continue;
            }
            $products[] = $product;
        }

        usort(
            $products,
            static function(CommerceStorefrontProduct $left, CommerceStorefrontProduct $right): int {
                $featured = (int)$right->is_featured() <=> (int)$left->is_featured();
                if ($featured !== 0) {
                    return $featured;
                }

                $order = $left->get_display_order() <=> $right->get_display_order();
                if ($order !== 0) {
                    return $order;
                }

                $name = strcasecmp($left->get_name(), $right->get_name());
                return $name !== 0 ? $name : strcmp($left->get_sku(), $right->get_sku());
            }
        );

        return new CommerceStorefrontListResult(
            array_slice($products, $page * $perpage, $perpage),
            count($products),
            $page,
            $perpage
        );
    }

    public function find_by_sku(
        string $sku,
        string $language,
        ?string $currency = null,
        bool $allowdirectlink = true
    ): ?CommerceStorefrontProduct {
        $details = $this->catalogue->find_by_sku($sku);
        if ($details === null) {
            return null;
        }

        $summary = $details->get_summary();
        if (!$this->is_publicly_viewable($summary, $allowdirectlink)) {
            return null;
        }

        return $this->project($details, $language, $currency);
    }

    private function is_publicly_listable(CommerceCatalogProductSummary $product): bool {
        return $product->get_editorial_status() === CommerceCatalogEditorialStatus::PUBLISHED
            && $product->get_visibility() === CommerceCatalogVisibility::VISIBLE
            && $product->get_availability() === CommerceCatalogAvailability::ON_SALE
            && $product->get_technical_state() === CommerceCatalogTechnicalState::VALID;
    }

    private function is_publicly_viewable(CommerceCatalogProductSummary $product, bool $allowdirectlink): bool {
        $allowedvisibility = $product->get_visibility() === CommerceCatalogVisibility::VISIBLE
            || ($allowdirectlink && $product->get_visibility() === CommerceCatalogVisibility::DIRECT_LINK);

        return $product->get_editorial_status() === CommerceCatalogEditorialStatus::PUBLISHED
            && $allowedvisibility
            && $product->get_availability() === CommerceCatalogAvailability::ON_SALE
            && $product->get_technical_state() === CommerceCatalogTechnicalState::VALID;
    }

    private function project(
        CommerceCatalogProductDetails $details,
        string $language,
        ?string $currency
    ): ?CommerceStorefrontProduct {
        $summary = $details->get_summary();
        $translation = $this->translation($details->get_translations(), $language);
        $metadata = $summary->get_metadata();
        $merchandising = (new CommerceStorefrontMerchandisingResolver())->resolve($metadata);
        $promotionresolver = new CommerceStorefrontPromotionResolver();
        $experience = (new CommerceStorefrontExperienceResolver())->resolve($metadata, $summary->get_type(), $language);
        global $USER;
        $owned = isloggedin() && !isguestuser()
            ? (new CommerceStorefrontOwnershipResolver($this->db))->owns((int)$USER->id, $summary->get_sku())
            : false;
        $upgrade = null;
        if (!$owned && isloggedin() && !isguestuser() && $summary->get_id() !== null) {
            $upgradecurrency = strtoupper(trim((string)$currency));
            if ($upgradecurrency === '') {
                foreach ($summary->get_prices() as $candidateprice) {
                    if ($candidateprice->is_active()) {
                        $upgradecurrency = $candidateprice->get_currency();
                        break;
                    }
                }
            }
            if ($upgradecurrency !== '') {
                $legacyplanid = null;
                foreach ($details->get_legacy_references() as $reference) {
                    if (($reference['table'] ?? '') === 'subscription_plan') {
                        $legacyplanid = (int)($reference['id'] ?? 0);
                        break;
                    }
                }
                $upgrade = (new CommerceStorefrontUpgradeResolver($this->db))->resolve(
                    (int)$USER->id,
                    (int)$summary->get_id(),
                    $upgradecurrency,
                    $legacyplanid
                );
            }
        }
        $prices = [];
        $selectedcurrency = strtoupper(trim((string)$currency));

        foreach ($summary->get_prices() as $price) {
            if (!$price->is_active()) {
                continue;
            }
            if ($selectedcurrency !== '' && $price->get_currency() !== $selectedcurrency) {
                continue;
            }
            $promotion = $promotionresolver->resolve(
                $merchandising,
                $price->get_currency(),
                $price->get_amount_minor()
            );
            $prices[] = new CommerceStorefrontPrice(
                $price->get_currency(),
                $price->get_amount_minor(),
                $promotion['compareamountminor'] ?? null,
                $promotion['discountpercentage'] ?? null,
                $promotion['end'] ?? null,
                $price->get_id()
            );
        }

        if ($selectedcurrency !== '' && $prices === []) {
            return null;
        }

        usort(
            $prices,
            static fn(CommerceStorefrontPrice $left, CommerceStorefrontPrice $right): int =>
                strcmp($left->get_currency(), $right->get_currency())
        );

        $name = trim((string)($translation['name'] ?? $summary->get_name()));
        $shortdescription = trim((string)($translation['shortdescription'] ?? ''));
        $description = trim((string)($translation['description'] ?? $summary->get_description()));

        return new CommerceStorefrontProduct(
            $summary->get_sku(),
            $name !== '' ? $name : $summary->get_name(),
            $shortdescription,
            $description,
            $summary->get_type(),
            $prices,
            (new CommerceStorefrontComponentLocaliser($this->db))->localise(
                $details->get_components(),
                $language
            ),
            count($prices) === 1,
            $this->cover_url($details),
            $details->get_legacy_references(),
            $metadata,
            $merchandising->is_featured(),
            $merchandising->get_display_order(),
            $merchandising->get_badges(),
            $experience->get_group(),
            $experience->get_trust_items(),
            $experience->get_quick_facts(),
            $owned,
            $upgrade,
            $this->cover_urls($details),
            $summary->get_id()
        );
    }


    private function cover_url(CommerceCatalogProductDetails $details): ?string {
        global $CFG;

        foreach ($details->get_legacy_references() as $reference) {
            if (($reference['table'] ?? '') !== 'subscription_digital_product') {
                continue;
            }

            $legacy = $this->db->get_record(
                'subscription_digital_product',
                ['id' => (int)($reference['id'] ?? 0)],
                'coverimage',
                IGNORE_MISSING
            );
            if (!$legacy || trim((string)$legacy->coverimage) === '') {
                continue;
            }

            $filename = basename((string)$legacy->coverimage);
            if (is_file($CFG->dirroot . '/local/subscriptions/pix/cover/' . $filename)) {
                return (new \moodle_url(
                    '/local/subscriptions/pix/cover/' . rawurlencode($filename)
                ))->out(false);
            }
        }

        $summary = $details->get_summary();
        if ($summary->get_origin() !== 'native' || $summary->get_id() === null) {
            return null;
        }

        return CommerceProductCoverService::create()
            ->resolve((int)$summary->get_id(), CommerceProductCoverContext::STOREFRONT)
            ->get_url();
    }

    /** @return array<string,string|null> */
    private function cover_urls(CommerceCatalogProductDetails $details): array {
        $summary = $details->get_summary();
        $legacyurl = $this->cover_url($details);
        if ($summary->get_origin() !== 'native' || $summary->get_id() === null) {
            return array_fill_keys(CommerceProductCoverContext::all(), $legacyurl);
        }
        $resolved = CommerceProductCoverService::create()->resolve_all((int)$summary->get_id(), $legacyurl);
        return array_map(static fn($cover): ?string => $cover->get_url(), $resolved);
    }

    /**
     * @param array<int, array<string, mixed>> $translations
     * @return array<string, mixed>|null
     */
    private function translation(array $translations, string $language): ?array {
        $language = strtolower(trim($language));
        $base = explode('_', str_replace('-', '_', $language))[0];
        $bylanguage = [];

        foreach ($translations as $translation) {
            $candidate = strtolower(trim((string)($translation['language'] ?? '')));
            if ($candidate !== '' && !isset($bylanguage[$candidate])) {
                $bylanguage[$candidate] = $translation;
            }
        }

        foreach (array_values(array_unique(array_filter([$language, $base, 'fr', 'en', 'ru']))) as $candidate) {
            if (isset($bylanguage[$candidate])) {
                return $bylanguage[$candidate];
            }
        }

        return $translations !== [] ? reset($translations) : null;
    }

    private function matches_query(CommerceStorefrontProduct $product, string $query): bool {
        $query = trim($query);
        if ($query === '') {
            return true;
        }

        $haystack = \core_text::strtolower(implode(' ', [
            $product->get_name(),
            $product->get_short_description(),
            strip_tags($product->get_description()),
            $product->get_sku(),
        ]));

        return \core_text::strpos($haystack, \core_text::strtolower($query)) !== false;
    }
}
