<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\legal\merchant\CommerceMerchantResolutionContext;
use local_subscriptions\commerce\legal\merchant\CommerceMerchantResolver;

final class commerce_796i3_merchant_resolver_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /** @dataProvider ru_by_provider */
    public function test_ru_and_by_resolve_to_russian_entity(string $country): void {
        $result = (new CommerceMerchantResolver())->resolve(
            new CommerceMerchantResolutionContext($country, 'EUR', 'stripe')
        );

        self::assertSame('ru_main', $result->get_entity_key());
        self::assertSame($country, $result->get_market_country());
        self::assertSame(CommerceMerchantResolver::RULE_RU_BY, $result->get_rule());
    }

    public static function ru_by_provider(): array {
        return [
            'Russia' => ['RU'],
            'Belarus' => ['BY'],
        ];
    }

    /** @dataProvider row_provider */
    public function test_rest_of_world_resolves_to_french_entity(string $country): void {
        $result = (new CommerceMerchantResolver())->resolve(
            new CommerceMerchantResolutionContext($country, 'RUB', 'alfa')
        );

        self::assertSame('fr_main', $result->get_entity_key());
        self::assertSame(CommerceMerchantResolver::RULE_ROW, $result->get_rule());
    }

    public static function row_provider(): array {
        return [
            'France' => ['FR'],
            'United States' => ['US'],
            'United Kingdom' => ['GB'],
            'Armenia' => ['AM'],
            'Kazakhstan' => ['KZ'],
        ];
    }

    public function test_unknown_market_has_explicit_french_fallback(): void {
        $result = (new CommerceMerchantResolver())->resolve(
            new CommerceMerchantResolutionContext('ZZ', 'RUB', 'alfa')
        );

        self::assertSame('fr_main', $result->get_entity_key());
        self::assertSame('ZZ', $result->get_market_country());
        self::assertSame(CommerceMerchantResolver::RULE_UNKNOWN_FALLBACK_FR, $result->get_rule());
    }

    public function test_currency_and_provider_do_not_change_v1_seller_routing(): void {
        $resolver = new CommerceMerchantResolver();

        foreach ([
            ['RU', 'EUR', 'stripe'],
            ['RU', 'USD', 'paypal'],
            ['RU', 'RUB', 'alfa'],
            ['BY', 'GBP', 'stripe'],
        ] as [$country, $currency, $provider]) {
            self::assertSame(
                'ru_main',
                $resolver->resolve(new CommerceMerchantResolutionContext($country, $currency, $provider))->get_entity_key()
            );
        }

        foreach ([
            ['FR', 'RUB', 'alfa'],
            ['US', 'RUB', 'alfa'],
            ['GB', 'EUR', 'stripe'],
            ['AM', 'USD', 'paypal'],
        ] as [$country, $currency, $provider]) {
            self::assertSame(
                'fr_main',
                $resolver->resolve(new CommerceMerchantResolutionContext($country, $currency, $provider))->get_entity_key()
            );
        }
    }

    public function test_resolution_is_auditable(): void {
        $result = (new CommerceMerchantResolver())->resolve(
            new CommerceMerchantResolutionContext('BY', 'RUB', 'alfa')
        );

        self::assertSame([
            'legal_entity_key' => 'ru_main',
            'market_country' => 'BY',
            'rule' => CommerceMerchantResolver::RULE_RU_BY,
        ], $result->to_audit_array());
    }

    public function test_context_normalises_inputs(): void {
        $context = new CommerceMerchantResolutionContext('ru', 'eur', 'Stripe', ['source' => 'checkout']);

        self::assertSame('RU', $context->get_market_country());
        self::assertSame('EUR', $context->get_currency());
        self::assertSame('stripe', $context->get_provider());
        self::assertSame(['source' => 'checkout'], $context->get_metadata());
    }

    public function test_invalid_market_country_is_rejected(): void {
        $this->expectException(\coding_exception::class);
        new CommerceMerchantResolutionContext('RUS');
    }

    public function test_invalid_currency_is_rejected_when_provided(): void {
        $this->expectException(\coding_exception::class);
        new CommerceMerchantResolutionContext('FR', 'EURO');
    }

    public function test_i3_does_not_modify_purchase_schema_or_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');
        $installxml = file_get_contents($root . '/db/install.xml');

        self::assertMatchesRegularExpression(
            '/\\$plugin->version = \\d+;/',
            $version
        );
        self::assertStringNotContainsString('legal_entity', $installxml);
        self::assertStringNotContainsString('merchant', $installxml);
    }
}
