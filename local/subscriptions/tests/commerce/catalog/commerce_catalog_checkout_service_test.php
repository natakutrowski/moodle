<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../fixtures/CommerceCatalogTestPaymentProvider.php');

use advanced_testcase;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderRegistry;
use local_subscriptions\tests\fixtures\CommerceCatalogTestPaymentProvider;
use local_subscriptions\commerce\purchase\CommerceCustomer;

final class commerce_catalog_checkout_service_test extends advanced_testcase {
    public function test_checkout_is_simulated_by_default(): void {
        $this->resetAfterTest(true);
        global $DB;
        $DB->insert_record('subscription_digital_product', (object)[
            'slug' => 'guarded-guide', 'name' => 'Guarded Guide', 'descriptionformat' => 1,
            'filename' => 'guarded.pdf', 'price_eur' => '15.00', 'price_rub' => '0.00',
            'enabled' => 1, 'creation_date' => 0, 'last_update' => 0, 'sortorder' => 0,
        ]);
        $factory = new CommerceCatalogFactory(
            $DB,
            new CommercePaymentProviderRegistry([
                new CommerceCatalogTestPaymentProvider()
            ])
        );
        $this->assertEmpty($factory->importer()->import('digital', true)['errors']);
        $result = $factory->catalog_checkout()->initialize(
            'c8-checkout', new CommerceCustomer(null, 'buyer@example.test'),
            [['sku' => 'DIGITAL.GUARDED-GUIDE']], 'EUR', 'fr', 'stripe',
            'https://example.test/success', 'https://example.test/cancel', false
        );
        $this->assertFalse($result->was_executed());
        $this->assertSame(1500, $result->get_pipeline()->get_payment_request()->get_amount_minor());
    }
}
