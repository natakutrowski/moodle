<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\customer\identity\CommerceCustomerIdentitySimilarityService;
use local_subscriptions\commerce\customer\identity\CommerceLegacyDigitalIdentityLinkService;
use local_subscriptions\commerce\customer\reconciliation\CommerceCustomerIdentityReconciliationService;

final class commerce_customer_cross_source_native_visibility_m42h_test extends advanced_testcase {
    public function test_cross_source_link_updates_native_purchase_and_digital_access(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $user = $this->getDataGenerator()->create_user([
            'firstname' => 'даша',
            'lastname' => 'Зобнина',
            'email' => 'dzobnina06@gmail.com',
        ]);

        $legacyid = $this->create_legacy_purchase(
            'dzobninaa@gmail.com',
            'Дарья',
            'Зобнина'
        );

        $reference = 'cmp_' . bin2hex(random_bytes(10));
        $purchaseid = $DB->insert_record(
            'local_subscriptions_commerce_purchase',
            (object)[
                'purchaseuuid' => bin2hex(random_bytes(16)),
                'reference' => $reference,
                'type' => 'digital',
                'legacyfamily' => 'digital',
                'legacyid' => $legacyid,
                'userid' => null,
                'customeremail' => 'dzobninaa@gmail.com',
                'status' => 'fulfilled',
                'currency' => 'EUR',
                'subtotalminor' => 990,
                'discountminor' => 0,
                'totalminor' => 990,
                'customerjson' => '{}',
                'snapshotjson' => '{}',
                'metadatajson' => '{}',
                'snapshotversion' => 1,
                'timecreated' => time(),
                'timemodified' => time(),
            ]
        );

        $accessid = $DB->insert_record(
            'local_subs_commerce_dig_access',
            (object)[
                'grantreference' => 'ga_' . bin2hex(random_bytes(8)),
                'idempotencykey' => 'a:' . bin2hex(random_bytes(12)),
                'purchasereference' => $reference,
                'productsku' => 'DIGITAL.TEST',
                'resourcekey' => 'legacy:test',
                'beneficiaryuserid' => null,
                'beneficiaryemail' => 'dzobninaa@gmail.com',
                'downloadtoken' => bin2hex(random_bytes(32)),
                'maxdownloads' => null,
                'downloadcount' => 0,
                'validfrom' => time(),
                'validuntil' => null,
                'status' => 'active',
                'lastdownloadat' => null,
                'timecreated' => time(),
                'timemodified' => time(),
            ]
        );

        $service = new CommerceLegacyDigitalIdentityLinkService(
            $DB,
            new CommerceCustomerIdentitySimilarityService($DB),
            new CommerceCustomerIdentityReconciliationService($DB)
        );

        $result = $service->execute(
            'dzobninaa@gmail.com',
            (int)$user->id,
            (int)get_admin()->id
        );

        self::assertSame(
            (int)$user->id,
            (int)$DB->get_field(
                'subscription_digital_payment_request',
                'userid',
                ['id' => $legacyid]
            )
        );
        self::assertSame(
            (int)$user->id,
            (int)$DB->get_field(
                'local_subscriptions_commerce_purchase',
                'userid',
                ['id' => $purchaseid]
            )
        );
        self::assertSame(
            (int)$user->id,
            (int)$DB->get_field(
                'local_subs_commerce_dig_access',
                'beneficiaryuserid',
                ['id' => $accessid]
            )
        );
        self::assertSame(1, $result->nativepurchases);
        self::assertSame(1, $result->nativepurchaseslinked);
    }

    public function test_repair_works_when_legacy_row_is_already_linked(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $user = $this->getDataGenerator()->create_user([
            'firstname' => 'даша',
            'lastname' => 'Зобнина',
            'email' => 'dzobnina06@gmail.com',
        ]);

        $legacyid = $this->create_legacy_purchase(
            'dzobninaa@gmail.com',
            'Дарья',
            'Зобнина'
        );
        $DB->set_field(
            'subscription_digital_payment_request',
            'userid',
            (int)$user->id,
            ['id' => $legacyid]
        );

        $purchaseid = $DB->insert_record(
            'local_subscriptions_commerce_purchase',
            (object)[
                'purchaseuuid' => bin2hex(random_bytes(16)),
                'reference' => 'cmp_' . bin2hex(random_bytes(10)),
                'type' => 'digital',
                'legacyfamily' => 'digital',
                'legacyid' => $legacyid,
                'userid' => null,
                'customeremail' => 'dzobninaa@gmail.com',
                'status' => 'fulfilled',
                'currency' => 'EUR',
                'subtotalminor' => 990,
                'discountminor' => 0,
                'totalminor' => 990,
                'customerjson' => '{}',
                'snapshotjson' => '{}',
                'metadatajson' => '{}',
                'snapshotversion' => 1,
                'timecreated' => time(),
                'timemodified' => time(),
            ]
        );

        $service = new CommerceLegacyDigitalIdentityLinkService(
            $DB,
            new CommerceCustomerIdentitySimilarityService($DB),
            new CommerceCustomerIdentityReconciliationService($DB)
        );

        $preview = $service->preview(
            'dzobninaa@gmail.com',
            (int)$user->id
        );
        self::assertSame(1, $preview->legacypurchases);
        self::assertSame(1, $preview->nativepurchases);
        self::assertSame(0, $preview->nativepurchaseslinked);

        $service->execute(
            'dzobninaa@gmail.com',
            (int)$user->id,
            (int)get_admin()->id
        );

        self::assertSame(
            (int)$user->id,
            (int)$DB->get_field(
                'local_subscriptions_commerce_purchase',
                'userid',
                ['id' => $purchaseid]
            )
        );
    }

    private function create_legacy_purchase(
        string $email,
        string $firstname,
        string $lastname
    ): int {
        global $DB;

        $productid = $DB->insert_record(
            'subscription_digital_product',
            (object)[
                'slug' => 'm42h-' . bin2hex(random_bytes(4)),
                'name' => 'M4.2H test',
                'filename' => 'test.pdf',
            ]
        );

        return (int)$DB->insert_record(
            'subscription_digital_payment_request',
            (object)[
                'productid' => $productid,
                'userid' => null,
                'email' => $email,
                'firstname' => $firstname,
                'lastname' => $lastname,
                'currency' => 'EUR',
                'price' => 9.90,
                'amount_minor' => 990,
                'payment_provider' => 'legacy',
                'status' => 'paid',
                'creation_date' => time(),
                'last_update' => time(),
                'buyer_lang' => 'ru',
            ]
        );
    }
}
