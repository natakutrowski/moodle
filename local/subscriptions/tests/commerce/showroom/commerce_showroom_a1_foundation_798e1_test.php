<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductEntitlementDefinition;
use local_subscriptions\commerce\catalog\domain\CommerceProductPrice;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductEntitlementRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductPriceRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\domain\value\CommerceMoney;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupConfiguration;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupOrchestrator;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinPricingService;
use local_subscriptions\commerce\showroom\a1\CommerceA1ShowroomFoundationService;
use local_subscriptions\commerce\showroom\CommerceShowroomDefinition;
use local_subscriptions\commerce\showroom\CommerceShowroomPresenter;
use local_subscriptions\commerce\showroom\CommerceShowroomProductResolver;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;

/** 7.98E.1 — A1 Showroom foundation and existing-promotion bridge. */
final class commerce_showroom_a1_foundation_798e1_test extends \advanced_testcase {
    public function test_foundation_creates_hidden_draft_without_separate_a0_product(): void {
        global $DB;
        $this->resetAfterTest(true);

        $service = CommerceA1ShowroomFoundationService::create($DB);
        $showroomid = $service->ensure(2);
        $repository = new CommerceShowroomCmsRepository($DB);
        $showroom = $repository->get($showroomid);

        self::assertNotNull($showroom);
        self::assertSame(CommerceA1ShowroomFoundationService::SHOWROOM_KEY, $showroom->showroomkey);
        self::assertSame('draft', $showroom->status);
        self::assertSame('local_subscriptions/showroom/default', $showroom->template);
        self::assertSame('', $showroom->slugfr);
        self::assertSame('', $showroom->slugen);
        self::assertSame('', $showroom->slugru);

        $products = json_decode((string)$showroom->productsjson, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame([
            CommerceA1ShowroomFoundationService::PRODUCT_ROLE_FR => CommerceA1ShowroomFoundationService::PRODUCT_SKU_FR,
            CommerceA1ShowroomFoundationService::PRODUCT_ROLE_RU => CommerceA1ShowroomFoundationService::PRODUCT_SKU_RU,
        ], $products);
        self::assertArrayNotHasKey('a0', $products);

        $settings = json_decode((string)$showroom->settingsjson, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('a1_course', $settings['foundation']['kind']);
        self::assertTrue($settings['foundation']['a0included']);

        self::assertSame(
            ['hero', 'audience_profiles', 'content_highlights', 'video', 'offers', 'faq', 'final_cta'],
            array_map(
                static fn(\stdClass $block): string => (string)$block->blocktype,
                $repository->blocks($showroomid)
            )
        );

        self::assertSame($showroomid, $service->ensure(2));
        self::assertCount(1, $DB->get_records('local_subs_showroom', [
            'showroomkey' => CommerceA1ShowroomFoundationService::SHOWROOM_KEY,
        ]));
        self::assertCount(7, $repository->blocks($showroomid));
    }

    public function test_foundation_never_overwrites_an_existing_editorial_record(): void {
        global $DB;
        $this->resetAfterTest(true);

        $repository = new CommerceShowroomCmsRepository($DB);
        $existingid = $repository->save([
            'showroomkey' => CommerceA1ShowroomFoundationService::SHOWROOM_KEY,
            'status' => 'draft',
            'name' => 'A1 déjà édité',
            'template' => 'local_subscriptions/showroom/default',
            'slugfr' => 'a1-personnalise',
            'slugen' => '',
            'slugru' => '',
            'titlekey' => 'commerce_showroom_a1_title',
            'descriptionkey' => 'commerce_showroom_a1_description',
            'productsjson' => json_encode(['course' => 'A1.CUSTOM'], JSON_THROW_ON_ERROR),
            'settingsjson' => '{}',
        ], 2);

        set_config('trial_conversion_product_sku', 'A1.OTHER', 'local_subscriptions');
        self::assertSame(
            $existingid,
            CommerceA1ShowroomFoundationService::create($DB)->ensure(2)
        );

        $after = $repository->get($existingid);
        self::assertNotNull($after);
        self::assertSame('A1 déjà édité', $after->name);
        self::assertSame('a1-personnalise', $after->slugfr);
        self::assertSame(
            ['course' => 'A1.CUSTOM'],
            json_decode((string)$after->productsjson, true, 512, JSON_THROW_ON_ERROR)
        );
        self::assertCount(0, $repository->blocks($existingid));
    }

    public function test_existing_a1_owner_gets_promotion_join_and_dynamic_pedagogy(): void {
        global $DB;
        $this->resetAfterTest(true);

        $now = time();
        $sku = 'COURSE.A1.E1';
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        // Model a technically valid Native course-access product. The public
        // Storefront/Showroom contract validates course products against their
        // effective Access Scope; a bare entitlement row alone is not enough to
        // make a course product publicly viewable.
        $scopeid = (int)$DB->insert_record('subscription_access_scope', (object)[
            'name' => 'A1 E1 scope',
            'description' => 'Scope used by the A1 Showroom owner scenario.',
            'course_ids' => json_encode([(int)$course->id], JSON_THROW_ON_ERROR),
            'is_active' => 1,
            'creation_date' => $now,
            'last_update' => $now,
        ]);

        $hydrator = new CommerceCatalogHydrator();
        $products = new CommerceProductRepository($DB, $hydrator);
        $product = $products->save(new CommerceProduct(
            $sku,
            CommerceProductType::COURSE_ACCESS,
            CommerceProductStatus::ACTIVE,
            'A1 CampusFR',
            '',
            ['access' => ['scopeid' => $scopeid]]
        ));
        (new CommerceProductEntitlementRepository($DB, $hydrator, $products))
            ->replace_for_product($sku, [
                new CommerceProductEntitlementDefinition(
                    $sku,
                    'course_access',
                    'course:' . $course->id . ':full'
                ),
            ]);
        (new CommerceProductPriceRepository($DB, $hydrator, $products))->save(
            new CommerceProductPrice(
                $sku,
                CommerceMoney::from_minor(28000, 'EUR'),
                true
            )
        );

        $startsat = $now + 7 * DAYSECS;
        $salesclosesat = $now + 5 * DAYSECS;
        $promotion = CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                null,
                'a1-e1-promotion',
                'Promotion A1 E1',
                (int)$course->id,
                CommercePedagogicalPromotionStatus::OPEN,
                true,
                $now - HOURSECS,
                $salesclosesat,
                $startsat,
                null,
                6,
                null,
                null,
                $now,
                $now
            )
        );
        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(),
            (int)$product->get_id(),
            6,
            null,
            $now
        );

