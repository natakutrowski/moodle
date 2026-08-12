<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\customer\identity\CommerceCustomerIdentitySimilarityService;
use local_subscriptions\commerce\customer\identity\CommerceLegacyDigitalIdentityLinkService;
use local_subscriptions\commerce\customer\provisioning\CommerceLegacyDigitalProvisioningPlan;
use local_subscriptions\commerce\customer\provisioning\CommerceLegacyDigitalProvisioningService;
use local_subscriptions\commerce\customer\reconciliation\CommerceCustomerIdentityReconciliationService;

final class commerce_customer_cross_source_identity_m42g_test extends advanced_testcase {
    public function test_cyrillic_name_and_close_email_find_existing_moodle_account(): void {
        global $DB;

        $this->resetAfterTest(true);

        $moodleuser = $this->getDataGenerator()->create_user([
            'firstname' => 'даша',
            'lastname' => 'Зобнина',
            'email' => 'dzobnina06@gmail.com',
        ]);

        $this->create_legacy_purchase(
            'dzobninaa@gmail.com',
            'Дарья',
            'Зобнина'
        );

        $service = new CommerceLegacyDigitalProvisioningService(
            $DB,
            new CommerceCustomerIdentitySimilarityService($DB),
            new CommerceCustomerIdentityReconciliationService($DB)
        );

        $plan = $service->plan_email('dzobninaa@gmail.com');

        self::assertSame(
            CommerceLegacyDigitalProvisioningPlan::STATUS_SIMILAR_ACCOUNT,
            $plan->status
        );
        self::assertNotEmpty($plan->similaraccounts);

        $candidateids = array_map(
            static fn($match): int => (int)$match->second->id,
            $plan->similaraccounts
        );
        self::assertContains((int)$moodleuser->id, $candidateids);
    }

    public function test_legacy_identity_can_be_attached_to_existing_moodle_account_without_changing_it(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $moodleuser = $this->getDataGenerator()->create_user([
            'firstname' => 'даша',
            'lastname' => 'Зобнина',
            'email' => 'dzobnina06@gmail.com',
        ]);

        $purchaseid = $this->create_legacy_purchase(
            'dzobninaa@gmail.com',
            'Дарья',
            'Зобнина'
        );

        $before = $DB->get_record(
            'user',
            ['id' => $moodleuser->id],
            '*',
            MUST_EXIST
        );

        $service = new CommerceLegacyDigitalIdentityLinkService(
            $DB,
            new CommerceCustomerIdentitySimilarityService($DB)
        );

        $preview = $service->preview(
            'dzobninaa@gmail.com',
            (int)$moodleuser->id
        );

        self::assertGreaterThanOrEqual(60, $preview->similarityscore);
        self::assertSame(1, $preview->legacypurchases);

        $service->execute(
            'dzobninaa@gmail.com',
            (int)$moodleuser->id,
            (int)get_admin()->id
        );

        self::assertSame(
            (int)$moodleuser->id,
            (int)$DB->get_field(
                'subscription_digital_payment_request',
                'userid',
                ['id' => $purchaseid]
            )
        );

        $after = $DB->get_record(
            'user',
            ['id' => $moodleuser->id],
            '*',
            MUST_EXIST
        );

        self::assertSame($before->email, $after->email);
        self::assertSame($before->firstname, $after->firstname);
        self::assertSame($before->lastname, $after->lastname);
        self::assertSame($before->suspended, $after->suspended);
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
                'slug' => 'm42g-' . bin2hex(random_bytes(4)),
                'name' => 'M4.2G test',
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
