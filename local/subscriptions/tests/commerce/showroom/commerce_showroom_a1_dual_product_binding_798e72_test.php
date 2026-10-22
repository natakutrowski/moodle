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
use local_subscriptions\commerce\showroom\a1\CommerceA1ShowroomFoundationService;
use local_subscriptions\commerce\showroom\CommerceShowroomDefinition;
use local_subscriptions\commerce\showroom\CommerceShowroomPresenter;
use local_subscriptions\commerce\showroom\CommerceShowroomProductResolver;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomProductLinkOptions;

/** 7.98E.7.2 — explicit FR/RU A1 product bindings, independent from Trial. */
final class commerce_showroom_a1_dual_product_binding_798e72_test extends \advanced_testcase {
    public function test_foundation_uses_explicit_dual_a1_products_and_ignores_trial_conversion_sku(): void {
        global $DB;
        $this->resetAfterTest(true);

        set_config('trial_conversion_product_sku', 'SOME-OTHER-TRIAL-DESTINATION', 'local_subscriptions');

        $service = CommerceA1ShowroomFoundationService::create($DB);
        $showroomid = $service->ensure(2);
        $showroom = (new CommerceShowroomCmsRepository($DB))->get($showroomid);
        self::assertNotNull($showroom);

        self::assertSame([
            CommerceA1ShowroomFoundationService::PRODUCT_ROLE_FR
                => CommerceA1ShowroomFoundationService::PRODUCT_SKU_FR,
            CommerceA1ShowroomFoundationService::PRODUCT_ROLE_RU
                => CommerceA1ShowroomFoundationService::PRODUCT_SKU_RU,
        ], json_decode((string)$showroom->productsjson, true, 512, JSON_THROW_ON_ERROR));
        self::assertStringNotContainsString(
            'SOME-OTHER-TRIAL-DESTINATION',
            (string)$showroom->productsjson
        );
        self::assertStringNotContainsString(
            'trial_conversion_product_sku',
            (string)file_get_contents(
                __DIR__ . '/../../../classes/commerce/showroom/a1/CommerceA1ShowroomFoundationService.php'
            )
        );
    }