        CommercePedagogicalGroupRepository::create($DB)->save_configuration(
            new CommercePedagogicalGroupConfiguration(
                (int)$promotion->get_id(),
                true,
                6,
                null,
                null,
                $now,
                $now
            )
        );
        CommercePedagogicalGroupOrchestrator::create($DB)->create_group(
            (int)$promotion->get_id(),
            'Clan A1',
            0,
            null,
            'fr',
            null,
            null,
            null,
            $now,
            null,
            (int)$product->get_id()
        );

        CommercePedagogicalPromotionJoinPricingService::create($DB)->configure(
            (int)$promotion->get_id(),
            (int)$product->get_id(),
            'EUR',
            9900,
            null,
            $now
        );

        $DB->insert_record('local_subs_commerce_grant', (object)[
            'grantreference' => 'grant-a1-e1',
            'idempotencykey' => 'idem-a1-e1',
            'purchasereference' => 'purchase-a1-e1',
            'itemreference' => 'item-a1-e1',
            'productsku' => $sku,
            'type' => 'course_access',
            'resourcekey' => 'course:' . $course->id . ':full',
            'quantity' => 1,
            'beneficiaryuserid' => (int)$user->id,
            'beneficiaryemail' => $user->email,
            'validfrom' => $now - 60,
            'validuntil' => null,
            'status' => 'active',
            'configurationjson' => '{}',
            'metadatajson' => '{}',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $this->setUser($user);

        $definition = new CommerceShowroomDefinition(
            'a1-e1-test',
            ['fr' => ''],
            'local_subscriptions/showroom/default',
            ['course' => $sku],
            'commerce_showroom_a1_title',
            'commerce_showroom_a1_description'
        );
        $offers = CommerceShowroomProductResolver::create($DB)->resolve(
            $definition,
            'fr',
            'EUR'
        );
        self::assertCount(1, $offers);
        self::assertTrue($offers[0]['available']);
        self::assertTrue($offers[0]['owned']);
        self::assertTrue($offers[0]['promotionjoinpurchasable']);
        self::assertGreaterThan(0, (int)$offers[0]['promotionjoinpriceid']);
        self::assertGreaterThan(0, (int)$offers[0]['promotionjoincatalogpriceid']);
        self::assertNotSame('', $offers[0]['promotionjoinpriceformatted']);

        $data = (new CommerceShowroomPresenter())->present(
            $definition,
            $offers,
            'EUR'
        );
        self::assertTrue($data['haspedagogicalpromotion']);
        self::assertSame((int)$promotion->get_id(), $data['pedagogicalpromotionid']);
        self::assertSame($startsat, $data['pedagogicalpromotionstartsat']);
        self::assertSame($salesclosesat, $data['pedagogicalpromotionsalesclosesat']);
        self::assertTrue($data['pedagogicalgroupsenabled']);
        self::assertSame(6, $data['pedagogicalgroupsize']);
        self::assertTrue($data['haspedagogicalcapacity']);
        self::assertSame(6, $data['pedagogicalremaining']);
        self::assertTrue($data['offers'][0]['promotionjoinpurchasable']);
        self::assertTrue($data['offers'][0]['canaccess']);
        self::assertFalse($data['offers'][0]['canbuy']);
    }

    public function test_showroom_owner_join_form_uses_existing_native_operation(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/showroom/offer.mustache'
        );
        $javascript = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/showroom.js'
        );

        self::assertIsString($template);
        self::assertStringContainsString('name="operation" value="promotion_join"', $template);
        self::assertStringContainsString('data-showroom-promotion-join-priceid', $template);
        self::assertStringContainsString('data-showroom-standard-buy', $template);
        self::assertIsString($javascript);
        self::assertStringContainsString('[data-showroom-standard-buy]', $javascript);
        self::assertStringContainsString('[data-showroom-promotion-join-form]', $javascript);
    }
}
