<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\purchase;

defined('MOODLE_INTERNAL') || die();

use core\lock\lock_config;
use local_subscriptions\commerce\education\access\CommerceCourseAccessConfigurationRepository;
use local_subscriptions\commerce\education\access\CommerceCourseAccessMode;
use local_subscriptions\commerce\education\access\CommerceStudentAccessProfile;
use local_subscriptions\commerce\education\access\CommerceStudentCourseAccess;
use local_subscriptions\commerce\education\access\CommerceStudentCourseAccessRepository;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupOrchestrator;
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalParticipationRepository;
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalSalePolicy;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationService;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationRepository;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservation;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinGrant;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\fulfillment\native\course\CommerceCourseAccessFulfillmentHandler;
use local_subscriptions\commerce\fulfillment\native\course\CommerceCourseAccessGrant;

/**
 * Bridges successfully fulfilled Native Commerce education grants to 7.97 pedagogy.
 *
 * Classic course_access purchases are untouched. Promotion-mode course purchases
 * become progressive only through an explicit offer link, while promotion_join
 * grants attach an existing owner to the pinned promotion without mutating the
 * underlying Moodle course entitlement.
 */
final class CommercePedagogicalPurchaseOrchestrator {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceCourseAccessConfigurationRepository $courseconfig,
        private readonly CommercePedagogicalPromotionOfferRepository $offers,
        private readonly CommerceStudentCourseAccessRepository $access,
        private readonly CommercePedagogicalGroupOrchestrator $groups,
        private readonly CommercePedagogicalSalePolicy $salepolicy,
        private readonly CommercePedagogicalParticipationRepository $participations,
        private readonly CommercePedagogicalSeatReservationService $reservations
    ) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        $db = $db ?? $DB;

        return new self(
            $db,
            CommerceCourseAccessConfigurationRepository::create($db),
            CommercePedagogicalPromotionOfferRepository::create($db),
            CommerceStudentCourseAccessRepository::create($db),
            CommercePedagogicalGroupOrchestrator::create($db),
            CommercePedagogicalSalePolicy::create($db),
            CommercePedagogicalParticipationRepository::create($db),
            CommercePedagogicalSeatReservationService::create($db)
        );
    }

    /**
     * @param CommerceEntitlementGrant[] $grants
     */
    public function apply(
        array $grants,
        int $now,
        ?string $cartuuid = null,
        array $preexistingfullcourseaccess = []
    ): void {
        $products = new CommerceProductRepository(
            $this->db,
            new CommerceCatalogHydrator()
        );
        $reservationrepository =
            CommercePedagogicalSeatReservationRepository::create($this->db);
        $promotionrepository =
            CommercePedagogicalPromotionRepository::create($this->db);

        foreach ($grants as $grant) {
            if (!$grant instanceof CommerceEntitlementGrant) {
                throw new \coding_exception('Invalid Commerce grant passed to pedagogical purchase orchestration.');
            }

            if ($grant->get_type() === CommercePedagogicalPromotionJoinGrant::GRANT_TYPE) {
                $this->apply_promotion_join(
                    $grant,
                    $now,
                    $cartuuid,
                    $products,
                    $reservationrepository,
                    $promotionrepository
                );
                continue;
            }

            if ($grant->get_type() !== CommerceCourseAccessFulfillmentHandler::GRANT_TYPE) {
                continue;
            }

            $userid = $grant->get_beneficiary_user_id();
            if ($userid === null) {
                continue;
            }

            $coursegrant = CommerceCourseAccessGrant::from_grant($grant);
            $courseid = $coursegrant->get_course_id();

            if ($this->courseconfig->mode_for_course($courseid) !== CommerceCourseAccessMode::PROMOTION) {
                continue;
            }

            $product = $products->find_by_sku($grant->get_product_sku());
            if ($product === null) {
                continue;
            }
            $productid = (int)$product->get_id();

            // A cart reservation pins the purchase to the exact cohort that
            // owned the sale, even when a later cohort reuses the same stable
            // Commerce product before fulfillment completes.
            $pinnedreservation = $cartuuid !== null
                ? $reservationrepository->find_for_cart_product(
                    $cartuuid,
                    $productid
                )
                : null;

            if ($pinnedreservation !== null) {
                if (
                    !$pinnedreservation->is_active_at($now)
                    && $pinnedreservation->get_state()
                        !== CommercePedagogicalSeatReservation::CONSUMED
                ) {
                    throw new \RuntimeException(
                        'The pedagogical seat reservation expired before fulfillment.'
                    );
                }

                $promotion = $promotionrepository->get_by_id(
                    $pinnedreservation->get_promotion_id()
                );
                if (
                    $promotion === null
                    || $promotion->get_course_id() !== $courseid
                ) {
                    throw new \coding_exception(
                        'Pedagogical reservation does not match the fulfilled course.'
                    );
                }
            } else {
                $promotion = $this->offers->promotion_for_product_and_course(
                    $grant->get_product_sku(),
                    $courseid,
                    $now
                );

                // Safe fallback: promotion mode alone never makes a historical
                // or unrelated purchase progressive. The explicit product link
                // is the trigger.
                if ($promotion === null) {
                    continue;
                }

                // Without a pinned hold, the normal current-sale policy remains
                // authoritative.
                $this->salepolicy->assert_product_available(
                    $grant->get_product_sku(),
                    $now,
                    null
                );
            }

            $exactlink = $this->offers->link_for_promotion_and_product(
                (int)$promotion->get_id(),
                $productid
            );
            if ($exactlink === null) {
                throw new \coding_exception(
                    'Pedagogical offer link disappeared during purchase orchestration.'
                );
            }

            $existing = $this->access->find($courseid, $userid);
            $accesskey = $userid . ':' . $courseid;

            // M4.8.1: the pre-fulfillment Moodle snapshot is only allowed to
            // establish historical full access on the first pedagogical write.
            // Once this bridge has already classified the user as progressive,
            // a late/replayed callback must not reinterpret the enrolment created
            // by this same Commerce purchase as pre-existing full access. Actual
            // lifetime/legacy profiles remain monotonic and are always preserved.
            $existinghasfullaccess =
                $existing !== null
                && CommerceStudentAccessProfile::has_full_course_access(
                    $existing->get_profile()
                );
            $snapshotestablishesfullaccess =
                $existing === null
                && !empty($preexistingfullcourseaccess[$accesskey]);
            $mustpreservefullaccess =
                $existinghasfullaccess || $snapshotestablishesfullaccess;

            $createdby = $existing?->get_created_by();
            $timecreated = $existing?->get_time_created() ?: $now;

            $this->access->save(
                new CommerceStudentCourseAccess(
                    $existing?->get_id(),
                    $courseid,
                    $userid,
                    (int)$promotion->get_id(),
                    $mustpreservefullaccess
                        ? CommerceStudentAccessProfile::LIFETIME_FULL
                        : CommerceStudentAccessProfile::PROMOTION_PROGRESSIVE,
                    $createdby,
                    null,
                    $timecreated,
                    $now
                )
            );

            $this->participations->record_active(
                (int)$promotion->get_id(),
                $courseid,
                $userid,
                $grant->get_product_sku(),
                $grant->get_purchase_reference(),
                $now
            );

            $this->groups->assign_first_available_for_product(
                (int)$promotion->get_id(),
                $productid,
                $userid,
                null,
                $now
            );

            // Keep the reservation active until participation + group
            // assignment exist. This prevents another cart from stealing the
            // seat in the small post-payment fulfillment window.
            if ($cartuuid !== null) {
                $consumed = $this->reservations->consume(
                    $grant->get_product_sku(),
                    $cartuuid,
                    $grant->get_purchase_reference(),
                    $now
                );

                if (!$consumed) {
                    throw new \RuntimeException(
                        'The pedagogical seat reservation could not be consumed after fulfillment.'
                    );
                }
            }
        }
    }

    private function apply_promotion_join(
        CommerceEntitlementGrant $grant,
        int $now,
        ?string $cartuuid,
        CommerceProductRepository $products,
        CommercePedagogicalSeatReservationRepository $reservationrepository,
        CommercePedagogicalPromotionRepository $promotionrepository
    ): void {
        $join = CommercePedagogicalPromotionJoinGrant::from_grant($grant);
        $userid = $join->get_user_id();
        $courseid = $join->get_course_id();
        $promotionid = $join->get_promotion_id();
        $productid = $join->get_product_id();

        if ($this->courseconfig->mode_for_course($courseid) !== CommerceCourseAccessMode::PROMOTION) {
            throw new \RuntimeException('Promotion join fulfillment requires a course in promotion mode.');
        }

        $product = $products->find_by_sku($join->get_product_sku());
        if (
            $product === null
            || $product->get_id() === null
            || (int)$product->get_id() !== $productid
        ) {
            throw new \coding_exception('Promotion join grant product identity no longer matches the catalogue.');
        }

        $promotion = $promotionrepository->get_by_id($promotionid);
        if ($promotion === null || $promotion->get_course_id() !== $courseid) {
            throw new \coding_exception('Promotion join grant no longer matches its pedagogical course.');
        }

        if ($this->offers->link_for_promotion_and_product($promotionid, $productid) === null) {
            throw new \coding_exception('Promotion join pedagogical offer link disappeared before fulfillment.');
        }

        if ($cartuuid === null || !preg_match('/^[a-f0-9]{32}$/', strtolower(trim($cartuuid)))) {
            throw new \RuntimeException('Promotion join fulfillment requires its pinned checkout reservation.');
        }
        $cartuuid = strtolower(trim($cartuuid));

        $reservation = $reservationrepository->find_for_cart_product($cartuuid, $productid);
        if ($reservation === null) {
            throw new \RuntimeException('Promotion join fulfillment lost its pedagogical seat reservation.');
        }
        if (
            $reservation->get_promotion_id() !== $promotionid
            || $reservation->get_product_id() !== $productid
            || $reservation->get_customer_id() !== $userid
            || $reservation->get_quantity() !== 1
        ) {
            throw new \coding_exception('Promotion join reservation does not match the paid canonical context.');
        }
        if (
            !$reservation->is_active_at($now)
            && $reservation->get_state() !== CommercePedagogicalSeatReservation::CONSUMED
        ) {
            throw new \RuntimeException('The promotion join seat reservation expired before fulfillment.');
        }
        if (
            $reservation->get_state() === CommercePedagogicalSeatReservation::CONSUMED
            && !hash_equals(
                (string)$reservation->get_purchase_reference(),
                $grant->get_purchase_reference()
            )
        ) {
            throw new \coding_exception('Promotion join reservation was consumed by another purchase.');
        }

        $this->with_promotion_join_lock(
            $promotionid,
            $userid,
            function () use (
                $grant,
                $join,
                $userid,
                $courseid,
                $promotionid,
                $productid,
                $cartuuid,
                $now
            ): void {
                if ($this->participations->is_active($promotionid, $userid)) {
                    $samepurchase = false;
                    foreach (
                        $this->participations->active_for_purchase(
                            $grant->get_purchase_reference()
                        ) as $participation
                    ) {
                        if (
                            (int)$participation->promotionid === $promotionid
                            && (int)$participation->userid === $userid
                        ) {
                            $samepurchase = true;
                            break;
                        }
                    }

                    if (!$samepurchase) {
                        throw new \RuntimeException(
                            'Promotion join fulfillment refused a second active purchase for the same participant.'
                        );
                    }
                }

                $existing = $this->access->find($courseid, $userid);
                $this->access->save(
                    new CommerceStudentCourseAccess(
                        $existing?->get_id(),
                        $courseid,
                        $userid,
                        $promotionid,
                        CommerceStudentAccessProfile::LIFETIME_FULL,
                        $existing?->get_created_by(),
                        null,
                        $existing?->get_time_created() ?: $now,
                        $now
                    )
                );

                $this->participations->record_active(
                    $promotionid,
                    $courseid,
                    $userid,
                    $join->get_product_sku(),
                    $grant->get_purchase_reference(),
                    $now
                );

                $this->groups->assign_first_available_for_product(
                    $promotionid,
                    $productid,
                    $userid,
                    null,
                    $now
                );

                $consumed = $this->reservations->consume(
                    $join->get_product_sku(),
                    $cartuuid,
                    $grant->get_purchase_reference(),
                    $now
                );
                if (!$consumed) {
                    throw new \RuntimeException(
                        'The promotion join seat reservation could not be consumed after fulfillment.'
                    );
                }
            }
        );
    }

    private function with_promotion_join_lock(
        int $promotionid,
        int $userid,
        callable $operation
    ): mixed {
        $factory = lock_config::get_lock_factory(
            'local_subscriptions_commerce_pedagogical_join'
        );
        $lock = $factory->get_lock(
            'promotion:' . $promotionid . ':user:' . $userid,
            10
        );
        if ($lock === false) {
            throw new \RuntimeException(
                'Unable to acquire pedagogical promotion join lock.'
            );
        }

        try {
            return $operation();
        } finally {
            $lock->release();
        }
    }
}
