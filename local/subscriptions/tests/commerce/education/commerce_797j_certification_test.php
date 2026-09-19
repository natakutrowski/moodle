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
use local_subscriptions\commerce\education\access\CommerceStudentCourseAccess;
use local_subscriptions\commerce\education\access\CommerceStudentCourseAccessRepository;
use local_subscriptions\commerce\education\access\CommerceStudentCourseAccessResolver;
use local_subscriptions\commerce\education\calendar\CommercePedagogicalCalendarItem;
use local_subscriptions\commerce\education\calendar\CommercePedagogicalCalendarRepository;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupConfiguration;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupOrchestrator;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupRepository;
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalLifecycleService;
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalParticipationRepository;
use local_subscriptions\commerce\education\participant\CommercePedagogicalParticipantRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;
use local_subscriptions\commerce\education\purchase\CommercePedagogicalPurchaseOrchestrator;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;

final class commerce_797j_certification_test extends advanced_testcase {
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

    private function promotion(
        int $courseid,
        string $key,
        int $now
    ): CommercePedagogicalPromotion {
        global $DB;

        return CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                null,
                $key,
                'Certification ' . $key,
                $courseid,
                CommercePedagogicalPromotionStatus::OPEN,
                true,
                $now - 3600,
                $now + 86400,
                $now,
                null,
                100,
                null,
                null,
                $now,
                $now
            )
        );
    }

    private function grant(
        string $sku,
        int $courseid,
        int $userid,
        string $email,
        string $purchase
    ): CommerceEntitlementGrant {
        return new CommerceEntitlementGrant(
            'grant-' . strtolower($sku) . '-' . $userid,
            $purchase,
            'item-' . strtolower($sku) . '-' . $userid,
            $sku,
            'course_access',
            'course:' . $courseid . ':full',
            1,
            $userid,
            $email,
            time()
        );
    }

    /**
     * @return int[] course section ids, section numbers 1..3.
     */
    private function three_lesson_calendar(
        int $courseid,
        int $promotionid,
        int $now
    ): array {
        global $DB;

        $sections = array_values($DB->get_records_select(
            'course_sections',
            'course = :course AND section > 0',
            ['course' => $courseid],
            'section ASC'
        ));

        self::assertCount(3, $sections);

        $calendar = CommercePedagogicalCalendarRepository::create($DB);
        $unlocktimes = [$now - 60, $now + 3600, $now + 7200];

        foreach ($sections as $index => $section) {
            $calendar->save(new CommercePedagogicalCalendarItem(
                null,
                $promotionid,
                CommercePedagogicalCalendarItem::TYPE_COURSE_SECTION,
                (int)$section->id,
                $index,
                $unlocktimes[$index],
                null,
                null,
                $now,
                $now
            ));
        }

        return array_map(
            static fn(\stdClass $section): int => (int)$section->id,
            $sections
        );
    }

    public function test_three_lesson_ru_fr_purchase_chain_and_lifetime_transition(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();

        $course = $this->getDataGenerator()->create_course(['numsections' => 3]);
        $legacy = $this->getDataGenerator()->create_user();
        $ruuser = $this->getDataGenerator()->create_user();
        $fruser = $this->getDataGenerator()->create_user();

        // G runs only after successful native course fulfillment in production.
        $this->getDataGenerator()->enrol_user((int)$ruuser->id, (int)$course->id);
        $this->getDataGenerator()->enrol_user((int)$fruser->id, (int)$course->id);

        CommerceCourseAccessConfigurationRepository::create($DB)->set_mode(
            (int)$course->id,
            CommerceCourseAccessMode::PROMOTION
        );

        $promotion = $this->promotion((int)$course->id, 'cert-a1', $now);
        $sectionids = $this->three_lesson_calendar(
            (int)$course->id,
            (int)$promotion->get_id(),
            $now
        );

        $ru = $this->product('CERT-A1-RU');
        $fr = $this->product('CERT-A1-FR');
        $links = CommercePedagogicalPromotionOfferRepository::create($DB);
        $links->link((int)$promotion->get_id(), (int)$ru->get_id(), 60, null, $now);
        $links->link((int)$promotion->get_id(), (int)$fr->get_id(), 40, null, $now);

        $groups = CommercePedagogicalGroupRepository::create($DB);
        $groups->save_configuration(new CommercePedagogicalGroupConfiguration(
            (int)$promotion->get_id(),
            true,
            6,
            null,
            null,
            $now,
            $now
        ));
        $grouporchestrator = CommercePedagogicalGroupOrchestrator::create($DB);
        $rugroup = $grouporchestrator->create_group(
            (int)$promotion->get_id(),
            'Les Cigales RU',
            0,
            null,
            'ru',
            null,
            null,
            null,
            $now,
            null,
            (int)$ru->get_id()
        );
        $frgroup = $grouporchestrator->create_group(
            (int)$promotion->get_id(),
            'Les Cigales FR',
            1,
            null,
            'fr',
            null,
            null,
            null,
            $now,
            null,
            (int)$fr->get_id()
        );

        $orchestrator = CommercePedagogicalPurchaseOrchestrator::create($DB);
        $orchestrator->apply([
            $this->grant(
                'CERT-A1-RU',
                (int)$course->id,
                (int)$ruuser->id,
                $ruuser->email,
                'CERT-PURCHASE-RU'
            ),
        ], $now);
        $orchestrator->apply([
            $this->grant(
                'CERT-A1-FR',
                (int)$course->id,
                (int)$fruser->id,
                $fruser->email,
                'CERT-PURCHASE-FR'
            ),
        ], $now);

        // Historical user: changing the course to promotion mode must not
        // materialise a 7.97 row nor restrict their course.
        $legacydecision = CommerceStudentCourseAccessResolver::create($DB)->resolve(
            (int)$course->id,
            (int)$legacy->id,
            $now
        );
        self::assertSame(
            CommerceStudentAccessProfile::LEGACY_FULL,
            $legacydecision->get_profile()
        );
        self::assertTrue($legacydecision->has_full_course_access());

        foreach ([
            [(int)$ruuser->id, $rugroup],
            [(int)$fruser->id, $frgroup],
        ] as [$userid, $expectedgroup]) {
            $decision = CommerceStudentCourseAccessResolver::create($DB)->resolve(
                (int)$course->id,
                $userid,
                $now
            );

            self::assertSame(
                CommerceStudentAccessProfile::PROMOTION_PROGRESSIVE,
                $decision->get_profile()
            );
            self::assertSame([$sectionids[0]], $decision->get_unlocked_section_ids());
            self::assertTrue($decision->can_access_section($sectionids[0], 1));
            self::assertFalse($decision->can_access_section($sectionids[1], 2));
            self::assertTrue(groups_is_member(
                $expectedgroup->get_moodle_group_id(),
                $userid
            ));
        }

        self::assertCount(
            2,
            CommercePedagogicalParticipantRepository::create($DB)->for_promotion(
                (int)$promotion->get_id()
            )
        );

        // Once every pedagogical item is unlocked, the individual profile is
        // permanently promoted to lifetime_full.
        $future = $now + 10800;
        $lifetime = CommerceStudentCourseAccessResolver::create($DB)->resolve(
            (int)$course->id,
            (int)$ruuser->id,
            $future
        );
        self::assertSame(
            CommerceStudentAccessProfile::LIFETIME_FULL,
            $lifetime->get_profile()
        );
        self::assertTrue($lifetime->has_full_course_access());
    }

    public function test_groups_disabled_do_not_create_membership(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();

        $course = $this->getDataGenerator()->create_course(['numsections' => 3]);
        $user = $this->getDataGenerator()->create_user();

        CommerceCourseAccessConfigurationRepository::create($DB)->set_mode(
            (int)$course->id,
            CommerceCourseAccessMode::PROMOTION
        );
        $promotion = $this->promotion((int)$course->id, 'cert-no-group', $now);
        $this->three_lesson_calendar((int)$course->id, (int)$promotion->get_id(), $now);

        $product = $this->product('CERT-NO-GROUP');
        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(),
            (int)$product->get_id(),
            null,
            null,
            $now
        );

        // Default F configuration is groups disabled.
        CommercePedagogicalPurchaseOrchestrator::create($DB)->apply([
            $this->grant(
                'CERT-NO-GROUP',
                (int)$course->id,
                (int)$user->id,
                $user->email,
                'CERT-PURCHASE-NO-GROUP'
            ),
        ], $now);

        self::assertNull(
            CommercePedagogicalGroupRepository::create($DB)->group_for_user(
                (int)$promotion->get_id(),
                (int)$user->id
            )
        );
    }

    public function test_refund_terminal_state_removes_progressive_lessons_and_group(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();

        $course = $this->getDataGenerator()->create_course(['numsections' => 3]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user((int)$user->id, (int)$course->id);

        CommerceCourseAccessConfigurationRepository::create($DB)->set_mode(
            (int)$course->id,
            CommerceCourseAccessMode::PROMOTION
        );
        $promotion = $this->promotion((int)$course->id, 'cert-refund', $now);
        $this->three_lesson_calendar((int)$course->id, (int)$promotion->get_id(), $now);

        $product = $this->product('CERT-REFUND');
        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(),
            (int)$product->get_id(),
            null,
            null,
            $now
        );

        $groups = CommercePedagogicalGroupRepository::create($DB);
        $groups->save_configuration(new CommercePedagogicalGroupConfiguration(
            (int)$promotion->get_id(),
            true,
            6,
            null,
            null,
            $now,
            $now
        ));
        $group = CommercePedagogicalGroupOrchestrator::create($DB)->create_group(
            (int)$promotion->get_id(),
            'Refund Group',
            0,
            null,
            null,
            null,
            null,
            null,
            $now,
            null,
            (int)$product->get_id()
        );

        CommercePedagogicalPurchaseOrchestrator::create($DB)->apply([
            $this->grant(
                'CERT-REFUND',
                (int)$course->id,
                (int)$user->id,
                $user->email,
                'CERT-PURCHASE-REFUND'
            ),
        ], $now);

        self::assertTrue(groups_is_member(
            $group->get_moodle_group_id(),
            (int)$user->id
        ));

        CommercePedagogicalLifecycleService::create($DB)->terminate_purchase(
            'CERT-PURCHASE-REFUND',
            CommercePedagogicalParticipationRepository::REFUNDED,
            $now + 1
        );

        $decision = CommerceStudentCourseAccessResolver::create($DB)->resolve(
            (int)$course->id,
            (int)$user->id,
            $now + 1
        );

        self::assertSame([], $decision->get_unlocked_section_ids());
        self::assertTrue($decision->can_access_section(0, 0));
        self::assertFalse(groups_is_member(
            $group->get_moodle_group_id(),
            (int)$user->id
        ));
    }

    public function test_pre_i_progressive_relation_keeps_calendar_semantics_without_join_ledger(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();

        $course = $this->getDataGenerator()->create_course(['numsections' => 3]);
        $user = $this->getDataGenerator()->create_user();
        CommerceCourseAccessConfigurationRepository::create($DB)->set_mode(
            (int)$course->id,
            CommerceCourseAccessMode::PROMOTION
        );

        $promotion = $this->promotion((int)$course->id, 'cert-pre-i', $now);
        $sectionids = $this->three_lesson_calendar(
            (int)$course->id,
            (int)$promotion->get_id(),
            $now
        );

        CommerceStudentCourseAccessRepository::create($DB)->save(
            new CommerceStudentCourseAccess(
                null,
                (int)$course->id,
                (int)$user->id,
                (int)$promotion->get_id(),
                CommerceStudentAccessProfile::PROMOTION_PROGRESSIVE,
                null,
                null,
                $now,
                $now
            )
        );

        self::assertFalse(
            CommercePedagogicalParticipationRepository::create($DB)->has_history(
                (int)$promotion->get_id(),
                (int)$user->id
            )
        );

        $decision = CommerceStudentCourseAccessResolver::create($DB)->resolve(
            (int)$course->id,
            (int)$user->id,
            $now
        );
        self::assertSame([$sectionids[0]], $decision->get_unlocked_section_ids());
    }

    public function test_797_certification_source_contract(): void {
        $root = dirname(__DIR__, 3);

        foreach (range('a', 'i') as $phase) {
            $matches = glob(
                $root . '/tests/commerce/education/commerce_797'
                . $phase
                . '_*.php'
            );
            self::assertNotEmpty(
                $matches,
                'Missing 7.97' . strtoupper($phase) . ' focused test.'
            );
        }

        $version = file_get_contents($root . '/version.php');
        self::assertMatchesRegularExpression(
            '/\\$plugin->version\\s*=\\s*2026091902\\s*;/',
            $version
        );

        $promotiondomain = file_get_contents(
            $root . '/classes/commerce/education/promotion/CommercePedagogicalPromotion.php'
        );
        self::assertStringContainsString(
            'separate from commerce\\promotion\\domain\\CommercePromotion',
            $promotiondomain
        );
    }
}
