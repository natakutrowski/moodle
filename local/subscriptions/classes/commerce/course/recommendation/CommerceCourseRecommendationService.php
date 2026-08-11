<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\course\recommendation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\readmodel\CommerceCatalogReadRepository;
use local_subscriptions\commerce\storefront\presentation\CommerceStorefrontUrlResolver;
use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontListFilter;
use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontPrice;
use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontProduct;
use local_subscriptions\commerce\storefront\repository\CommerceStorefrontRepository;
use local_subscriptions\commerce\course\recommendation\CommerceCourseRecommendationEligibility;
use local_subscriptions\commerce\course\recommendation\CommerceCourseResourceKeyParser;
use local_subscriptions\commerce\trial\CommerceTrialCartPricingService;
use local_subscriptions\commerce\trial\CommerceTrialProductEligibilityService;
use local_subscriptions\trial_manager;

/** Selects public course and bundle products relevant to a learner. */
final class CommerceCourseRecommendationService {
    private readonly CommerceStorefrontRepository $storefront;
    private readonly CommerceCatalogReadRepository $catalogue;

    public function __construct(private readonly \moodle_database $db) {
        $this->storefront = CommerceStorefrontRepository::create($db);
        $this->catalogue = new CommerceCatalogReadRepository($db);
    }

    /**
     * @param int[] $accessiblecourseids
     * @param int[] $trialcourseids
     */
    public function get_for_learner(
        int $userid,
        array $accessiblecourseids,
        array $trialcourseids,
        string $language,
        string $currency,
        int $limit = 3
    ): CommerceCourseRecommendationCollection {
        if ($userid <= 0 || $limit <= 0) {
            return new CommerceCourseRecommendationCollection();
        }

        $accessible = $this->normalise_course_ids($accessiblecourseids);
        $trials = $this->normalise_course_ids($trialcourseids);
        $currency = strtoupper(trim($currency));
        $products = $this->products_for_currency($language, $currency);
        $eligibility = new CommerceCourseRecommendationEligibility();
        $ranker = new CommerceCourseRecommendationRanker();
        $candidates = [];

        foreach ($products as $product) {
            // Once the target product is owned, the upgrade is complete and must disappear.
            if ($product->is_owned()) {
                continue;
            }

            if (!in_array($product->get_type(), ['course_access', 'subscription', 'bundle'], true)) {
                continue;
            }

            $courseids = $this->product_course_ids($product);
            if ($courseids === []) {
                continue;
            }

            $type = $product->get_type();
            $isbundle = $type === 'bundle';
            $iscourseproduct = in_array(
                $type,
                ['course_access', 'subscription'],
                true
            );

            $trialaccesslevel = $this->product_access_level($product);
            $storefrontupgrade = $iscourseproduct
                ? $product->get_upgrade()
                : null;
            $explicitupgrade = $iscourseproduct && (
                $storefrontupgrade !== null
                || $this->metadata_upgrade_for_learner(
                    $product,
                    array_keys($accessible)
                )
            );

            // A real course upgrade must remain an upgrade even while the
            // learner still has an active Trial. The Trial discount may affect
            // its price, but it must not replace the commercial path.
            $istrialeligible = $iscourseproduct
                && CommerceTrialProductEligibilityService::create()->is_eligible(
                    $userid,
                    $product->get_sku()
                );
            $istrialoffer = $istrialeligible && !$explicitupgrade;

            $decision = $eligibility->evaluate(
                $product->is_owned(),
                $courseids,
                array_keys($accessible),
                // Bundle contents never create an upgrade path. A bundle may
                // remain relevant because it introduces new course content,
                // but it must be presented and ranked as a bundle.
                $isbundle ? [] : array_keys($trials),
                $explicitupgrade
            );
            if (!$decision['relevant']) {
                continue;
            }

            $isupgrade = !$isbundle && $decision['upgrade'];

            $presentation = $this->present(
                $product,
                $isupgrade,
                $currency,
                $istrialoffer,
                $userid
            );
            $candidates[] = [
                'item' => $presentation,
                'score' => $ranker->score(
                    ($isupgrade || $istrialoffer),
                    $product->get_badges(),
                    $product->is_featured(),
                    $type,
                    $istrialoffer,
                    $trialaccesslevel
                ),
                'order' => $product->get_display_order(),
                'sku' => $product->get_sku(),
            ];
        }

        usort($candidates, static function(array $left, array $right): int {
            $score = $right['score'] <=> $left['score'];
            if ($score !== 0) {
                return $score;
            }
            $order = $left['order'] <=> $right['order'];
            if ($order !== 0) {
                return $order;
            }
            return strcmp($left['sku'], $right['sku']);
        });

        return new CommerceCourseRecommendationCollection(array_map(
            static fn(array $candidate): CommerceCourseRecommendationPresentation => $candidate['item'],
            array_slice($candidates, 0, $limit)
        ));
    }

    /** @return array<int, true> */
    private function normalise_course_ids(array $courseids): array {
        $result = [];
        foreach ($courseids as $courseid) {
            $courseid = (int)$courseid;
            if ($courseid > 0) {
                $result[$courseid] = true;
            }
        }
        return $result;
    }

