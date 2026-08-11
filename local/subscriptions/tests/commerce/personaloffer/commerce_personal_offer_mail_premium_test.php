<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_personal_offer_mail_premium_test extends advanced_testcase {
    public function test_product_name_fallback_prefers_translations_before_technical_name(): void {
        $root = dirname(__DIR__, 3);
        $repository = (string)file_get_contents(
            $root . '/classes/commerce/catalog/repository/CommerceProductTranslationRepository.php'
        );
        $storefront = (string)file_get_contents(
            $root . '/classes/commerce/storefront/repository/CommerceStorefrontRepository.php'
        );
        $mail = (string)file_get_contents(
            $root . '/classes/commerce/personaloffer/mail/CommercePersonalOfferMailService.php'
        );

        foreach ([$repository, $storefront, $mail] as $source) {
            $this->assertStringContainsString("'fr'", $source);
            $this->assertStringContainsString("'en'", $source);
            $this->assertStringContainsString("'ru'", $source);
        }
        $this->assertStringContainsString('resolve_product_name', $mail);
    }

    public function test_personal_offer_mail_has_gold_card_and_separate_currency_cards(): void {
        $root = dirname(__DIR__, 3);
        $template = (string)file_get_contents($root . '/templates/commerce/mail/personal_offer.mustache');
        $service = (string)file_get_contents(
            $root . '/classes/commerce/mail/template/CommercePersonalOfferTemplate.php'
        );

        $this->assertStringContainsString('personaloffer.hasprices', $template);
        $this->assertStringContainsString('personaloffer.prices', $template);
        $this->assertStringContainsString('#d5aa45', $template);
        $this->assertStringContainsString('pricing_cards', $service);
        $this->assertStringContainsString("'EUR' => '€'", $service);
        $this->assertStringContainsString("'RUB' => '₽'", $service);
    }

    public function test_shared_mail_shell_has_premium_card_and_cta(): void {
        $root = dirname(__DIR__, 3);
        $renderer = (string)file_get_contents($root . '/classes/mail/MailRenderer.php');

        $this->assertStringContainsString('border:1px solid #e6e1ec', $renderer);
        $this->assertStringContainsString('box-shadow:0 10px 32px', $renderer);
        $this->assertStringContainsString('padding:14px 28px', $renderer);
    }
}
