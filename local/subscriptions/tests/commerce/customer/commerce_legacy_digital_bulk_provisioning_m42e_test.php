<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\customer\identity\CommerceCustomerIdentitySimilarityService;
use local_subscriptions\commerce\customer\provisioning\CommerceLegacyDigitalBulkProvisioningService;
use local_subscriptions\commerce\customer\provisioning\CommerceLegacyDigitalProvisioningService;
use local_subscriptions\commerce\customer\reconciliation\CommerceCustomerIdentityReconciliationService;

/**
 * @covers \local_subscriptions\commerce\customer\provisioning\CommerceLegacyDigitalBulkProvisioningService
 */
final class commerce_legacy_digital_bulk_provisioning_m42e_test extends advanced_testcase {
    public function test_bulk_preview_is_read_only(): void {
        global $DB;

        $this->resetAfterTest(true);

        $productid = $DB->insert_record(
            'subscription_digital_product',
            (object)[
                'slug' => 'm42e-bulk-product',
                'name' => 'M4.2E bulk product',
                'filename' => 'bulk.pdf',
            ]
        );

        foreach (
            ['one@example.test', 'two@example.test']
            as $email
        ) {
            $DB->insert_record(
                'subscription_digital_payment_request',
                (object)[
                    'productid' => $productid,
                    'userid' => null,
                    'email' => $email,
                    'firstname' => 'Bulk',
                    'lastname' => 'Buyer',
                    'currency' => 'EUR',
                    'price' => 9.90,
                    'amount_minor' => 990,
                    'payment_provider' => 'legacy',
                    'status' => 'paid',
                    'creation_date' => time(),
                    'last_update' => time(),
                    'buyer_lang' => 'fr',
                ]
            );
        }

        $before = $DB->count_records('user');

        $service =
            new CommerceLegacyDigitalProvisioningService(
                $DB,
                new CommerceCustomerIdentitySimilarityService($DB),
                new CommerceCustomerIdentityReconciliationService($DB)
            );
        $plans =
            (
                new CommerceLegacyDigitalBulkProvisioningService(
                    $service
                )
            )->preview(
                [
                    'one@example.test',
                    'two@example.test',
                ]
            );

        self::assertCount(2, $plans);
        self::assertSame(
            $before,
            $DB->count_records('user')
        );
    }
}
