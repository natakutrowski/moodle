<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer;

use advanced_testcase;
use local_subscriptions\commerce\customer\quality\CommerceEmailQualityService;
use local_subscriptions\commerce\customer\quality\CommerceLegacyDigitalIdentityService;

final class commerce_795m6_legacy_identity_update_test extends advanced_testcase {
    public function test_update_can_repair_all_records_sharing_old_email(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $productid = $DB->insert_record('subscription_digital_product', (object)[
            'name' => 'Legacy test', 'slug' => 'legacy-m6-test', 'filename' => 'legacy.pdf', 'enabled' => 1,
            'creation_date' => time(), 'last_update' => time(),
        ]);
        $base = [
            'productid' => $productid, 'userid' => null, 'email' => 'nata@gmai.com',
            'firstname' => 'Nata', 'lastname' => 'Test', 'currency' => 'EUR', 'price' => 10,
            'amount_minor' => 1000, 'payment_provider' => 'test', 'status' => 'paid',
            'emailsent' => 0, 'receipt_sent' => 0, 'creation_date' => time(), 'last_update' => time(),
            'attempts' => 0, 'locked_list_price' => 10, 'locked_discount_percent' => 0,
            'locked_discount_amount' => 0, 'locked_final_price' => 10, 'locked_at' => time(),
        ];
        $id1 = $DB->insert_record('subscription_digital_payment_request', (object)$base);
        $id2 = $DB->insert_record('subscription_digital_payment_request', (object)$base);

        $service = new CommerceLegacyDigitalIdentityService($DB, new CommerceEmailQualityService());
        $result = $service->update($id1, 'nata@gmail.com', 'Nataliya', 'Test', true);

        $this->assertSame(2, $result['updated']);
        foreach ([$id1, $id2] as $id) {
            $record = $DB->get_record('subscription_digital_payment_request', ['id' => $id], '*', MUST_EXIST);
            $this->assertSame('nata@gmail.com', $record->email);
            $this->assertSame('Nataliya', $record->firstname);
        }
    }
}