    /** @return CommerceStorefrontProduct[] */
    private function products_for_currency(string $language, string $currency): array {
        $filter = new CommerceStorefrontListFilter($language, $currency !== '' ? $currency : null);
        $products = $this->storefront->search($filter, 0, 100)->get_products();

        // A learner should still receive useful recommendations when the detected regional
        // currency has not yet been configured for the catalogue. The displayed price then
        // uses the first active currency of the product instead of hiding the whole section.
        if ($products === [] && $currency !== '') {
            $products = $this->storefront->search(
                new CommerceStorefrontListFilter($language),
                0,
                100
            )->get_products();
        }

        return $products;
    }

    /** @return int[] */
    private function product_course_ids(CommerceStorefrontProduct $product): array {
        $skus = [$product->get_sku()];
        foreach ($product->get_components() as $component) {
            $childsku = trim((string)($component['childproductsku'] ?? $component['sku'] ?? ''));
            if ($childsku !== '') {
                $skus[] = $childsku;
            }
        }

        $ids = [];
        foreach (array_unique($skus) as $sku) {
            $details = $this->catalogue->find_by_sku($sku);
            if ($details === null) {
                continue;
            }
            foreach ($details->get_summary()->get_fulfillments() as $fulfillment) {
                if (!in_array($fulfillment->get_type(), ['course_access', 'course_enrolment'], true)) {
                    continue;
                }
                $courseid = CommerceCourseResourceKeyParser::course_id(
                    $fulfillment->get_resource_key(),
                    $fulfillment->get_configuration()
                );
                if ($courseid !== null) {
                    $ids[$courseid] = $courseid;
                }
            }
        }
        return array_values($ids);
    }

    private function present(
        CommerceStorefrontProduct $product,
        bool $upgrade,
        string $currency,
        bool $trialoffer = false,
        int $userid = 0
    ): CommerceCourseRecommendationPresentation {
        $price = $this->preferred_price($product, $currency);
        $priceformatted = $price instanceof CommerceStorefrontPrice
            ? $this->format_money($price->get_amount_minor(), $price->get_currency())
            : '';
        $compareformatted = $price instanceof CommerceStorefrontPrice && $price->get_compare_amount_minor() !== null
            ? $this->format_money($price->get_compare_amount_minor(), $price->get_currency())
            : null;
        $urlcurrency = $price instanceof CommerceStorefrontPrice ? $price->get_currency() : $currency;

        return new CommerceCourseRecommendationPresentation(
            $product->get_sku(),
            $product->get_type(),
            $product->get_name(),
            $product->get_short_description(),
            $product->get_cover_url('recommendation'),
            $this->product_url(
                $product,
                $urlcurrency,
                $trialoffer
            ),
            $priceformatted,
            $compareformatted,
            $price?->get_discount_percentage(),
            $upgrade,
            $upgrade ? $this->resolved_upgrade_price($product, $price) : null,
            $upgrade ? $this->resolved_upgrade_from_label($product) : null,
            $upgrade ? $this->resolved_upgrade_to_label($product) : null,
            $trialoffer,
            $trialoffer ? $this->trial_price($userid, $product, $price) : null,
            $trialoffer ? $priceformatted : null,
            $trialoffer ? trial_manager::get_trial_settings()['disc_pct'] : null,
            $upgrade ? $priceformatted : null,
            $upgrade
                ? $this->upgrade_discount_percentage($product, $price)
                : null,
            $upgrade
                ? $this->upgrade_saving($product, $price)
                : null
        );
    }



    private function upgrade_saving(
        CommerceStorefrontProduct $product,
        ?CommerceStorefrontPrice $price
    ): ?string {
        if ($price === null) {
            return null;
        }

        $upgrade = $product->get_upgrade();
        if ($upgrade === null) {
            return null;
        }

        $savingminor = max(
            0,
            $price->get_amount_minor() - $upgrade->get_amount_minor()
        );

        if ($savingminor <= 0) {
            return null;
        }

        return $this->format_money(
            $savingminor,
            $price->get_currency()
        );
    }

    private function upgrade_discount_percentage(
        CommerceStorefrontProduct $product,
        ?CommerceStorefrontPrice $price
    ): ?int {
        if ($price === null || $price->get_amount_minor() <= 0) {
            return null;
        }

        $upgrade = $product->get_upgrade();
        if ($upgrade === null) {
            return null;
        }

        $reference = $price->get_amount_minor();
        $final = max(0, $upgrade->get_amount_minor());

        if ($final >= $reference) {
            return null;
        }

        return (int)round(
            (($reference - $final) * 100) / $reference
        );
    }

    private function resolved_upgrade_price(
        CommerceStorefrontProduct $product,
        ?CommerceStorefrontPrice $fallback
    ): ?string {
        $upgrade = $product->get_upgrade();
        if ($upgrade !== null) {
            return $this->format_money($upgrade->get_amount_minor(), $upgrade->get_currency());
        }
        return $this->upgrade_price($product, $fallback);
    }

