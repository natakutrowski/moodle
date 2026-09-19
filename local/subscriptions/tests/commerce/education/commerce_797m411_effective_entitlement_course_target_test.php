<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinEligibility;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinEligibilityService;

/** M4.1.1: promotion-join course matching follows effective Native entitlements. */
final class commerce_797m411_effective_entitlement_course_target_test extends advanced_testcase {
    public function test_access_scope_only_course_product_matches_its_promotion_course(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $scopeid = (int)$DB->insert_record('subscription_access_scope', (object)[
            'name' => 'M4.1.1 scope',
            'course_ids' => json_encode([(int)$course->id]),
            'creation_date' => $now,
            'last_update' => $now,
        ]);

        $products = new CommerceProductRepository($DB, new CommerceCatalogHydrator());
        $product = $products->save(new CommerceProduct(
            'M411-SCOPE',
            CommerceProductType::COURSE_ACCESS,
            CommerceProductStatus::ACTIVE,
            'M4.1.1 Scope Product',
            '',
            ['access' => ['scopeid' => $scopeid]]
        ));

        // Intentionally no local_subs_commerce_prod_ent rows: Native fulfillment
        // resolves this product through its effective Access Scope.
        self::assertSame(0, $DB->count_records('local_subs_commerce_prod_ent', [
            'productid' => (int)$product->get_id(),
        ]));

        $promotion = CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                null,
                'm411-scope',
                'M4.1.1 Scope Promotion',
                (int)$course->id,
                CommercePedagogicalPromotionStatus::OPEN,
                true,
                $now - HOURSECS,
                $now + DAYSECS,
                $now + DAYSECS,
                null,
                3,
                null,
                null,
                $now,
                $now
            )
        );

        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(),
            (int)$product->get_id(),
            3,
            null,
            $now
        );

        $DB->insert_record('local_subs_commerce_grant', (object)[
            'grantreference' => 'grant-m411-scope',
            'idempotencykey' => 'idem-m411-scope',
            'purchasereference' => 'purchase-m411-scope',
            'itemreference' => 'item-m411-scope',
            'productsku' => 'M411-SCOPE',
            'type' => 'course_access',
            'resourcekey' => 'course:' . (int)$course->id . ':full',
            'quantity' => 1,
            'beneficiaryuserid' => (int)$user->id,
            'beneficiaryemail' => (string)$user->email,
            'validfrom' => $now - 10,
            'validuntil' => null,
            'status' => 'active',
            'configurationjson' => json_encode([
                'courseid' => (int)$course->id,
                'accesslevel' => 'full',
                'legacysource' => 'native_access_scope',
                'legacyscopeid' => $scopeid,
            ]),
            'metadatajson' => '{}',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $decision = CommercePedagogicalPromotionJoinEligibilityService::create($DB)
            ->resolve((int)$user->id, 'M411-SCOPE', $now);

        self::assertTrue($decision->is_eligible());
        self::assertNull($decision->get_reason());
        self::assertSame('native_entitlement', $decision->get_ownership_source());
        self::assertSame((int)$course->id, $decision->get_context()?->get_course_id());
        self::assertSame((int)$promotion->get_id(), $decision->get_context()?->get_promotion_id());
    }

    public function test_access_scope_only_product_is_rejected_for_another_course(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $ownedcourse = $this->getDataGenerator()->create_course();
        $promotioncourse = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $scopeid = (int)$DB->insert_record('subscription_access_scope', (object)[
            'name' => 'M4.1.1 mismatch scope',
            'course_ids' => json_encode([(int)$ownedcourse->id]),
            'creation_date' => $now,
            'last_update' => $now,
        ]);

        $products = new CommerceProductRepository($DB, new CommerceCatalogHydrator());
        $product = $products->save(new CommerceProduct(
            'M411-MISMATCH',
            CommerceProductType::COURSE_ACCESS,
            CommerceProductStatus::ACTIVE,
            'M4.1.1 Mismatch Product',
            '',
            ['access' => ['scopeid' => $scopeid]]
        ));

        $promotion = CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                null,
                'm411-mismatch',
                'M4.1.1 Mismatch Promotion',
                (int)$promotioncourse->id,
                CommercePedagogicalPromotionStatus::OPEN,
                true,
                $now - HOURSECS,
                $now + DAYSECS,
                $now + DAYSECS,
                null,
                3,
                null,
                null,
                $now,
                $now
            )
        );
        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(),
            (int)$product->get_id(),
            3,
            null,
            $now
        );

        $DB->insert_record('local_subs_commerce_grant', (object)[
            'grantreference' => 'grant-m411-mismatch',
            'idempotencykey' => 'idem-m411-mismatch',
            'purchasereference' => 'purchase-m411-mismatch',
            'itemreference' => 'item-m411-mismatch',
            'productsku' => 'M411-MISMATCH',
            'type' => 'course_access',
            'resourcekey' => 'course:' . (int)$ownedcourse->id . ':full',
            'quantity' => 1,
            'beneficiaryuserid' => (int)$user->id,
            'beneficiaryemail' => (string)$user->email,
            'validfrom' => $now - 10,
            'validuntil' => null,
            'status' => 'active',
            'configurationjson' => json_encode(['courseid' => (int)$ownedcourse->id]),
            'metadatajson' => '{}',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $decision = CommercePedagogicalPromotionJoinEligibilityService::create($DB)
            ->resolve((int)$user->id, 'M411-MISMATCH', $now);

        self::assertFalse($decision->is_eligible());
        self::assertSame(
            CommercePedagogicalPromotionJoinEligibility::COURSE_MISMATCH,
            $decision->get_reason()
        );
    }
}