    public function test_e72_migrates_legacy_course_role_to_two_products_and_preserves_custom_roles(): void {
        global $DB;
        $this->resetAfterTest(true);

        $repo = new CommerceShowroomCmsRepository($DB);
        $showroomid = $repo->save([
            'showroomkey' => CommerceA1ShowroomFoundationService::SHOWROOM_KEY,
            'status' => 'draft',
            'name' => 'Cours A1 CampusFR',
            'template' => 'local_subscriptions/showroom/default',
            'slugfr' => 'francais-a1',
            'slugen' => 'french-a1',
            'slugru' => 'francuzskij-a1',
            'titlekey' => 'commerce_showroom_a1_title',
            'descriptionkey' => 'commerce_showroom_a1_description',
            'productsjson' => json_encode([
                'course' => 'OLD-TRIAL-HINT',
                'custom_bonus' => 'BONUS.KEEP',
            ], JSON_THROW_ON_ERROR),
            'settingsjson' => json_encode([
                'offers' => [
                    'course' => ['detailsenabled' => true, 'donationmode' => 'none'],
                ],
                'foundation' => ['kind' => 'a1_course', 'a0included' => true],
            ], JSON_THROW_ON_ERROR),
        ], 2);
        $repo->save_block($showroomid, [
            'blocktype' => 'offers',
            'blockkey' => 'offers',
            'sortorder' => 100,
            'enabled' => true,
            'configjson' => json_encode([
                'presentation' => 'single_course',
                'singleofferrole' => 'course',
                'order' => 'course',
                'featuredrole' => 'course',
                'columns' => '2',
                'translations' => [
                    'ru' => [
                        'coursedescription' => 'Общее описание A1',
                        'coursefeatures' => "A0 включён\n30 приключений",
                        'coursebuylabel' => 'Присоединиться',
                    ],
                    'fr' => [
                        'coursedescription' => 'Description A1 commune',
                        'coursefeatures' => "A0 inclus\n30 aventures",
                        'coursebuylabel' => 'Rejoindre le cours',
                        // A manual role-specific edit must win over the seed.
                        'course_rusubtitle' => 'Mon texte personnalisé',
                    ],
                    'en' => [
                        'coursedescription' => 'Shared A1 description',
                        'coursefeatures' => "A0 included\n30 adventures",
                        'coursebuylabel' => 'Join the course',
                    ],
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ], 2);

        $result = CommerceA1ShowroomFoundationService::create($DB)
            ->apply_dual_offer_product_binding_v1(2);
        self::assertSame(2, $result['productsbound']);
        self::assertSame(2, $result['offersettingsbound']);
        self::assertSame(1, $result['offerblockupdated']);

        $showroom = $repo->get($showroomid);
        self::assertNotNull($showroom);
        $products = json_decode((string)$showroom->productsjson, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayNotHasKey('course', $products);
        self::assertSame('A1-FR-SUPPORT', $products['course_fr']);
        self::assertSame('A1-RU-SUPPORT', $products['course_ru']);
        self::assertSame('BONUS.KEEP', $products['custom_bonus']);

        $settings = json_decode((string)$showroom->settingsjson, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayNotHasKey('course', $settings['offers']);
        self::assertTrue($settings['offers']['course_fr']['detailsenabled']);
        self::assertTrue($settings['offers']['course_ru']['detailsenabled']);
        self::assertSame('none', $settings['offers']['course_fr']['donationmode']);
        self::assertSame('none', $settings['offers']['course_ru']['donationmode']);
        self::assertSame('7.98E7.2', $settings['foundation']['productbinding']);

        $offersblock = null;
        foreach ($repo->blocks($showroomid) as $block) {
            if ((string)$block->blocktype === 'offers') {
                $offersblock = $block;
                break;
            }
        }
        self::assertNotNull($offersblock);
        $config = json_decode((string)$offersblock->configjson, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('standard', $config['presentation']);
        self::assertArrayNotHasKey('singleofferrole', $config);
        self::assertSame('course_fr,course_ru', $config['order']);
        self::assertSame('course_fr', $config['featuredrole']);
        self::assertSame('Общее описание A1', $config['translations']['ru']['course_frdescription']);
        self::assertSame('Общее описание A1', $config['translations']['ru']['course_rudescription']);
        self::assertSame('Mon texte personnalisé', $config['translations']['fr']['course_rusubtitle']);
        self::assertSame(
            'Parcours A1 avec accompagnement en français',
            $config['translations']['fr']['course_frsubtitle']
        );

        // Idempotent on the second pass.
        $again = CommerceA1ShowroomFoundationService::create($DB)
            ->apply_dual_offer_product_binding_v1(2);
        self::assertSame(0, $again['productsbound']);
        self::assertSame(0, $again['offersettingsbound']);
        self::assertSame(0, $again['offerblockupdated']);
    }

    public function test_dual_roles_only_accept_course_access_products(): void {
        global $DB;
        $this->resetAfterTest(true);

        $hydrator = new CommerceCatalogHydrator();
        $repo = new CommerceProductRepository($DB, $hydrator);
        $repo->save(new CommerceProduct(
            'A1-FR-SUPPORT',
            CommerceProductType::COURSE_ACCESS,
            CommerceProductStatus::ACTIVE,
            'A1 FR',
            '',
            []
        ));
        $repo->save(new CommerceProduct(
            'DIGITAL-WRONG',
            CommerceProductType::DIGITAL_DOWNLOAD,
            CommerceProductStatus::ACTIVE,
            'Wrong type',
            '',
            []
        ));

        $options = new CommerceShowroomProductLinkOptions($DB);
        self::assertSame('A1-FR-SUPPORT', $options->normalise_role_sku('course_fr', 'A1-FR-SUPPORT'));
        self::assertSame('A1-FR-SUPPORT', $options->normalise_role_sku('course_ru', 'A1-FR-SUPPORT'));

        $this->expectException(\invalid_parameter_exception::class);
        $options->normalise_role_sku('course_fr', 'DIGITAL-WRONG');
    }

    public function test_two_a1_products_render_as_buyable_offers_before_promotions_exist(): void {
        global $DB;
        $this->resetAfterTest(true);

        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $scopeid = (int)$DB->insert_record('subscription_access_scope', (object)[
            'name' => 'A1 dual scope',
            'description' => 'Shared A1 scope',
            'course_ids' => json_encode([(int)$course->id], JSON_THROW_ON_ERROR),
            'is_active' => 1,
            'creation_date' => $now,
            'last_update' => $now,
        ]);

        $hydrator = new CommerceCatalogHydrator();
        $products = new CommerceProductRepository($DB, $hydrator);
        $entitlements = new CommerceProductEntitlementRepository($DB, $hydrator, $products);
        $prices = new CommerceProductPriceRepository($DB, $hydrator, $products);
        foreach ([
            'A1-FR-SUPPORT' => 'A1 + accompagnement français',
            'A1-RU-SUPPORT' => 'A1 + accompagnement russe',
        ] as $sku => $name) {
            $products->save(new CommerceProduct(
                $sku,
                CommerceProductType::COURSE_ACCESS,
                CommerceProductStatus::ACTIVE,
                $name,
                '',
                ['access' => ['scopeid' => $scopeid]]
            ));
            $entitlements->replace_for_product($sku, [
                new CommerceProductEntitlementDefinition(
                    $sku,
                    'course_access',
                    'course:' . $course->id . ':full'
                ),
            ]);
            $prices->save(new CommerceProductPrice(
                $sku,
                CommerceMoney::from_minor(10000, 'EUR'),
                true
            ));
        }

        $definition = new CommerceShowroomDefinition(
            'a1-e72-test',
            ['fr' => ''],
            'local_subscriptions/showroom/default',
            [
                CommerceA1ShowroomFoundationService::PRODUCT_ROLE_FR => 'A1-FR-SUPPORT',
                CommerceA1ShowroomFoundationService::PRODUCT_ROLE_RU => 'A1-RU-SUPPORT',
            ],
            'commerce_showroom_a1_title',
            'commerce_showroom_a1_description'
        );
        $offers = CommerceShowroomProductResolver::create($DB)->resolve($definition, 'fr', 'EUR');
        self::assertCount(2, $offers);
        self::assertSame(['course_fr', 'course_ru'], array_column($offers, 'role'));
        self::assertTrue($offers[0]['available']);
        self::assertTrue($offers[1]['available']);

        $data = (new CommerceShowroomPresenter())->present($definition, $offers, 'EUR');
        self::assertFalse($data['haspedagogicalpromotion']);
        self::assertTrue($data['offers'][0]['canbuy']);
        self::assertTrue($data['offers'][1]['canbuy']);
        self::assertFalse($data['offers'][0]['promotionjoinpurchasable']);
        self::assertFalse($data['offers'][1]['promotionjoinpurchasable']);
    }
}
