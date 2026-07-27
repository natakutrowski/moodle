<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../fixtures/CommerceCatalogTestPaymentProvider.php');

use advanced_testcase;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderRegistry;
use local_subscriptions\tests\fixtures\CommerceCatalogTestPaymentProvider;
use local_subscriptions\commerce\purchase\CommerceCustomer;

final class commerce_catalog_payment_simulation_test extends advanced_testcase {
    public function test_native_product_reaches_provider_validation_without_remote_call(): void {
        $this->resetAfterTest(true);
        global $DB;

        $DB->insert_record('subscription_digital_product', (object)[
            'slug' => 'simulation-guide', 'name' => 'Simulation Guide', 'descriptionformat' => 1,
            'filename' => 'simulation.pdf', 'price_eur' => '12.00', 'price_rub' => '0.00',
            'enabled' => 1, 'creation_date' => 0, 'last_update' => 0, 'sortorder' => 0,
        ]);
        $factory = new CommerceCatalogFactory(
            $DB,
            new CommercePaymentProviderRegistry([
                new CommerceCatalogTestPaymentProvider()
            ])
        );
        $this->assertEmpty($factory->importer()->import('digital', true)['errors']);

        $result = $factory->payment_simulation()->simulate(
            'c7-simulation', new CommerceCustomer(null, 'buyer@example.test'),
            [['sku' => 'DIGITAL.SIMULATION-GUIDE']], 'EUR', 'fr', 'stripe'
        );

        $this->assertSame(1200, $result['pipeline']->get_payment_request()->get_amount_minor());
        $this->assertTrue($result['initialization']->is_simulated());
        $this->assertSame('stripe', $result['initialization']->get_provider_key());
        $this->assertNull($result['initialization']->get_payment_result());
    }
}
