<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\grant;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\bundle\expansion\CommerceBundleExpansionService;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductComponentRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductEntitlementRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\catalog\service\CommerceEffectiveEntitlementResolver;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrantPlan;
use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantPersister;
use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantRecordMapper;
use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantRepository;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentContext;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentHandlerRegistry;
use local_subscriptions\commerce\fulfillment\native\course\CommerceCourseAccessFulfillmentHandler;
use local_subscriptions\commerce\fulfillment\native\digital\CommerceDigitalDownloadFulfillmentHandler;
use local_subscriptions\commerce\fulfillment\native\persistence\CommercePersistentNativeFulfillmentExecutor;
use local_subscriptions\commerce\fulfillment\native\persistence\MoodleCommerceNativeFulfillmentPersistenceRepository;

/**
 * Native Commerce manual grant facade for CRM/admin tools.
 *
 * No purchase row is fabricated. The historical ledger field
 * "purchasereference" carries a deterministic non-purchase source reference,
 * and metadata explicitly records SOURCE=crm_manual_grant.
 */
final class CommerceManualProductGrantService {
    public const SOURCE = 'crm_manual_grant';

    public function __construct(private readonly \moodle_database $db) {
    }

    public function plan(
        int $userid,
        int $productid,
        int $actoruserid,
        string $reason = '',
        ?int $now = null
    ): CommerceEntitlementGrantPlan {
        if ($userid <= 0 || $productid <= 0 || $actoruserid <= 0) {
            throw new \coding_exception('Manual Native grant requires valid user, product and actor identifiers.');
        }

        $user = $this->db->get_record(
            'user',
            ['id' => $userid, 'deleted' => 0],
            'id,email',
            MUST_EXIST
        );
        if (!validate_email((string)$user->email)) {
            throw new \coding_exception('Manual Native grant beneficiary must have a valid email address.');
        }

        $hydrator = new CommerceCatalogHydrator();
        $products = new CommerceProductRepository($this->db, $hydrator);
        $product = $products->find_by_id($productid);
        if ($product === null || !$product->is_active()) {
            throw new \moodle_exception('commerce_manual_grant_product_unavailable', 'local_subscriptions');
        }

        $components = new CommerceProductComponentRepository($this->db, $hydrator, $products);
        $expander = new CommerceBundleExpansionService($products, $components);
        $persisted = new CommerceProductEntitlementRepository($this->db, $hydrator, $products);
        $effective = new CommerceEffectiveEntitlementResolver($this->db, $products, $persisted);

        $mapper = new CommerceEntitlementGrantRecordMapper();
        $repository = new CommerceEntitlementGrantRepository($this->db, $mapper);

        $rootsku = $product->get_sku();
        $sourceref = $this->source_reference($userid, $productid, $rootsku);
        $plannedat = $now ?? time();
        $reason = trim($reason);
        $grants = [];
        $position = 0;

        foreach ($expander->expand($rootsku, 1)->get_items() as $expandeditem) {
            $leafsku = $expandeditem->get_product()->get_sku();
            $definitions = $effective->resolve_by_product_sku($leafsku);

            if ($definitions === []) {
                throw new \moodle_exception(
                    'commerce_manual_grant_missing_entitlement',
                    'local_subscriptions',
                    '',
                    $leafsku
                );
            }

            foreach ($definitions as $definition) {
                $position++;
                $itemref = $this->item_reference($rootsku, $leafsku, $position);
                $duration = $definition->get_duration_seconds();

                $candidate = new CommerceEntitlementGrant(
                    $this->grant_reference(
                        $sourceref,
                        $itemref,
                        $definition->get_type(),
                        $definition->get_resource_key()
                    ),
                    $sourceref,
                    $itemref,
                    $leafsku,
                    $definition->get_type(),
                    $definition->get_resource_key(),
                    $definition->get_quantity() * $expandeditem->get_quantity(),
                    $userid,
                    (string)$user->email,
                    $plannedat,
                    $duration === null ? null : $plannedat + $duration,
                    $definition->get_configuration(),
                    [
                        'source' => self::SOURCE,
                        'actoruserid' => $actoruserid,
                        'reason' => $reason,
                        'rootproductid' => $productid,
                        'rootsku' => $rootsku,
                        'expandedsku' => $leafsku,
                        'definitionid' => $definition->get_id(),
                        'sortorder' => $definition->get_sort_order(),
                    ]
                );

                $existing = $repository->find_by_idempotency_key(
                    $candidate->get_idempotency_key()
                );
                $grants[] = $existing !== null
                    ? $mapper->from_record($existing)
                    : $candidate;
            }
        }

        if ($grants === []) {
            throw new \moodle_exception('commerce_manual_grant_empty_plan', 'local_subscriptions');
        }

        return new CommerceEntitlementGrantPlan($sourceref, $grants, $plannedat);
    }

    /**
     * @return array{plan:CommerceEntitlementGrantPlan,created:int,identical:int,results:array}
     */
    public function grant(
        int $userid,
        int $productid,
        int $actoruserid,
        string $reason = '',
        ?int $now = null
    ): array {
        $now ??= time();
        $plan = $this->plan($userid, $productid, $actoruserid, $reason, $now);

        $mapper = new CommerceEntitlementGrantRecordMapper();
        $repository = new CommerceEntitlementGrantRepository($this->db, $mapper);
        $persisted = (new CommerceEntitlementGrantPersister(
            $this->db,
            $repository
        ))->persist($plan, $now);

        $registry = new CommerceNativeFulfillmentHandlerRegistry([
            new CommerceCourseAccessFulfillmentHandler(),
            new CommerceDigitalDownloadFulfillmentHandler(),
        ]);
        $executor = new CommercePersistentNativeFulfillmentExecutor(
            $registry,
            new MoodleCommerceNativeFulfillmentPersistenceRepository()
        );

        $context = CommerceNativeFulfillmentContext::runtime(
            'crm-manual-' . substr(hash('sha256', $plan->get_purchase_reference()), 0, 24),
            $now,
            $actoruserid,
            self::SOURCE,
            [
                'reason' => trim($reason),
                'productid' => $productid,
                'userid' => $userid,
            ]
        );

        $results = [];
        foreach ($plan->get_grants() as $grant) {
            $result = $executor->execute($grant, $context);
            $results[] = $result;

            if (!$result->is_completed() && $result->get_status() !== 'skipped') {
                $message = trim((string)$result->get_message());
                throw new \RuntimeException(
                    $message !== '' ? $message : 'Manual Native fulfillment failed.'
                );
            }
        }

        return [
            'plan' => $plan,
            'created' => $persisted->get_created(),
            'identical' => $persisted->get_identical(),
            'results' => $results,
        ];
    }

    private function source_reference(int $userid, int $productid, string $sku): string {
        return 'manual-u' . $userid . '-p' . $productid . '-'
            . substr(hash('sha256', strtoupper(trim($sku))), 0, 16);
    }

    private function item_reference(string $rootsku, string $leafsku, int $position): string {
        return 'manual-item-' . substr(
            hash('sha256', $rootsku . '|' . $leafsku . '|' . $position),
            0,
            32
        );
    }

    private function grant_reference(
        string $source,
        string $item,
        string $type,
        string $resourcekey
    ): string {
        return 'ent-manual-' . substr(
            hash('sha256', $source . '|' . $item . '|' . $type . '|' . $resourcekey),
            0,
            32
        );
    }
}
