<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_personal_offer_campaign_email_locale_m3h15_test extends \advanced_testcase {
    public function test_personal_offer_template_reads_plugin_owned_campaign_language_catalogues_directly(): void {
        global $CFG;

        $source = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/mail/template/'
            . 'CommercePersonalOfferTemplate.php'
        );

        $this->assertStringContainsString(
            "'/local/subscriptions/lang/' . \$language . '/local_subscriptions.php'",
            $source
        );
        $this->assertStringContainsString('is_readable($langfile)', $source);
        $this->assertStringContainsString('include($langfile)', $source);
        $this->assertStringNotContainsString('load_component_strings(', $source);
    }

    public function test_ru_catalogue_contains_all_campaign_technical_labels(): void {
        global $CFG;

        $string = [];
        include($CFG->dirroot . '/local/subscriptions/lang/ru/local_subscriptions.php');

        $this->assertSame(
            'Ваше персональное предложение',
            $string['commerce_mail_personal_offer_card_label']
        );
        $this->assertSame(
            'Предложение действует',
            $string['commerce_mail_personal_offer_validity_label']
        );
        $this->assertSame(
            'Перейти к оплате',
            $string['commerce_mail_personal_offer_direct_checkout']
        );
    }
}
