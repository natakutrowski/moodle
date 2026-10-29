<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\campaign\CommerceMarketingCampaignTestLanguageOptions;

final class commerce_marketing_campaign_test_language_options_test extends \advanced_testcase {
    public function test_russian_only_draft_offers_russian_as_the_default(): void {
        $contents = ['ru' => (object)[
            'language' => 'ru', 'subject' => 'Тест', 'bodyhtml' => '<p>Привет!</p>',
        ]];
        $options = CommerceMarketingCampaignTestLanguageOptions::available($contents);
        self::assertSame(['ru' => 'RU'], $options);
        self::assertSame('ru', CommerceMarketingCampaignTestLanguageOptions::default_language($options));
    }

    public function test_french_fallback_does_not_masquerade_as_an_english_translation(): void {
        $contents = ['fr' => (object)[
            'language' => 'fr', 'subject' => 'Test', 'bodyhtml' => '<p>Bonjour !</p>',
        ]];
        self::assertSame(['fr' => 'FR'], CommerceMarketingCampaignTestLanguageOptions::available($contents));
    }

    public function test_incomplete_translations_cannot_be_selected_for_test(): void {
        $contents = ['ru' => (object)[
            'language' => 'ru', 'subject' => 'Тест', 'bodyhtml' => '',
        ]];
        self::assertSame([], CommerceMarketingCampaignTestLanguageOptions::available($contents));
        self::assertSame('fr', CommerceMarketingCampaignTestLanguageOptions::default_language([]));
    }
}
