<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\promotionjoin;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductEntitlementRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\catalog\service\CommerceEffectiveEntitlementResolver;
use local_subscriptions\commerce\education\capacity\CommercePedagogicalCapacityService;
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalParticipationRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationRepository;
use local_subscriptions\commerce\fulfillment\native\course\CommerceCourseAccessFulfillmentHandler;
use local_subscriptions\commerce\storefront\ownership\CommerceStorefrontOwnershipResolver;

/** Authoritative eligibility for an existing Commerce owner joining a cohort. */
final class CommercePedagogicalPromotionJoinEligibilityService {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceStorefrontOwnershipResolver $ownership,
        private readonly CommercePedagogicalPromotionOfferRepository $offers,
        private readonly CommercePedagogicalParticipationRepository $participations,
        private readonly CommercePedagogicalCapacityService $capacity,
        private readonly CommercePedagogicalSeatReservationRepository $reservations,
        private readonly CommerceProductRepository $products,
        private readonly CommerceEffectiveEntitlementResolver $effectiveentitlements
    ) {
    }

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        $db = $db ?? $DB;
        $hydrator = new CommerceCatalogHydrator();
        $products = new CommerceProductRepository($db, $hydrator);

        $persistedentitlements = new CommerceProductEntitlementRepository($db, $hydrator, $products);

        return new self(
            $db,
            new CommerceStorefrontOwnershipResolver($db),
            CommercePedagogicalPromotionOfferRepository::create($db),
            CommercePedagogicalParticipationRepository::create($db),
            CommercePedagogicalCapacityService::create($db),
            CommercePedagogicalSeatReservationRepository::create($db),
            $products,
            new CommerceEffectiveEntitlementResolver($db, $products, $persistedentitlements)
        );
    }

    public function resolve(
        int $userid,
        string $productsku,
        int $now,
        ?string $excludedcartuuid = null
    ): CommercePedagogicalPromotionJoinEligibility {
        $sku = strtoupper(trim($productsku));
        if ($userid <= 0) {
            return CommercePedagogicalPromotionJoinEligibility::denied(
                CommercePedagogicalPromotionJoinEligibility::AUTHENTICATION_REQUIRED
            );
        }

        $ownershipsource = $this->ownership->resolve_source($userid, $sku);
        if ($ownershipsource === 'none') {
            return CommercePedagogicalPromotionJoinEligibility::denied(
                CommercePedagogicalPromotionJoinEligibility::OWNERSHIP_REQUIRED
            );
        }

        $link = $this->offers->sale_link_for_product($sku, $now);
        if ($link === null) {
            return CommercePedagogicalPromotionJoinEligibility::denied(
                CommercePedagogicalPromotionJoinEligibility::NO_PROMOTION,
                $ownershipsource
            );
        }

        $promotion = $link['promotion'];
        $offer = $link['offer'];
        $product = $this->products->find_by_sku($sku);
        if ($product === null || $product->get_id() === null) {
            return CommercePedagogicalPromotionJoinEligibility::denied(
                CommercePedagogicalPromotionJoinEligibility::UNSUPPORTED_PRODUCT,
                $ownershipsource
            );
        }

        if (!in_array($product->get_type(), ['course_access', 'subscription'], true)) {
            return CommercePedagogicalPromotionJoinEligibility::denied(
                CommercePedagogicalPromotionJoinEligibility::UNSUPPORTED_PRODUCT,
                $ownershipsource
            );
        }

        $courseid = $promotion->get_course_id();
        if (!$this->product_targets_course($sku, $courseid)) {
            return CommercePedagogicalPromotionJoinEligibility::denied(
                CommercePedagogicalPromotionJoinEligibility::COURSE_MISMATCH,
                $ownershipsource
            );
        }

        $promotionid = (int)$promotion->get_id();
        $snapshot = $this->capacity->for_product($sku, $now, $excludedcartuuid);
        if ($snapshot->get_promotion_id() !== $promotionid) {
            throw new \coding_exception(
                'Promotion join eligibility resolved inconsistent pedagogical promotion state.'
            );
        }
        $context = new CommercePedagogicalPromotionJoinContext(
            $userid,
            $sku,
            (int)$offer->productid,
            $ownershipsource,
            $promotionid,
            $promotion->get_promotion_key(),
            $promotion->get_name(),
            $courseid,
            $snapshot->get_remaining()
        );

        if ($this->participations->is_active($promotionid, $userid)) {
            return CommercePedagogicalPromotionJoinEligibility::denied(
                CommercePedagogicalPromotionJoinEligibility::ALREADY_JOINED,
                $ownershipsource,
                $context
            );
        }

        if ($this->reservations->has_active_for_customer_offer(
            $promotionid,
            (int)$offer->productid,
            $userid,
            $now,
            $excludedcartuuid
        )) {
            return CommercePedagogicalPromotionJoinEligibility::denied(
                CommercePedagogicalPromotionJoinEligibility::JOIN_IN_PROGRESS,
                $ownershipsource,
                $context
            );
        }

        if (!$snapshot->is_available()) {
            return CommercePedagogicalPromotionJoinEligibility::denied(
                $snapshot->get_blocking_reason()
                    ?? CommercePedagogicalPromotionJoinEligibility::NO_PROMOTION,
                $ownershipsource,
                $context
            );
        }

        return CommercePedagogicalPromotionJoinEligibility::allowed($context);
    }

    private function product_targets_course(string $sku, int $courseid): bool {
        // Use the same effective entitlement contract as Native fulfillment.
        // Course-access products may legitimately have no persisted entitlement
        // rows: their access can be resolved from the product's effective
        // Access Scope. Promotion-join course matching must therefore never
        // inspect only local_subs_commerce_prod_ent.
        foreach ($this->effectiveentitlements->resolve_by_product_sku($sku) as $definition) {
            if ($definition->get_type() !== CommerceCourseAccessFulfillmentHandler::GRANT_TYPE) {
                continue;
            }

            $resolvedcourseid = $this->course_id_from_definition(
                $definition->get_resource_key(),
                $definition->get_configuration()
            );
            if ($resolvedcourseid === $courseid) {
                return true;
            }
        }

        $product = $this->products->find_by_sku($sku);
        if ($product === null || $product->get_id() === null) {
            return false;
        }
        $mapping = $this->legacy_plan_mapping((int)$product->get_id());
        if ($mapping === null) {
            return false;
        }

        return $this->legacy_plan_targets_course($mapping, $courseid);
    }


    private function legacy_plan_mapping(int $productid): ?int {
        $mapping = $this->db->get_record(
            'local_subs_commerce_prod_map',
            [
                'productid' => $productid,
                'legacytable' => 'subscription_plan',
            ],
            'legacyid',
            IGNORE_MISSING
        );
        return $mapping ? (int)$mapping->legacyid : null;
    }

    private function legacy_plan_targets_course(int $planid, int $courseid): bool {
        [$levelsql, $params] = $this->db->get_in_or_equal(
            ['full', 'subscriber'],
            SQL_PARAMS_NAMED,
            'joinlevel'
        );
        $params['planid'] = $planid;
        $params['courseid'] = $courseid;

        return $this->db->record_exists_sql(
            "SELECT 1
               FROM {subscription_plan_entitlement}
              WHERE planid = :planid
                AND courseid = :courseid
                AND accesslevel {$levelsql}",
            $params
        );
    }

    /** @param array<string,mixed> $configuration */
    private function course_id_from_definition(string $resourcekey, array $configuration): int {
        if (preg_match('/^course:(\d+)(?::[a-z0-9_-]+)?$/i', trim($resourcekey), $matches) === 1) {
            return (int)$matches[1];
        }

        return max(0, (int)($configuration['courseid'] ?? $configuration['course_id'] ?? 0));
    }
}
