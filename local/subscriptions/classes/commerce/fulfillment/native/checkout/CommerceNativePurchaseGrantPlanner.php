<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native\checkout;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\bundle\expansion\CommerceBundleExpansionService;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductComponentRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductEntitlementRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductPriceRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\catalog\service\CommerceEffectiveEntitlementResolver;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrantPlan;

/** Builds the deterministic Native Grant plan for one persisted purchase. */
final class CommerceNativePurchaseGrantPlanner {
    public function __construct(private readonly \moodle_database $db) {
    }

    /** @param \stdClass[] $items */
    public function plan(\stdClass $purchase, array $items, ?int $plannedat = null): CommerceEntitlementGrantPlan {
        $hydrator = new CommerceCatalogHydrator();
        $products = new CommerceProductRepository($this->db, $hydrator);
        $prices = new CommerceProductPriceRepository($this->db, $hydrator, $products);
        $components = new CommerceProductComponentRepository($this->db, $hydrator, $products);
        $expander = new CommerceBundleExpansionService($products, $components);
        $persisted = new CommerceProductEntitlementRepository($this->db, $hydrator, $products);
        $effective = new CommerceEffectiveEntitlementResolver($this->db, $products, $persisted);

        $grants = [];
        $position = 0;
        $now = $plannedat ?? time();

        foreach ($items as $item) {
            $purchasedsku = $this->resolve_product_sku($item, $prices);
            $expandeditems = $expander->expand($purchasedsku, max(1, (int)$item->quantity))->get_items();

            foreach ($expandeditems as $expandeditem) {
                $sku = $expandeditem->get_product()->get_sku();
                $definitions = $effective->resolve_by_product_sku($sku);
                if ($definitions === []) {
                    throw new \RuntimeException(
                        'No effective Native entitlement definition exists for Commerce product ' . $sku . '.'
                    );
                }

                $itemmetadata = $this->extract_item_metadata($item);
                foreach ($definitions as $definition) {
                    $position++;
                    $duration = $definition->get_duration_seconds();
                    $grants[] = new CommerceEntitlementGrant(
                        'ent-' . substr(hash('sha256',
                            $purchase->reference . '|' . $item->id . '|' . $sku . '|' . $position
                        ), 0, 32),
                        (string)$purchase->reference,
                        (string)$item->itemreference,
                        $sku,
                        $definition->get_type(),
                        $definition->get_resource_key(),
                        $definition->get_quantity() * $expandeditem->get_quantity(),
                        !empty($purchase->userid) ? (int)$purchase->userid : null,
                        (string)$purchase->customeremail,
                        $now,
                        $duration === null ? null : $now + $duration,
                        array_replace($definition->get_configuration(), $this->operation_configuration($itemmetadata)),
                        [
                            'definitionid' => $definition->get_id(),
                            'sortorder' => $definition->get_sort_order(),
                            'source' => $definition->get_id() === null
                                ? 'native_access_scope'
                                : 'native_catalogue_definition',
                            'priceid' => $this->extract_price_id($item),
                            'purchasedsku' => $purchasedsku,
                            'expandedsku' => $sku,
                            'operation' => $itemmetadata['operation'] ?? null,
                            'sourceplanid' => $itemmetadata['sourceplanid'] ?? null,
                            'targetplanid' => $itemmetadata['targetplanid'] ?? null,
                        ]
                    );
                }
            }
        }

        return new CommerceEntitlementGrantPlan((string)$purchase->reference, $grants, $now);
    }

    private function resolve_product_sku(\stdClass $item, CommerceProductPriceRepository $prices): string {
        $priceid = $this->extract_price_id($item);
        if ($priceid > 0) {
            $price = $prices->find_by_id($priceid);
            if ($price !== null) {
                return $price->get_product_sku();
            }
        }
        return strtoupper(trim((string)$item->itemreference));
    }

    /** @return array<string, mixed> */
    private function extract_item_metadata(\stdClass $item): array {
        $metadata = [];
        foreach (['metadatajson', 'fulfillmentjson'] as $field) {
            $decoded = json_decode((string)($item->{$field} ?? ''), true);
            if (is_array($decoded)) {
                $metadata = array_replace($metadata, $decoded);
            }
        }
        return $metadata;
    }

    /** @param array<string, mixed> $metadata @return array<string, mixed> */
    private function operation_configuration(array $metadata): array {
        $operation = strtolower(trim((string)($metadata['operation'] ?? '')));

        if ($operation === 'upgrade') {
            return [
                'commerceoperation' => 'upgrade',
                'sourceplanid' => (int)($metadata['sourceplanid'] ?? 0),
                'targetplanid' => (int)($metadata['targetplanid'] ?? 0),
            ];
        }

        if ($operation === 'trialconversion') {
            return [
                'commerceoperation' => 'trialconversion',
                'trialdiscountpercent' => max(
                    0,
                    min(100, (int)($metadata['trialdiscountpercent'] ?? 0))
                ),
                'trialdiscountexpiresat' =>
                    (int)($metadata['trialdiscountexpiresat'] ?? 0),
                'trialproductsku' =>
                    strtoupper(trim((string)($metadata['trialproductsku'] ?? ''))),
            ];
        }

        return [];
    }

    private function extract_price_id(\stdClass $item): int {
        foreach (['fulfillmentjson', 'metadatajson'] as $field) {
            $decoded = json_decode((string)($item->{$field} ?? ''), true);
            if (is_array($decoded) && !empty($decoded['priceid'])) {
                return (int)$decoded['priceid'];
            }
        }
        return 0;
    }
}
