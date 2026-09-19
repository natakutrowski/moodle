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
use local_subscriptions\commerce\education\access\CommerceCourseAccessConfigurationRepository;
use local_subscriptions\commerce\education\access\CommerceCourseAccessMode;
use local_subscriptions\commerce\education\access\CommerceStudentAccessProfile;
use local_subscriptions\commerce\education\access\CommerceStudentCourseAccessRepository;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupConfiguration;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupOrchestrator;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;
use local_subscriptions\commerce\education\purchase\CommercePedagogicalPurchaseOrchestrator;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;

final class commerce_797g_commerce_promotion_bridge_test extends advanced_testcase {
    private function promotion(int $courseid): CommercePedagogicalPromotion {
        global $DB;
        $now = time();
        return CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                null, 'a1-797g', 'A1 7.97G', $courseid,
                CommercePedagogicalPromotionStatus::OPEN, true,
                null, null, $now, null, null, null, null, $now, $now
            )
        );
    }

    private function product(string $sku): CommerceProduct {
        global $DB;
        return (new CommerceProductRepository($DB, new CommerceCatalogHydrator()))->save(
            new CommerceProduct(
                $sku,
                CommerceProductType::COURSE_ACCESS,
                CommerceProductStatus::ACTIVE,
                $sku
            )
        );
    }

    private function grant(string $sku, int $courseid, int $userid, string $email): CommerceEntitlementGrant {
        return new CommerceEntitlementGrant(
            'grant-' . strtolower($sku),
            'purchase-797g',
            'item-' . strtolower($sku),
            $sku,
            'course_access',
            'course:' . $courseid . ':full',
            1,
            $userid,
            $email,
            time()
        );
    }

    public function test_linked_product_purchase_creates_progressive_access(): void {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $promotion = $this->promotion((int)$course->id);
        $product = $this->product('A1-RU');

        CommerceCourseAccessConfigurationRepository::create($DB)->set_mode(
            (int)$course->id,
            CommerceCourseAccessMode::PROMOTION
        );
        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(),
            (int)$product->get_id(),
            100,
            null,
            time()
        );

        CommercePedagogicalPurchaseOrchestrator::create($DB)->apply(
            [$this->grant('A1-RU', (int)$course->id, (int)$user->id, $user->email)],
            time()
        );

        $access = CommerceStudentCourseAccessRepository::create($DB)->find(
            (int)$course->id,
            (int)$user->id
        );

        self::assertNotNull($access);
        self::assertSame(CommerceStudentAccessProfile::PROMOTION_PROGRESSIVE, $access->get_profile());
        self::assertSame((int)$promotion->get_id(), $access->get_promotion_id());
    }

    public function test_two_products_can_join_same_promotion(): void {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $promotion = $this->promotion((int)$course->id);
        $ru = $this->product('A1-RU');
        $fr = $this->product('A1-FR');
        $links = CommercePedagogicalPromotionOfferRepository::create($DB);

        $links->link((int)$promotion->get_id(), (int)$ru->get_id(), null, null, time());
        $links->link((int)$promotion->get_id(), (int)$fr->get_id(), 50, null, time());

        self::assertCount(2, $links->links_for_promotion((int)$promotion->get_id()));
    }

    public function test_classic_course_is_not_changed_by_bridge(): void {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $promotion = $this->promotion((int)$course->id);
        $product = $this->product('CLASSIC-A1');

        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(), (int)$product->get_id(), null, null, time()
        );

        CommercePedagogicalPurchaseOrchestrator::create($DB)->apply(
            [$this->grant('CLASSIC-A1', (int)$course->id, (int)$user->id, $user->email)],
            time()
        );

        self::assertNull(
            CommerceStudentCourseAccessRepository::create($DB)->find(
                (int)$course->id,
                (int)$user->id
            )
        );
    }

    public function test_enabled_groups_are_assigned_after_purchase(): void {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user((int)$user->id, (int)$course->id);
        $promotion = $this->promotion((int)$course->id);
        $product = $this->product('A1-GROUPED');
        $now = time();

        CommerceCourseAccessConfigurationRepository::create($DB)->set_mode(
            (int)$course->id,
            CommerceCourseAccessMode::PROMOTION
        );
        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(), (int)$product->get_id(), null, null, $now
        );

        $groups = CommercePedagogicalGroupRepository::create($DB);
        $groups->save_configuration(new CommercePedagogicalGroupConfiguration(
            (int)$promotion->get_id(), true, 6, null, null, $now, $now
        ));
        $group = CommercePedagogicalGroupOrchestrator::create($DB)->create_group(
            (int)$promotion->get_id(),
            'Les Cigales',
            0,
            null,
            'ru',
            null,
            null,
            null,
            $now,
            null,
            (int)$product->get_id()
        );

        CommercePedagogicalPurchaseOrchestrator::create($DB)->apply(
            [$this->grant('A1-GROUPED', (int)$course->id, (int)$user->id, $user->email)],
            $now
        );

        self::assertTrue(groups_is_member($group->get_moodle_group_id(), (int)$user->id));
        self::assertSame(
            $group->get_id(),
            $groups->group_for_user((int)$promotion->get_id(), (int)$user->id)?->get_id()
        );
    }

    public function test_paid_purchase_completer_invokes_bridge_only_after_successful_fulfillment(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/classes/commerce/fulfillment/native/checkout/CommerceNativePaidPurchaseCompleter.php'
        );

        $successpos = strpos(
            $source,
            'if ($batch->count() === 0 || !$batch->is_successful())'
        );
        $bridgepos = strpos($source, 'CommercePedagogicalPurchaseOrchestrator::create');

        self::assertNotFalse($successpos);
        self::assertNotFalse($bridgepos);
        self::assertGreaterThan($successpos, $bridgepos);
    }

    public function test_offer_schema_does_not_duplicate_product_foreign_key_as_index(): void {
        $root = dirname(__DIR__, 3);
        $install = file_get_contents($root . '/db/install.xml');
        $upgrade = file_get_contents($root . '/db/upgrade.php');

        self::assertStringContainsString(
            '<KEY NAME="product_fk" TYPE="foreign" FIELDS="productid"',
            $install
        );
        self::assertStringContainsString(
            '<INDEX NAME="promotion_product_uix" UNIQUE="true" FIELDS="promotionid,productid" />',
            $install
        );
        self::assertStringNotContainsString(
            '<INDEX NAME="product_idx" UNIQUE="false" FIELDS="productid" />',
            $install
        );
        self::assertStringNotContainsString(
            "add_index('product_idx'",
            $upgrade
        );
    }

}
