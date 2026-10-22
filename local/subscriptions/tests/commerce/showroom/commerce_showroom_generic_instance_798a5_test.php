<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductPrice;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\commerce\domain\value\CommerceMoney;
use local_subscriptions\commerce\showroom\CommerceShowroomDefaultPresentationProfile;
use local_subscriptions\commerce\showroom\CommerceShowroomPresentationProfileRegistry;
use local_subscriptions\commerce\showroom\CommerceShowroomPresenter;
use local_subscriptions\commerce\showroom\CommerceShowroomProductResolver;
use local_subscriptions\commerce\showroom\CommerceShowroomRegistry;
use local_subscriptions\commerce\showroom\CommerceShowroomUrl;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPublicationService;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPublishedDefinitionResolver;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomRuntimeBlockSet;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomSlugService;

/** 7.98A.5 — certifies a second neutral Showroom as a pure generic-engine instance. */
final class commerce_showroom_generic_instance_798a5_test extends \advanced_testcase {
    private const SHOWROOM_KEY = 'neutral-a5';
    private const PRODUCT_SKU = 'SERVICE.A5.NEUTRAL';

    public function test_neutral_showroom_runs_end_to_end_without_registry_entry(): void {
        global $DB;
        $this->resetAfterTest(true);

        $catalogue = (new CommerceCatalogFactory($DB))->admin();
        $catalogue->save_product(new CommerceProduct(
            self::PRODUCT_SKU,
            CommerceProductType::SERVICE,
            CommerceProductStatus::ACTIVE,
            'Neutral A5 service'
        ));
        $catalogue->set_price(new CommerceProductPrice(
            self::PRODUCT_SKU,
            CommerceMoney::from_minor(2500, 'EUR')
        ));

        $repository = new CommerceShowroomCmsRepository($DB);
        $showroomid = $repository->save([
            'showroomkey' => self::SHOWROOM_KEY,
            'status' => 'draft',
            'name' => 'Neutral A5',
            'template' => 'local_subscriptions/showroom/default',
            'slugfr' => 'showroom-neutre-a5',
            'slugen' => 'neutral-showroom-a5',
            'slugru' => '',
            'titlekey' => '',
            'descriptionkey' => '',
            'productsjson' => json_encode(
                ['support' => self::PRODUCT_SKU],
                JSON_THROW_ON_ERROR
            ),
            'settingsjson' => '{}',
        ], 2);

        $repository->save_block($showroomid, [
            'blocktype' => 'hero',
            'blockkey' => 'hero',
            'sortorder' => 10,
            'enabled' => true,
            'configjson' => json_encode([
                'title' => 'Un showroom vraiment générique',
                'text' => 'Aucune logique produit dédiée.',
                'primarylabel' => 'Voir l’offre',
                'primarytarget' => '#showroom-offers',
                'herovariant' => 'minimal',
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ], 2);
        $repository->save_block($showroomid, [
            'blocktype' => 'offers',
            'blockkey' => 'offers',
            'sortorder' => 20,
            'enabled' => true,
            'configjson' => json_encode([
                'badge' => 'Offre neutre',
                'title' => 'Choisissez',
                'text' => 'Une offre portée par le moteur générique.',
                'skus' => self::PRODUCT_SKU,
                'featuredrole' => 'support',
                'order' => 'support',
                'supportrolelabel' => 'Support CampusFR',
                'supporttitle' => 'Offre neutre',
                'supportsubtitle' => 'Produit Commerce de type service',
                'supportfeatures' => "Rôle CMS générique\nPrix Commerce réel",
                'supportfeaturedlabel' => 'Mise en avant CMS',
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ], 2);
        $repository->save_block($showroomid, [
            'blocktype' => 'faq',
            'blockkey' => 'faq',
            'sortorder' => 30,
            'enabled' => true,
            'configjson' => json_encode([
                'title' => 'FAQ',
                'items' => 'Ce showroom a-t-il une classe dédiée ?|Non.',
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ], 2);
        $repository->save_block($showroomid, [
            'blocktype' => 'final_cta',
            'blockkey' => 'final-cta',
            'sortorder' => 40,
            'enabled' => true,
            'configjson' => json_encode([
                'title' => 'Moteur générique certifié',
                'text' => 'Le contenu vient entièrement du CMS.',
                'buttonlabel' => 'Revenir aux offres',
                'buttontarget' => '#showroom-offers',
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ], 2);

        self::assertNull(CommerceShowroomRegistry::get(self::SHOWROOM_KEY));

        $publication = new CommerceShowroomPublicationService($DB, $repository);
        $publication->submit_for_review($showroomid, 2);
        $publication->publish($showroomid, 2);

        $definition = (new CommerceShowroomPublishedDefinitionResolver($DB))
            ->require(self::SHOWROOM_KEY);

        self::assertSame(self::SHOWROOM_KEY, $definition->get_key());
        self::assertSame('local_subscriptions/showroom/default', $definition->get_template());
        self::assertSame(self::PRODUCT_SKU, $definition->get_products()['support']);
        self::assertSame('showroom-neutre-a5', $definition->get_slug('fr'));
        self::assertInstanceOf(
            CommerceShowroomDefaultPresentationProfile::class,
            CommerceShowroomPresentationProfileRegistry::for_definition($definition)
        );

        self::assertSame(
            self::SHOWROOM_KEY,
            (new CommerceShowroomSlugService($DB))
                ->find_published_showroom_key('showroom-neutre-a5')
        );
        self::assertStringContainsString(
            '/showroom-neutre-a5',
            CommerceShowroomUrl::make($definition, [], 'fr')->out(false)
        );

        $offers = CommerceShowroomProductResolver::create($DB)->resolve(
            $definition,
            'fr',
            'EUR'
        );
        self::assertCount(1, $offers);
        self::assertSame('support', $offers[0]['role']);
        self::assertSame('Support', $offers[0]['rolelabel']);
        self::assertSame(self::PRODUCT_SKU, $offers[0]['sku']);
        self::assertTrue($offers[0]['available']);
        self::assertTrue($offers[0]['canbuy']);
        self::assertSame(2500, $offers[0]['amountminor']);

        $data = (new CommerceShowroomPresenter())->present(
            $definition,
            $offers,
            'EUR'
        );
        self::assertFalse($data['showexpeditiontheme']);

        $runtime = CommerceShowroomRuntimeBlockSet::load($DB, self::SHOWROOM_KEY);
        $data = array_replace($data, $runtime->to_template_data());
        $data = (new CommerceShowroomBlockConfigurationPresenter())
            ->apply($data, $runtime);

        self::assertSame(
            'hero,offers,faq,final_cta',
            $data['showroomblocksequence']
        );
        self::assertSame('Un showroom vraiment générique', $data['title']);
        self::assertSame('Choisissez', $data['offersheading']);
        self::assertCount(1, $data['offers']);
        self::assertSame('Support CampusFR', $data['offers'][0]['rolelabel']);
        self::assertSame('Offre neutre', $data['offers'][0]['name']);
        self::assertTrue($data['offers'][0]['isfeatured']);
        self::assertCount(2, $data['offers'][0]['features']);
        self::assertCount(1, $data['faqs']);
        self::assertSame('Moteur générique certifié', $data['finaltitle']);
    }

    public function test_generic_engine_contract_has_no_neutral_instance_in_php_registry(): void {
        global $CFG;

        $registrysource = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/showroom/CommerceShowroomRegistry.php'
        );
        $templatesource = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/templates/showroom/default.mustache'
        );

        self::assertStringNotContainsString(self::SHOWROOM_KEY, $registrysource);
        self::assertStringContainsString('{{#showroomorderedblocks}}', $templatesource);
        self::assertStringContainsString('data-showroom="{{showroomkey}}"', $templatesource);
    }
}
