<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;

final class commerce_showroom_articles_offers_slide8_798c315_test extends \advanced_testcase {
    public function test_presenter_exposes_alf_support_variant_footer_and_role_ctas(): void {
        $reflection = new \ReflectionClass(CommerceShowroomBlockConfigurationPresenter::class);
        $presenter = $reflection->newInstance();
        $method = $reflection->getMethod('apply_offers');
        $method->setAccessible(true);

        $data = $method->invoke($presenter, [
            'offersheading' => 'Offres',
            'offersdescription' => '',
            'offersbadgeicon' => 'fa-solid fa-bag-shopping',
            'offers' => [
                ['role' => 'course', 'sku' => 'COURSE', 'buylabel' => 'Acheter', 'shortdescription' => ''],
                ['role' => 'bundle', 'sku' => 'BUNDLE', 'buylabel' => 'Acheter', 'shortdescription' => ''],
                ['role' => 'donation', 'sku' => 'DON', 'buylabel' => 'Acheter', 'shortdescription' => ''],
            ],
            'comparisonoffers' => [],
        ], [
            'presentation' => 'alf_support',
            'badgeicon' => 'fa-solid fa-heart',
            'footertext' => 'Merci de nous aider.',
            'footeraccent' => 'Chaque geste compte.',
            'coursebuylabel' => 'Commencer',
            'bundlebuylabel' => 'Aider davantage',
            'donationbuylabel' => 'Faire un don',
            'featuredrole' => 'bundle',
            'order' => 'course,bundle,donation',
            'columns' => '3',
        ]);

        self::assertSame('alf_support', $data['offerspresentation']);
        self::assertSame('commerce-showroom-offers--alf-support', $data['offerspresentationclass']);
        self::assertTrue($data['offersisalfSupport']);
        self::assertSame('fa-solid fa-heart', $data['offersbadgeicon']);
        self::assertTrue($data['hasoffersfooter']);
        self::assertSame('Commencer', $data['offers'][0]['buylabel']);
        self::assertSame('Aider davantage', $data['offers'][1]['buylabel']);
        self::assertSame('Faire un don', $data['offers'][2]['buylabel']);
        self::assertTrue($data['offers'][1]['isfeatured']);
    }

    public function test_slide_eight_markup_registry_and_css_support_alf_variant(): void {
        global $CFG;

        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/default.mustache');
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        $registry = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/cms/CommerceShowroomBlockEditorRegistry.php');

        self::assertStringContainsString('{{offerspresentationclass}}', $template);
        self::assertStringContainsString('commerce-showroom-offers__alf-footer', $template);
        self::assertStringContainsString('fa-solid fa-paw', $template);
        self::assertStringContainsString('.commerce-showroom-offers--alf-support', $css);
        self::assertStringContainsString("'alf_support' => 'Articles / Alf — offres solidaires'", $registry);
        self::assertStringContainsString("\$role . 'buylabel'", $registry);
    }
}
