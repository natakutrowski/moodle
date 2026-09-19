<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\legal\entity\CommerceLegalEntity;
use local_subscriptions\commerce\legal\entity\CommerceLegalEntityRegistry;

final class commerce_796i2_legal_entity_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_registry_exposes_two_stable_legal_entities(): void {
        $registry = new CommerceLegalEntityRegistry();

        self::assertSame(['fr_main', 'ru_main'], $registry->keys());
        self::assertSame('FR', $registry->get('fr_main')->get_registered_country());
        self::assertSame('RU', $registry->get('ru_main')->get_registered_country());
    }

    public function test_registry_reads_new_configuration(): void {
        set_config('legal_entity_fr_name', 'CampusFR France', 'local_subscriptions');
        set_config('legal_entity_fr_registration', 'REG-FR', 'local_subscriptions');
        set_config('legal_entity_fr_tax_identifier', 'TAX-FR', 'local_subscriptions');

        $entity = (new CommerceLegalEntityRegistry())->get('FR_MAIN');

        self::assertSame('fr_main', $entity->get_key());
        self::assertSame('CampusFR France', $entity->get_name());
        self::assertSame('REG-FR', $entity->get_registration());
        self::assertSame('TAX-FR', $entity->get_tax_identifier());
    }

    public function test_registry_falls_back_to_legacy_invoice_configuration_only_when_new_key_is_absent(): void {
        set_config('invoice_eur_name', 'Legacy France', 'local_subscriptions');
        set_config('invoice_eur_footer', 'Legacy footer', 'local_subscriptions');

        $registry = new CommerceLegalEntityRegistry();
        self::assertSame('Legacy France', $registry->get('fr_main')->get_name());
        self::assertSame('Legacy footer', $registry->get('fr_main')->get_footer());

        set_config('legal_entity_fr_name', '', 'local_subscriptions');
        self::assertSame('', (new CommerceLegalEntityRegistry())->get('fr_main')->get_name());
    }

    public function test_optional_legal_fields_may_be_empty(): void {
        $entity = (new CommerceLegalEntityRegistry())->get('ru_main');

        self::assertSame('', $entity->get_registration());
        self::assertSame('', $entity->get_tax_identifier());
        self::assertSame('', $entity->get_legal());
    }

    public function test_invalid_legal_entity_key_is_rejected(): void {
        $this->expectException(\coding_exception::class);
        new CommerceLegalEntity('RUB', 'RU');
    }

    public function test_entity_contract_has_no_currency_or_provider_binding(): void {
        $data = (new CommerceLegalEntityRegistry())->get('fr_main')->to_array();

        self::assertArrayNotHasKey('currency', $data);
        self::assertArrayNotHasKey('provider', $data);
        self::assertArrayNotHasKey('paymentprovider', $data);
    }

    public function test_unknown_registry_key_is_rejected(): void {
        $this->expectException(\coding_exception::class);
        (new CommerceLegalEntityRegistry())->get('eur');
    }

    public function test_i2_does_not_change_invoice_resolver_or_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $resolver = file_get_contents($root . '/classes/commerce/order/invoice/CommerceInvoiceProfileResolver.php');
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString("\$profile = \$currency === 'RUB' ? 'rub' : 'eur';", $resolver);
        self::assertMatchesRegularExpression(
            '/\\$plugin->version = \\d+;/',
            $version
        );
    }
}
