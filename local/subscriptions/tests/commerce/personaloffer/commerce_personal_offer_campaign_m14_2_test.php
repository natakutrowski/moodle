<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_personal_offer_campaign_m14_2_test extends advanced_testcase {
    public function test_compact_cta_has_one_painted_surface(): void {
        global $CFG;

        $renderer = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/personaloffer/mail/'
            . 'CommercePersonalOfferCampaignMailRenderer.php'
        );

        self::assertStringContainsString('Compact one-surface button', $renderer);
        self::assertStringContainsString('padding:10px 22px', $renderer);
        self::assertStringContainsString('background:transparent;border:0', $renderer);
        self::assertStringNotContainsString('bgcolor="' . "' . \$background", $renderer);
    }

    public function test_direct_pay_and_image_are_positionable_editor_tags(): void {
        global $CFG;

        $editor = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/personal-offers/campaign_email.php'
        );
        $renderer = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/personaloffer/mail/'
            . 'CommercePersonalOfferCampaignMailRenderer.php'
        );

        self::assertStringContainsString("'{{direct_pay}}'", $editor);
        self::assertStringContainsString("'{{image}}'", $editor);
        self::assertStringContainsString("CAMPUSFR_DIRECT_PAY_MARKER_", $renderer);
        self::assertStringContainsString("CAMPUSFR_IMAGE_MARKER_", $renderer);
        self::assertStringContainsString("direct_pay_html", $renderer);
        self::assertStringContainsString("campaign_image_html", $renderer);
    }

    public function test_positionable_layout_disables_legacy_automatic_direct_pay_and_image(): void {
        global $CFG;

        $abstract = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/mail/template/'
            . 'AbstractCommerceMailTemplate.php'
        );
        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/mail/template/'
            . 'CommercePersonalOfferTemplate.php'
        );

        self::assertStringContainsString('personaloffer_positionable_layout', $abstract);
        self::assertStringContainsString('$positionablelayout', $template);
        self::assertStringContainsString('if (!$positionablelayout', $template);
    }

    public function test_direct_pay_uses_existing_payment_brand_assets(): void {
        global $CFG;

        $renderer = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/personaloffer/mail/'
            . 'CommercePersonalOfferCampaignMailRenderer.php'
        );

        self::assertStringContainsString("'visa.png'", $renderer);
        self::assertStringContainsString("'mastercard.png'", $renderer);
        self::assertStringContainsString("'stripe.png'", $renderer);
        self::assertStringContainsString("'alfa.png'", $renderer);
    }
    public function test_secondary_cta_uses_campus_secondary_button_contract(): void {
        global $CFG;

        $renderer = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/personaloffer/mail/'
            . 'CommercePersonalOfferCampaignMailRenderer.php'
        );

        self::assertStringContainsString('color:#f72585', $renderer);
        self::assertStringContainsString('border:1px solid #f72585', $renderer);
        self::assertStringContainsString('background:#ffffff', $renderer);
        self::assertStringContainsString('padding:9px 20px', $renderer);
    }

    public function test_direct_pay_payment_brand_icons_share_the_same_height(): void {
        global $CFG;

        $renderer = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/personaloffer/mail/'
            . 'CommercePersonalOfferCampaignMailRenderer.php'
        );

        self::assertSame(4, substr_count($renderer, 'height="30"'));
        self::assertSame(4, substr_count($renderer, 'width:auto;height:30px'));
        self::assertStringNotContainsString('alt="Visa" width="42"', $renderer);
        self::assertStringNotContainsString('alt="Alfa-Bank" width="42"', $renderer);
    }

    public function test_campaign_ctas_have_progressive_email_hover_styles(): void {
        global $CFG;

        $renderer = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/personaloffer/mail/'
            . 'CommercePersonalOfferCampaignMailRenderer.php'
        );

        self::assertStringContainsString('button_hover_css', $renderer);
        self::assertStringContainsString('campusfr-campaign-cta-gold:hover', $renderer);
        self::assertStringContainsString('campusfr-campaign-cta.campusfr-campaign-cta-gold:hover', $renderer);
        self::assertStringContainsString('background-color:#f1dda0!important', $renderer);
        self::assertStringContainsString('box-shadow:0 3px 8px rgba(95,69,20,.14)!important', $renderer);
        self::assertStringNotContainsString("return '<style type=\"text/css\">'", $renderer);
        self::assertStringContainsString('campusfr-campaign-cta-campus_pink:hover', $renderer);
        self::assertStringContainsString('campusfr-campaign-cta-legacy_blue:hover', $renderer);
        self::assertStringContainsString('campusfr-campaign-cta-secondary:hover', $renderer);
        self::assertStringContainsString('campusfr-campaign-cta campusfr-campaign-cta-secondary', $renderer);
        self::assertStringContainsString("campusfr-campaign-cta-' . \$variant", $renderer);
    }

    public function test_campaign_hover_css_is_injected_into_mail_head(): void {
        global $CFG;

        $campaignrenderer = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/personaloffer/mail/'
            . 'CommercePersonalOfferCampaignMailRenderer.php'
        );
        $abstract = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/mail/template/'
            . 'AbstractCommerceMailTemplate.php'
        );
        $mailrenderer = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/mail/MailRenderer.php'
        );

        self::assertStringContainsString("'headcss' => \$this->button_hover_css()", $campaignrenderer);
        self::assertStringNotContainsString('$this->button_hover_css() . $bodyhtml', $campaignrenderer);
        self::assertStringContainsString("'headcss' => trim((string)(\$editorial['headcss'] ?? ''))", $abstract);
        self::assertStringContainsString("\$options['headcss'] ?? ''", $mailrenderer);
        self::assertStringContainsString("'.\$headcss.'", $mailrenderer);
    }

}