    private function resolved_upgrade_from_label(CommerceStorefrontProduct $product): ?string {
        $upgrade = $product->get_upgrade();
        if ($upgrade !== null && trim($upgrade->get_from_label()) !== '') {
            return $upgrade->get_from_label();
        }
        return $this->metadata_string($product->get_metadata(), ['upgrade_from_label', 'upgradefromlabel']);
    }

    private function resolved_upgrade_to_label(CommerceStorefrontProduct $product): ?string {
        $upgrade = $product->get_upgrade();
        if ($upgrade !== null && trim($upgrade->get_to_label()) !== '') {
            return $upgrade->get_to_label();
        }
        return $this->metadata_string($product->get_metadata(), ['upgrade_to_label', 'upgradetolabel']);
    }

    /**
     * Explicit upgrade paths may be configured on a target product without leaking
     * catalogue rules into local_campus. Trial upgrades remain supported separately.
     *
     * Supported metadata:
     * - upgrade_from_courseids: array or comma-separated course IDs;
     * - upgrade: true, when the target product enriches a course already accessible.
     */
    private function metadata_upgrade_for_learner(
        CommerceStorefrontProduct $product,
        array $accessiblecourseids
    ): bool {
        $metadata = $product->get_metadata();
        $configured = $metadata['upgrade_from_courseids'] ?? [];
        if (is_string($configured)) {
            $configured = preg_split('/[,;\s]+/', $configured, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }
        if (is_array($configured)) {
            $configured = array_values(array_filter(array_map('intval', $configured)));
            if (array_intersect($configured, $accessiblecourseids) !== []) {
                return true;
            }
        }

        $explicit = filter_var($metadata['upgrade'] ?? false, FILTER_VALIDATE_BOOL);
        if (!$explicit) {
            return false;
        }

        return array_intersect($this->product_course_ids($product), $accessiblecourseids) !== [];
    }

    private function upgrade_price(
        CommerceStorefrontProduct $product,
        ?CommerceStorefrontPrice $fallback
    ): ?string {
        $metadata = $product->get_metadata();
        $upgradeconfig = is_array($metadata['upgrade'] ?? null) ? $metadata['upgrade'] : [];
        $minor = $metadata['upgrade_amount_minor']
            ?? $metadata['upgrade_price_minor']
            ?? ($upgradeconfig['amountminor'] ?? null);
        $currency = $metadata['upgrade_currency']
            ?? ($upgradeconfig['currency'] ?? null)
            ?? $fallback?->get_currency();

        if (is_numeric($minor) && (int)$minor >= 0 && is_string($currency) && trim($currency) !== '') {
            return $this->format_money((int)$minor, $currency);
        }

        if ($fallback instanceof CommerceStorefrontPrice) {
            return $this->format_money($fallback->get_amount_minor(), $fallback->get_currency());
        }

        return null;
    }

    /** @param string[] $keys */
    private function metadata_string(array $metadata, array $keys): ?string {
        foreach ($keys as $key) {
            $value = trim((string)($metadata[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }
        return null;
    }

    private function product_access_level(
        CommerceStorefrontProduct $product
    ): string {
        $details = $this->catalogue->find_by_sku($product->get_sku());
        if ($details === null) {
            return '';
        }

        foreach ($details->get_summary()->get_fulfillments() as $fulfillment) {
            if (!in_array(
                $fulfillment->get_type(),
                ['course_access', 'course_enrolment'],
                true
            )) {
                continue;
            }

            if (preg_match(
                '/^course:\d+:([a-z0-9_-]+)$/i',
                $fulfillment->get_resource_key(),
                $matches
            )) {
                return strtolower($matches[1]);
            }

            $configuration = $fulfillment->get_configuration();
            $level = strtolower(trim((string)(
                $configuration['accesslevel']
                ?? $configuration['access_level']
                ?? ''
            )));
            if ($level !== '') {
                return $level;
            }
        }

        return '';
    }

    private function product_url(
        CommerceStorefrontProduct $product,
        string $currency,
        bool $trialoffer
    ): string {
        $url = CommerceStorefrontUrlResolver::details($product, $currency);
        return $url->out(false);
    }

    private function trial_price(
        int $userid,
        CommerceStorefrontProduct $product,
        ?CommerceStorefrontPrice $price
    ): ?string {
        if ($userid <= 0 || !$price instanceof CommerceStorefrontPrice) {
            return null;
        }

        $resolved = CommerceTrialCartPricingService::create()->resolve(
            $userid,
            $product->get_sku(),
            $price->get_currency(),
            $price->get_amount_minor()
        );

        return $resolved === null
            ? null
            : $this->format_money(
                $resolved->get_total_minor(),
                $resolved->get_currency()
            );
    }

    private function preferred_price(
        CommerceStorefrontProduct $product,
        string $currency
    ): ?CommerceStorefrontPrice {
        $currency = strtoupper(trim($currency));
        foreach ($product->get_prices() as $price) {
            if ($currency !== '' && $price->get_currency() === $currency) {
                return $price;
            }
        }
        return $product->get_prices()[0] ?? null;
    }

    private function format_money(int $minor, string $currency): string {
        return format_float($minor / 100, 2) . ' ' . strtoupper($currency);
    }
}
