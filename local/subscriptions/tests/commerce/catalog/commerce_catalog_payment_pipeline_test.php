<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\commerce\purchase\CommerceCustomer;

final class commerce_catalog_payment_pipeline_test extends advanced_testcase {
    public function test_native_catalogue_builds_provider_independent_payment_request(): void {
        $this->resetAfterTest(true);
        global $DB;

        $productid = $DB->insert_record('subscription_digital_product', (object)[
            'slug' => 'guide-a1',
            'name' => 'Guide A1',
            'descriptionformat' => 1,
            'filename' => 'guide-a1.pdf',
            'price_eur' => '19.90',
            'price_rub' => '0.00',
            'enabled' => 1,
            'creation_date' => 0,
            'last_update' => 0,
            'sortorder' => 0,
        ]);

        $factory = new CommerceCatalogFactory($DB);
        $result = $factory->importer()->import('digital', true);
        $this->assertEmpty($result['errors']);

        $pipeline = $factory->payment_pipeline()->build(
            'c5-test',
            new CommerceCustomer(null, 'buyer@example.test'),
            [['sku' => 'DIGITAL.GUIDE-A1', 'quantity' => 2]],
            'EUR',
            'fr',
            'stripe',
            'https://example.test/success',
            'https://example.test/cancel'
        );

        $paymentrequest = $pipeline->get_payment_request();
        $this->assertSame(3980, $paymentrequest->get_amount_minor());
        $this->assertSame('EUR', $paymentrequest->get_currency());
        $this->assertSame('stripe', $paymentrequest->get_preferred_provider());
        $this->assertCount(1, $paymentrequest->get_lines());
        $this->assertSame(2, $paymentrequest->get_lines()[0]->get_quantity());
        $this->assertSame($productid, $pipeline->get_preparation()->get_fulfillment_operations()[0]['metadata']['productid']);
    }
}
