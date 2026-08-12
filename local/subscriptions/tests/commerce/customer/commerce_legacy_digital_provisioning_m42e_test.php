<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\customer\identity\CommerceCustomerIdentitySimilarityService;
use local_subscriptions\commerce\customer\provisioning\CommerceLegacyDigitalAccountActivationService;
use local_subscriptions\commerce\customer\provisioning\CommerceLegacyDigitalProvisioningPlan;
use local_subscriptions\commerce\customer\provisioning\CommerceLegacyDigitalProvisioningService;
use local_subscriptions\commerce\customer\reconciliation\CommerceCustomerIdentityReconciliationService;

/**
 * @covers \local_subscriptions\commerce\customer\provisioning\CommerceLegacyDigitalProvisioningService
 */
final class commerce_legacy_digital_provisioning_m42e_test extends advanced_testcase {
    public function test_dry_run_does_not_create_user(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->create_legacy_purchase(
            'dryrun@example.test',
            'Dry',
            'Run'
        );

        $before = $DB->count_records('user');

        $plan = $this->service()->plan_email(
            'dryrun@example.test'
        );

        self::assertSame(
            CommerceLegacyDigitalProvisioningPlan::STATUS_CREATABLE,
            $plan->status
        );
        self::assertSame(1, $plan->purchase_count());
        self::assertTrue(
            get_string_manager()->translation_exists(
                $plan->language,
                false
            )
        );
        self::assertSame(
            $before,
            $DB->count_records('user')
        );
    }

    public function test_existing_exact_account_is_never_created_again(): void {
        global $DB;

        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user([
            'email' => 'existing@example.test',
        ]);
        $this->create_legacy_purchase(
            'existing@example.test',
            'Existing',
            'Buyer'
        );

        $plan = $this->service()->plan_email(
            'existing@example.test'
        );

        self::assertSame(
            CommerceLegacyDigitalProvisioningPlan::STATUS_EXISTING_ACCOUNT,
            $plan->status
        );
        self::assertSame(
            [(int)$user->id],
            $plan->exactuserids
        );
    }

    public function test_similar_account_blocks_creation_by_default(): void {
        $this->resetAfterTest(true);

        $this->getDataGenerator()->create_user([
            'email' => 'nata.other@example.test',
            'firstname' => 'Nataliya',
            'lastname' => 'Kutrowski',
        ]);
        $this->create_legacy_purchase(
            'nata.shop@example.test',
            'Nataliya',
            'Kutrowski'
        );

        $plan = $this->service()->plan_email(
            'nata.shop@example.test'
        );

        self::assertSame(
            CommerceLegacyDigitalProvisioningPlan::STATUS_SIMILAR_ACCOUNT,
            $plan->status
        );
        self::assertNotEmpty($plan->similaraccounts);
    }

    public function test_execute_creates_suspended_account_links_all_legacy_purchases_and_queues_activation(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $this->create_legacy_purchase(
            'provision@example.test',
            'Provision',
            'Buyer'
        );
        $this->create_legacy_purchase(
            'provision@example.test',
            'Provision',
            'Buyer'
        );

        $result = $this->service()->execute_email(
            'provision@example.test',
            (int)get_admin()->id
        );

        self::assertSame('created', $result->status);
        self::assertNotNull($result->userid);
        self::assertSame(
            2,
            $DB->count_records(
                'subscription_digital_payment_request',
                ['userid' => $result->userid]
            )
        );

        $user = $DB->get_record(
            'user',
            ['id' => $result->userid],
            '*',
            MUST_EXIST
        );
        self::assertSame(1, (int)$user->suspended);
        self::assertSame(0, (int)$user->confirmed);
        self::assertSame(
            'activation_pending',
            (string)get_user_preferences(
                'local_subscriptions_account_state',
                '',
                (int)$user->id
            )
        );
        self::assertNotNull($result->mailqueueid);
        self::assertTrue(
            $DB->record_exists(
                'local_subs_commerce_mail',
                ['id' => $result->mailqueueid]
            )
        );
    }

    public function test_activation_unsuspends_and_confirms_account(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $this->create_legacy_purchase(
            'activate@example.test',
            'Activate',
            'Buyer'
        );

        $result = $this->service()->execute_email(
            'activate@example.test',
            (int)get_admin()->id
        );

        $user = $DB->get_record(
            'user',
            ['id' => $result->userid],
            '*',
            MUST_EXIST
        );

        $activation =
            new CommerceLegacyDigitalAccountActivationService($DB);
        $issued = $activation->issue_activation_url($user);

        parse_str(
            (string)parse_url(
                $issued['url']->out(false),
                PHP_URL_QUERY
            ),
            $params
        );

        $activation->complete(
            (string)$params['key'],
            (int)$user->id,
            'Aa#Test123456',
            false
        );

        $user = $DB->get_record(
            'user',
            ['id' => $user->id],
            '*',
            MUST_EXIST
        );

        self::assertSame(0, (int)$user->suspended);
        self::assertSame(1, (int)$user->confirmed);
        self::assertSame(
            'ready',
            (string)get_user_preferences(
                'local_subscriptions_account_state',
                '',
                (int)$user->id
            )
        );
    }

    private function service(): CommerceLegacyDigitalProvisioningService {
        global $DB;

        return new CommerceLegacyDigitalProvisioningService(
            $DB,
            new CommerceCustomerIdentitySimilarityService($DB),
            new CommerceCustomerIdentityReconciliationService($DB)
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
                'slug' =>
                    'm42e-product-'
                    . bin2hex(random_bytes(4)),
                'name' => 'M4.2E test product',
                'filename' => 'm42e.pdf',
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
                'buyer_lang' => 'fr',
            ]
        );
    }
}
