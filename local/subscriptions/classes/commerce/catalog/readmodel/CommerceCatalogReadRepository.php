<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\readmodel;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\presentation\CommerceProductDisplayText;

use local_subscriptions\commerce\bundle\pricing\CommerceBundlePricingStrategy;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\status\CommerceCatalogStatusResolver;
use local_subscriptions\commerce\catalog\validation\CommerceCatalogActivationValidator;

/** Native-first unified catalogue read repository with explicit Legacy adapters. */
final class CommerceCatalogReadRepository {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceCatalogStatusResolver $statusresolver = new CommerceCatalogStatusResolver()
    ) {
    }

    /** @return CommerceCatalogProductSummary[] */
    public function find_all(): array {
        $native = $this->load_native_products();
        $mapped = $this->mapped_legacy_keys();

        foreach ($this->load_legacy_plans($mapped) as $product) {
            $native[] = $product;
        }
        foreach ($this->load_legacy_digital_products($mapped) as $product) {
            $native[] = $product;
        }

        usort($native, static fn(CommerceCatalogProductSummary $a, CommerceCatalogProductSummary $b): int =>
            strcasecmp($a->get_name(), $b->get_name()));
        return $native;
    }

    public function search(CommerceCatalogListFilter $filter, int $page = 0, int $perpage = 25): CommerceCatalogListResult {
        $page = max(0, $page);
        $perpage = min(100, max(1, $perpage));
        $items = array_values(array_filter($this->find_all(), static fn(CommerceCatalogProductSummary $product): bool =>
            $filter->matches($product)));
        return new CommerceCatalogListResult(
            array_slice($items, $page * $perpage, $perpage),
            count($items),
            $page,
            $perpage
        );
    }

    public function find_by_sku(string $sku): ?CommerceCatalogProductDetails {
        foreach ($this->find_all() as $summary) {
            if ($summary->get_sku() === $sku) {
                return $this->build_details($summary);
            }
        }
        return null;
    }

    public function find_by_origin_and_id(string $origin, int $id): ?CommerceCatalogProductDetails {
        foreach ($this->find_all() as $summary) {
            if ($summary->get_origin() === $origin && $summary->get_id() === $id) {
                return $this->build_details($summary);
            }
        }
        return null;
    }

    /** Resolve a purchase item reference to its canonical catalogue product. */
    public function find_by_purchase_reference(string $reference): ?CommerceCatalogProductDetails {
        $reference = trim($reference);
        if ($reference === '') {
            return null;
        }

        $direct = $this->find_by_sku($reference);
        if ($direct !== null) {
            return $direct;
        }

        if (preg_match('/^subscription-plan:(\d+)$/i', $reference, $matches)) {
            return $this->find_mapped_or_legacy('subscription_plan', (int)$matches[1], 'legacy_plan');
        }

        if (preg_match('/^digital-product:(.+)$/i', $reference, $matches)) {
            $token = trim($matches[1]);
            $legacyid = ctype_digit($token) ? (int)$token : 0;
            if ($legacyid <= 0) {
                $legacy = $this->db->get_record(
                    'subscription_digital_product',
                    ['slug' => $token],
                    'id',
                    IGNORE_MISSING
                );
                $legacyid = $legacy ? (int)$legacy->id : 0;
            }
            if ($legacyid > 0) {
                return $this->find_mapped_or_legacy('subscription_digital_product', $legacyid, 'legacy_digital');
            }
        }

        return null;
    }

    private function find_mapped_or_legacy(string $legacytable, int $legacyid, string $legacyorigin): ?CommerceCatalogProductDetails {
        $mapping = $this->db->get_record(
            'local_subs_commerce_prod_map',
            ['legacytable' => $legacytable, 'legacyid' => $legacyid],
            'productid',
            IGNORE_MISSING
        );
        if ($mapping) {
            return $this->find_by_origin_and_id('native', (int)$mapping->productid);
        }
        return $this->find_by_origin_and_id($legacyorigin, $legacyid);
    }

    private function build_details(CommerceCatalogProductSummary $summary): CommerceCatalogProductDetails {
        $translations = [];
        $components = [];
        $legacyreferences = [];
        $id = (int)$summary->get_id();

        if ($summary->get_origin() === 'native') {
            foreach ($this->db->get_records('local_subs_commerce_prod_tr', ['productid' => $id], 'language ASC') as $record) {
                $translations[] = [
                    'language' => (string)$record->language,
                    'name' => CommerceProductDisplayText::title((string)$record->name),
                    'shortdescription' => (string)($record->shortdescription ?? ''),
                    'description' => (string)($record->description ?? ''),
                ];
            }
            foreach ($this->db->get_records('local_subs_commerce_prod_comp', ['parentproductid' => $id], 'sortorder ASC') as $record) {
                $child = $this->db->get_record('local_subs_commerce_product', ['id' => $record->childproductid], 'id,sku,name,type');
                $components[] = [
                    'id' => (int)$record->childproductid,
                    'sku' => $child ? (string)$child->sku : '',
                    'name' => $child ? CommerceProductDisplayText::title((string)$child->name) : ('#' . (int)$record->childproductid),
                    'type' => $child ? (string)$child->type : '',
                    'quantity' => (int)$record->quantity,
                ];
            }
            foreach ($this->db->get_records('local_subs_commerce_prod_map', ['productid' => $id], 'legacyfamily ASC') as $record) {
                $legacyreferences[] = [
                    'family' => (string)$record->legacyfamily,
                    'table' => (string)$record->legacytable,
                    'id' => (int)$record->legacyid,
                ];
            }
        } else if ($summary->get_origin() === 'legacy_plan') {
            foreach ($this->db->get_records('subscription_plan_translation', ['planid' => $id], 'lang ASC') as $record) {
                $translations[] = [
                    'language' => (string)$record->lang,
                    'name' => CommerceProductDisplayText::title((string)$record->name),
                    'shortdescription' => '',
                    'description' => (string)($record->description ?? ''),
                ];
            }
            $legacyreferences[] = ['family' => 'subscription', 'table' => 'subscription_plan', 'id' => $id];
        } else if ($summary->get_origin() === 'legacy_digital') {
            foreach ($this->db->get_records('subscription_digital_product_lang', ['productid' => $id], 'lang ASC') as $record) {
                $translations[] = [
                    'language' => (string)$record->lang,
                    'name' => CommerceProductDisplayText::title((string)$record->title),
                    'shortdescription' => (string)($record->sales_intro ?? ''),
                    'description' => (string)($record->content_items ?? ''),
                ];
            }
            $legacyreferences[] = ['family' => 'digital', 'table' => 'subscription_digital_product', 'id' => $id];
        }

        return new CommerceCatalogProductDetails($summary, $translations, $components, $legacyreferences);
    }

    /** @return CommerceCatalogProductSummary[] */
    private function load_native_products(): array {
        $records = $this->db->get_records('local_subs_commerce_product', null, 'name ASC');
        $result = [];
        foreach ($records as $record) {
            $metadata = $this->decode_json((string)($record->metadatajson ?? ''));
            $prices = [];
            foreach ($this->db->get_records('local_subs_commerce_prod_price', ['productid' => $record->id]) as $price) {
                $prices[] = new CommerceCatalogPrice(
                    (string)$price->currency,
                    (int)$price->amountminor,
                    $price->provider !== null ? (string)$price->provider : null,
                    !empty($price->active),
                    'native',
                    (int)$price->id
                );
            }
            $fulfillments = [];
            foreach ($this->db->get_records('local_subs_commerce_prod_ent', ['productid' => $record->id], 'sortorder ASC') as $entitlement) {
                $fulfillments[] = new CommerceCatalogFulfillment(
                    (string)$entitlement->type,
                    (string)$entitlement->resourcekey,
                    $entitlement->durationseconds !== null ? (int)$entitlement->durationseconds : null,
                    (int)$entitlement->quantity,
                    $this->decode_json((string)($entitlement->configurationjson ?? '')),
                    'native'
                );
            }
            $domainproduct = new CommerceProduct(
                (string)$record->sku,
                (string)$record->type,
                (string)$record->status,
                (string)$record->name,
                (string)($record->description ?? ''),
                $metadata,
                (int)$record->id,
                $record->availablefrom !== null ? (int)$record->availablefrom : null,
                $record->availableuntil !== null ? (int)$record->availableuntil : null,
                (int)$record->timecreated,
                (int)$record->timemodified
            );
            $configurationvalid = (new CommerceCatalogActivationValidator($this->db))
                ->validate($domainproduct)
                ->is_valid();

            $status = $this->statusresolver->resolve(
                (string)$record->status,
                $record->availablefrom !== null ? (int)$record->availablefrom : null,
                $record->availableuntil !== null ? (int)$record->availableuntil : null,
                $metadata,
                time(),
                $configurationvalid
            );
            $result[] = new CommerceCatalogProductSummary(
                (int)$record->id,
                (string)$record->sku,
                (string)$record->name,
                (string)($record->description ?? ''),
                (string)$record->type,
                'native',
                $status,
                $prices,
                $fulfillments,
                $metadata,
                $record->availablefrom !== null ? (int)$record->availablefrom : null,
                $record->availableuntil !== null ? (int)$record->availableuntil : null
            );
        }
        return $result;
    }

    /**
     * A fixed-price Bundle needs one active direct price. A calculated Bundle
     * needs at least two active components sharing one active currency.
     *
     * @param CommerceCatalogPrice[] $prices
     */
    private function is_bundle_configuration_valid(int $productid, array $metadata, array $prices): bool {
        $components = $this->db->get_records(
            'local_subs_commerce_prod_comp',
            ['parentproductid' => $productid],
            'sortorder ASC'
        );
        if (count($components) < 2) {
            return false;
        }

        $strategy = strtolower((string)($metadata['bundle_pricing_strategy'] ?? CommerceBundlePricingStrategy::FIXED));
        if ($strategy === CommerceBundlePricingStrategy::FIXED) {
            foreach ($prices as $price) {
                if ($price->is_active()) {
                    return true;
                }
            }
            return false;
        }

        if (!in_array($strategy, [
            CommerceBundlePricingStrategy::COMPONENT_SUM,
            CommerceBundlePricingStrategy::PERCENTAGE_DISCOUNT,
        ], true)) {
            return false;
        }

        $common = null;
        foreach ($components as $component) {
            $child = $this->db->get_record(
                'local_subs_commerce_product',
                ['id' => $component->childproductid],
                'id,status'
            );
            if (!$child || (string)$child->status !== 'active') {
                return false;
            }

            $currencies = [];
            foreach ($this->db->get_records('local_subs_commerce_prod_price', [
                'productid' => (int)$child->id,
                'active' => 1,
            ]) as $price) {
                $currencies[strtoupper((string)$price->currency)] = true;
            }

            $common = $common === null ? $currencies : array_intersect_key($common, $currencies);
            if ($common === []) {
                return false;
            }
        }

        return $common !== null && $common !== [];
    }

    /** @param array<string, bool> $mapped @return CommerceCatalogProductSummary[] */
    private function load_legacy_plans(array $mapped): array {
        $result = [];
        foreach ($this->db->get_records('subscription_plan', null, 'name ASC') as $record) {
            if (isset($mapped['subscription_plan:' . $record->id])) {
                continue;
            }
            $prices = [];
            foreach ($this->db->get_records('subscription_plan_price', ['planid' => $record->id]) as $price) {
                $prices[] = new CommerceCatalogPrice(
                    (string)$price->currency,
                    (int)round(((float)$price->price) * 100),
                    !empty($price->stripe_price_id) ? 'stripe' : null,
                    true,
                    'legacy_plan'
                );
            }
            $fulfillments = [];
            foreach ($this->db->get_records('subscription_plan_entitlement', ['planid' => $record->id], 'priority ASC') as $entitlement) {
                $fulfillments[] = new CommerceCatalogFulfillment(
                    'course_enrolment',
                    'course:' . (int)$entitlement->courseid,
                    null,
                    1,
                    [
                        'courseid' => (int)$entitlement->courseid,
                        'accesslevel' => (string)$entitlement->accesslevel,
                        'roleshortname' => (string)$entitlement->roleshortname,
                        'groupname' => (string)($entitlement->groupname ?? ''),
                    ],
                    'legacy_plan'
                );
            }
            if (empty($fulfillments) && !empty($record->accessscopeid)) {
                $scope = $this->db->get_record('subscription_access_scope', ['id' => $record->accessscopeid]);
                if ($scope) {
                    foreach ($this->parse_course_ids((string)$scope->course_ids) as $courseid) {
                        $fulfillments[] = new CommerceCatalogFulfillment(
                            'course_enrolment', 'course:' . $courseid, null, 1,
                            ['courseid' => $courseid, 'source' => 'access_scope'], 'legacy_plan'
                        );
                    }
                }
            }
            $status = $this->statusresolver->resolve(!empty($record->is_active) ? 'active' : 'inactive', null, null, [], time(), !empty($prices) && !empty($fulfillments));
            $result[] = new CommerceCatalogProductSummary(
                (int)$record->id,
                'SUB.PLAN.' . (int)$record->id,
                (string)$record->name,
                '',
                'course_access',
                'legacy_plan',
                $status,
                $prices,
                $fulfillments,
                ['accessscopeid' => $record->accessscopeid ?? null, 'durationkey' => (string)$record->duration_key,
                    'recurring' => !empty($record->is_recurring), 'trial' => !empty($record->is_trial)]
            );
        }
        return $result;
    }

    /** @param array<string, bool> $mapped @return CommerceCatalogProductSummary[] */
    private function load_legacy_digital_products(array $mapped): array {
        $result = [];
        foreach ($this->db->get_records('subscription_digital_product', null, 'sortorder ASC, name ASC') as $record) {
            if (isset($mapped['subscription_digital_product:' . $record->id])) {
                continue;
            }
            $prices = [];
            foreach (['EUR' => (float)$record->price_eur, 'RUB' => (float)$record->price_rub] as $currency => $amount) {
                if ($amount > 0) {
                    $prices[] = new CommerceCatalogPrice($currency, (int)round($amount * 100), null, true, 'legacy_digital');
                }
            }
            $fulfillments = [new CommerceCatalogFulfillment(
                'digital_download',
                'digital-product:' . (int)$record->id,
                null,
                1,
                ['productid' => (int)$record->id, 'filename' => (string)$record->filename,
                    'mobilefilename' => (string)($record->mobile_filename ?? '')],
                'legacy_digital'
            )];
            $status = $this->statusresolver->resolve(!empty($record->enabled) ? 'active' : 'inactive', null, null, [], time(), !empty($prices) && trim((string)$record->filename) !== '');
            $slug = strtoupper(preg_replace('/[^A-Z0-9._:-]+/i', '.', (string)$record->slug));
            $result[] = new CommerceCatalogProductSummary(
                (int)$record->id,
                'DIGITAL.' . $slug,
                (string)$record->name,
                (string)($record->description ?? ''),
                'digital_download',
                'legacy_digital',
                $status,
                $prices,
                $fulfillments,
                ['slug' => (string)$record->slug, 'coverimage' => (string)($record->coverimage ?? ''),
                    'sortorder' => (int)$record->sortorder]
            );
        }
        return $result;
    }

    /** @return array<string, bool> */
    private function mapped_legacy_keys(): array {
        $result = [];
        foreach ($this->db->get_records('local_subs_commerce_prod_map') as $record) {
            $result[(string)$record->legacytable . ':' . (int)$record->legacyid] = true;
        }
        return $result;
    }

    private function decode_json(string $json): array {
        if (trim($json) === '') {
            return [];
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    /** @return int[] */
    private function parse_course_ids(string $value): array {
        preg_match_all('/\d+/', $value, $matches);
        return array_values(array_unique(array_map('intval', $matches[0] ?? [])));
    }
}
