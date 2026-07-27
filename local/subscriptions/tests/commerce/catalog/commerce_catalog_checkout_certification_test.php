<?php

namespace local_subscriptions;
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../fixtures/CommerceCatalogTestPaymentProvider.php');
use advanced_testcase;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderRegistry;
use local_subscriptions\tests\fixtures\CommerceCatalogTestPaymentProvider;

final class commerce_catalog_checkout_certification_test extends advanced_testcase {
    public function test_active_imported_product_passes_checkout_certification(): void {
        $this->resetAfterTest(true);
        global $DB;
        $DB->insert_record('subscription_digital_product', (object)[
            'slug' => 'certified-guide', 'name' => 'Certified Guide', 'descriptionformat' => 1,
            'filename' => 'certified.pdf', 'price_eur' => '20.00', 'price_rub' => '0.00',
            'enabled' => 1, 'creation_date' => 0, 'last_update' => 0, 'sortorder' => 0,
        ]);
        $factory = new CommerceCatalogFactory(
            $DB,
            new CommercePaymentProviderRegistry([
                new CommerceCatalogTestPaymentProvider()
            ])
        );
        $this->assertEmpty($factory->importer()->import('digital', true)['errors']);
        $report = $factory->checkout_certification_auditor()->audit('fr');
        $this->assertSame(1, $report['checked']);
        $this->assertSame(1, $report['passed']);
        $this->assertSame(1, $report['skipped']);
        $this->assertSame(0, $report['failed']);
        $this->assertEmpty($report['errors']);
    }
}
