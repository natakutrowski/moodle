<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\personaloffer\mail\CommercePersonalOfferPricingPresentationBuilder;

final class commerce_personal_offer_campaign_dual_currency_m3h4_test extends \advanced_testcase {
    public function test_builder_presents_both_eur_and_rub_in_stable_order_but_keeps_preferred_currency(): void {
        $pricing = CommercePersonalOfferPricingPresentationBuilder::build([
            'RUB' => ['regularminor' => 549000, 'offerminor' => 299000],
            'EUR' => ['regularminor' => 5500, 'offerminor' => 3000],
        ], 'RUB');

        $this->assertSame('RUB', $pricing['currency']);
        $this->assertCount(2, $pricing['prices']);
        $this->assertSame('EUR', $pricing['prices'][0]['currency']);
        $this->assertSame('RUB', $pricing['prices'][1]['currency']);
        $this->assertSame(3000, $pricing['prices'][0]['offerminor']);
        $this->assertSame(299000, $pricing['prices'][1]['offerminor']);
        $this->assertTrue($pricing['prices'][0]['hasdiscountpercent']);
        $this->assertTrue($pricing['prices'][1]['hasdiscountpercent']);
    }

    public function test_campaign_template_uses_dual_currency_cards_and_coloured_discount_badge(): void {
        global $CFG;
        $template = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/commerce/mail/personal_offer.mustache'
        );
        $php = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/mail/template/CommercePersonalOfferTemplate.php'
        );

        $this->assertStringContainsString('personaloffer.campaignprices', $template);
        $this->assertStringContainsString('{{flag}} {{currency}}', $template);
        $this->assertStringContainsString('{{offerformatted}}', $template);
        $this->assertStringContainsString('{{regularformatted}}', $template);
        $this->assertStringContainsString('background:#f41966', $template);
        $this->assertStringContainsString("'campaignprices'", $php);
    }

    public function test_real_and_preview_pricing_share_the_same_builder(): void {
        global $CFG;
        $real = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/personaloffer/mail/'
            . 'CommercePersonalOfferMailPricingPresentationService.php'
        );
        $preview = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/personaloffer/mail/'
            . 'CommercePersonalOfferCampaignMailPreviewService.php'
        );

        $this->assertStringContainsString('CommercePersonalOfferPricingPresentationBuilder::build', $real);
        $this->assertStringContainsString('CommercePersonalOfferPricingPresentationBuilder::build', $preview);
    }
}
