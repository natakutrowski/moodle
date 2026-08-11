<?php

namespace local_subscriptions\tests\commerce\storefront;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\storefront\localisation\CommerceStorefrontLocaleResolver;

final class commerce_795f6d_stabilisation_test extends advanced_testcase {
    public function test_locale_resolver_uses_requested_language_and_french_fallback(): void {
        $resolver = new CommerceStorefrontLocaleResolver();
        $storefront = ['sections'=>[['type'=>'rich_text','content'=>'base']], 'locales'=>[
            'fr'=>['sections'=>[['type'=>'rich_text','content'=>'fr']]],
            'en'=>['sections'=>[['type'=>'rich_text','content'=>'en']]],
        ]];
        self::assertSame('en', $resolver->resolve($storefront, 'en')['sections'][0]['content']);
        self::assertSame('fr', $resolver->resolve($storefront, 'de')['sections'][0]['content']);
    }

    public function test_badge_presenter_declares_custom_gustave_fallback_contract(): void {
        $source = file_get_contents(__DIR__ . '/../../../classes/commerce/storefront/presentation/CommerceStorefrontPresenter.php');
        self::assertStringContainsString('hascustomicon', $source);
        self::assertStringContainsString("'gustave_choice' => '🦒'", $source);
    }
}
