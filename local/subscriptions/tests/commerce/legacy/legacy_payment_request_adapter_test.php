<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\payment\legacy\LegacyPaymentRequestAdapter;
use local_subscriptions\commerce\payment\legacy\LegacyPaymentRequestContext;
use local_subscriptions\commerce\payment\legacy\LegacyPaymentRequestMappingException;
use local_subscriptions\payment\Provider;

/**
 * Tests the transitional Commerce to Legacy payment request adapter.
 *
 * @covers \local_subscriptions\commerce\payment\legacy\LegacyPaymentRequestContext
 * @covers \local_subscriptions\commerce\payment\legacy\LegacyPaymentRequestAdapter
 * @covers \local_subscriptions\commerce\payment\legacy\LegacyPaymentRequestMappingException
 */
final class legacy_payment_request_adapter_test
    extends advanced_testcase {

    public function test_subscription_request_is_loaded_and_validated():
        void {
        global $DB;

        $this->resetAfterTest();

        $planid =
            $this->create_plan();

        $subscriptionid =
            $this->create_subscription(
                $planid
            );

        $paymentrequestid =
            $DB->insert_record(
                LegacyPaymentRequestContext::TABLE_SUBSCRIPTION,
                (object)[
                    'planid' =>
                        $planid,

                    'userid' =>
                        null,

                    'email' =>
                        'student@example.com',

                    'firstname' =>
                        'Test',

                    'lastname' =>
                        'Student',

                    'subscriptionid' =>
                        $subscriptionid,

                    'currency' =>
                        'EUR',

                    'price' =>
                        120.00,

                    'amount_minor' =>
                        12000,

                    'payment_provider' =>
                        Provider::STRIPE,

                    'status' =>
                        'pending',

                    'creation_date' =>
                        time(),

                    'last_update' =>
                        time(),

                    'operation' =>
                        'purchase_new',

                    'locked_list_price' =>
                        120.00,

                    'locked_discount_percent' =>
                        0,

                    'locked_discount_amount' =>
                        0,

                    'locked_final_price' =>
                        120.00,

                    'locked_at' =>
                        time(),
                ]
            );

        $context =
            new LegacyPaymentRequestContext(
                $paymentrequestid,
                LegacyPaymentRequestContext::TABLE_SUBSCRIPTION,
                LegacyPaymentRequestContext::CONTEXT_SUBSCRIPTION,
                Provider::STRIPE,
                'sub'
            );

        $adapter =
            new LegacyPaymentRequestAdapter(
                $DB
            );

        $record = $adapter->load_and_validate(
            $context,
            12000,
            'EUR',
            'student@example.com'
        );

        $this->assertSame(
            $paymentrequestid,
            (int)$record->id
        );

        $this->assertSame(
            Provider::STRIPE,
            $record->payment_provider
        );
    }

    public function test_digital_request_is_loaded_and_validated():
        void {
        global $DB;

        $this->resetAfterTest();

        $productid =
            $this->create_digital_product();

        $paymentrequestid =
            $DB->insert_record(
                LegacyPaymentRequestContext::TABLE_DIGITAL,
                (object)[
                    'productid' =>
                        $productid,

                    'userid' =>
                        null,

                    'email' =>
                        'buyer@example.com',

                    'firstname' =>
                        'Digital',

                    'lastname' =>
                        'Buyer',

                    'currency' =>
                        'RUB',

                    'price' =>
                        1500.00,

                    'amount_minor' =>
                        150000,

                    'payment_provider' =>
                        Provider::ALFA,

                    'status' =>
                        'pending',

                    'creation_date' =>
                        time(),

                    'last_update' =>
                        time(),

                    'locked_list_price' =>
                        1500.00,

                    'locked_discount_percent' =>
                        0,

                    'locked_discount_amount' =>
                        0,

                    'locked_final_price' =>
                        1500.00,

                    'locked_at' =>
                        time(),

                    'buyer_lang' =>
                        'ru',
                ]
            );

        $context =
            new LegacyPaymentRequestContext(
                $paymentrequestid,
                LegacyPaymentRequestContext::TABLE_DIGITAL,
                LegacyPaymentRequestContext::CONTEXT_DIGITAL_PRODUCT,
                Provider::ALFA,
                'digital',
                'ru'
            );

        $adapter =
            new LegacyPaymentRequestAdapter(
                $DB
            );

        $record = $adapter->load_and_validate(
            $context,
            150000,
            'RUB',
            'buyer@example.com'
        );

        $this->assertSame(
            $paymentrequestid,
            (int)$record->id
        );

        $this->assertSame(
            $productid,
            (int)$record->productid
        );
    }

    public function test_unapproved_table_is_rejected():
        void {
        $this->expectException(
            LegacyPaymentRequestMappingException::class
        );

        new LegacyPaymentRequestContext(
            123,
            'user',
            LegacyPaymentRequestContext::CONTEXT_SUBSCRIPTION,
            Provider::STRIPE,
            'sub'
        );
    }

    public function test_table_context_mismatch_is_rejected():
        void {
        $this->expectException(
            LegacyPaymentRequestMappingException::class
        );

        new LegacyPaymentRequestContext(
            123,
            LegacyPaymentRequestContext::TABLE_DIGITAL,
            LegacyPaymentRequestContext::CONTEXT_SUBSCRIPTION,
            Provider::STRIPE,
            'digital'
        );
    }

    public function test_provider_mismatch_is_rejected():
        void {
        global $DB;

        $this->resetAfterTest();

        $productid =
            $this->create_digital_product();

        $paymentrequestid =
            $DB->insert_record(
                LegacyPaymentRequestContext::TABLE_DIGITAL,
                (object)[
                    'productid' =>
                        $productid,

                    'email' =>
                        'buyer@example.com',

                    'currency' =>
                        'EUR',

                    'price' =>
                        19.00,

                    'amount_minor' =>
                        1900,

                    'payment_provider' =>
                        Provider::STRIPE,

                    'status' =>
                        'pending',

                    'creation_date' =>
                        time(),

                    'last_update' =>
                        time(),

                    'locked_list_price' =>
                        19.00,

                    'locked_discount_percent' =>
                        0,

                    'locked_discount_amount' =>
                        0,

                    'locked_final_price' =>
                        19.00,

                    'locked_at' =>
                        time(),

                    'buyer_lang' =>
                        'fr',
                ]
            );

        $context =
            new LegacyPaymentRequestContext(
                $paymentrequestid,
                LegacyPaymentRequestContext::TABLE_DIGITAL,
                LegacyPaymentRequestContext::CONTEXT_DIGITAL_PRODUCT,
                Provider::ALFA,
                'digital'
            );

        $adapter =
            new LegacyPaymentRequestAdapter(
                $DB
            );

        try {
            $adapter->load_and_validate(
                $context,
                1900,
                'EUR',
                'buyer@example.com'
            );

            $this->fail(
                'The Legacy provider mismatch should have been rejected.'
            );
        } catch (
            LegacyPaymentRequestMappingException $exception
        ) {
            $this->assertSame(
                'legacy_payment_provider_mismatch',
                $exception->get_mapping_code()
            );
        }
    }

    public function test_amount_mismatch_is_rejected():
        void {
        global $DB;

        $this->resetAfterTest();

        $productid =
            $this->create_digital_product();

        $paymentrequestid =
            $DB->insert_record(
                LegacyPaymentRequestContext::TABLE_DIGITAL,
                (object)[
                    'productid' =>
                        $productid,

                    'email' =>
                        'buyer@example.com',

                    'currency' =>
                        'EUR',

                    'price' =>
                        19.00,

                    'amount_minor' =>
                        1900,

                    'payment_provider' =>
                        Provider::STRIPE,

                    'status' =>
                        'pending',

                    'creation_date' =>
                        time(),

                    'last_update' =>
                        time(),

                    'locked_list_price' =>
                        19.00,

                    'locked_discount_percent' =>
                        0,

                    'locked_discount_amount' =>
                        0,

                    'locked_final_price' =>
                        19.00,

                    'locked_at' =>
                        time(),

                    'buyer_lang' =>
                        'fr',
                ]
            );

        $context =
            new LegacyPaymentRequestContext(
                $paymentrequestid,
                LegacyPaymentRequestContext::TABLE_DIGITAL,
                LegacyPaymentRequestContext::CONTEXT_DIGITAL_PRODUCT,
                Provider::STRIPE,
                'digital'
            );

        $adapter =
            new LegacyPaymentRequestAdapter(
                $DB
            );

        try {
            $adapter->load_and_validate(
                $context,
                2000,
                'EUR',
                'buyer@example.com'
            );

            $this->fail(
                'The amount mismatch should have been rejected.'
            );
        } catch (
            LegacyPaymentRequestMappingException $exception
        ) {
            $this->assertSame(
                'legacy_payment_amount_mismatch',
                $exception->get_mapping_code()
            );
        }
    }

    public function test_currency_mismatch_is_rejected():
        void {
        global $DB;

        $this->resetAfterTest();

        $productid =
            $this->create_digital_product();

        $paymentrequestid =
            $DB->insert_record(
                LegacyPaymentRequestContext::TABLE_DIGITAL,
                (object)[
                    'productid' =>
                        $productid,

                    'email' =>
                        'buyer@example.com',

                    'currency' =>
                        'EUR',

                    'price' =>
                        19.00,

                    'amount_minor' =>
                        1900,

                    'payment_provider' =>
                        Provider::STRIPE,

                    'status' =>
                        'pending',

                    'creation_date' =>
                        time(),

                    'last_update' =>
                        time(),

                    'locked_list_price' =>
                        19.00,

                    'locked_discount_percent' =>
                        0,

                    'locked_discount_amount' =>
                        0,

                    'locked_final_price' =>
                        19.00,

                    'locked_at' =>
                        time(),

                    'buyer_lang' =>
                        'fr',
                ]
            );

        $context =
            new LegacyPaymentRequestContext(
                $paymentrequestid,
                LegacyPaymentRequestContext::TABLE_DIGITAL,
                LegacyPaymentRequestContext::CONTEXT_DIGITAL_PRODUCT,
                Provider::STRIPE,
                'digital'
            );

        $adapter =
            new LegacyPaymentRequestAdapter(
                $DB
            );

        try {
            $adapter->load_and_validate(
                $context,
                1900,
                'USD',
                'buyer@example.com'
            );

            $this->fail(
                'The currency mismatch should have been rejected.'
            );
        } catch (
            LegacyPaymentRequestMappingException $exception
        ) {
            $this->assertSame(
                'legacy_payment_currency_mismatch',
                $exception->get_mapping_code()
            );
        }
    }

    public function test_context_can_be_created_from_metadata():
        void {
        $context =
            LegacyPaymentRequestContext::from_metadata(
                [
                    'legacy_payment_request_id' =>
                        42,

                    'legacy_payment_request_table' =>
                        LegacyPaymentRequestContext::TABLE_DIGITAL,

                    'legacy_payment_context' =>
                        LegacyPaymentRequestContext::CONTEXT_DIGITAL_PRODUCT,

                    'legacy_language' =>
                        'ru',
                ],
                Provider::ALFA
            );

        $this->assertSame(
            42,
            $context->get_payment_request_id()
        );

        $this->assertSame(
            'digital',
            $context->get_order_number_prefix()
        );

        $this->assertSame(
            'digital-42',
            $context->get_order_number()
        );

        $this->assertTrue(
            $context->is_digital()
        );
    }

    private function create_plan(): int {
        global $DB;

        $now = time();

        $scopeid = (int)$DB->insert_record(
            'subscription_access_scope',
            (object)[
                'name' =>
                    'Commerce bridge test scope '
                    . uniqid(),

                'course_ids' =>
                    '[]',

                'creation_date' =>
                    $now,

                'last_update' =>
                    $now,
            ]
        );

        return (int)$DB->insert_record(
            'subscription_plan',
            (object)[
                'name' =>
                    'Commerce bridge test plan '
                    . uniqid(),

                'access_scope_id' =>
                    $scopeid,

                'duration_key' =>
                    '1month',

                'is_active' =>
                    1,

                'is_recurring' =>
                    0,

                'is_trial' =>
                    0,

                'expiry_reminder_enabled' =>
                    1,

                'creation_date' =>
                    $now,

                'last_update' =>
                    $now,
            ]
        );
    }

    private function create_subscription(
        int $planid
    ): int {
        global $DB;

        $user =
            $this->getDataGenerator()
                ->create_user();

        $now = time();

        return (int)$DB->insert_record(
            'user_subscription',
            (object)[
                'userid' =>
                    (int)$user->id,

                'planid' =>
                    $planid,

                'status' =>
                    'pending',

                'start_date' =>
                    $now,

                'end_date' =>
                    $now + DAYSECS,

                'creation_date' =>
                    $now,

                'last_update' =>
                    $now,

                'payment_failed' =>
                    0,

                'discount_percent' =>
                    0,

                'discount_amount' =>
                    0,
            ]
        );
    }
    private function create_digital_product(): int {
        global $DB;

        return (int)$DB->insert_record(
            'subscription_digital_product',
            (object)[
                'slug' =>
                    'commerce-bridge-test-' . uniqid(),

                'name' =>
                    'Commerce bridge test product',

                'active' =>
                    1,

                'sortorder' =>
                    1,

                'timecreated' =>
                    time(),

                'timemodified' =>
                    time(),
            ]
        );
    }
}