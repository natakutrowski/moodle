<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\payment\legacy\LegacyPaymentRequestAdapter;
use local_subscriptions\commerce\payment\legacy\LegacyPaymentRequestContext;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderException;
use local_subscriptions\commerce\payment\provider\alfa\AlfaGatewayRequest;
use local_subscriptions\commerce\payment\provider\alfa\AlfaGatewayResponse;
use local_subscriptions\commerce\payment\provider\alfa\LegacyAlfaPaymentGateway;
use local_subscriptions\constants\Operation;
use local_subscriptions\payment\PaymentGatewayInterface;
use local_subscriptions\payment\Provider;
use local_subscriptions\payment\dto\CheckoutInitResult;
use local_subscriptions\payment\dto\InternalEvent;
use local_subscriptions\payment\dto\ProviderActionResult;
use local_subscriptions\payment\dto\ProviderCapabilities;

/**
 * Tests the transitional bridge to the Legacy Alfa gateway.
 *
 * @covers \local_subscriptions\commerce\payment\provider\alfa\LegacyAlfaPaymentGateway
 */
final class legacy_alfa_payment_gateway_test
    extends advanced_testcase {

    public function test_subscription_payment_is_delegated():
        void {
        global $DB;

        $this->resetAfterTest();

        $record =
            $this->create_subscription_payment_request();

        $capturedrequest = null;
        $capturedoptions = [];

        $legacygateway =
            $this->create_fake_gateway(
                $capturedrequest,
                $capturedoptions
            );

        $bridge =
            new LegacyAlfaPaymentGateway(
                new LegacyPaymentRequestAdapter(
                    $DB
                ),
                $legacygateway
            );

        $response = $bridge->register(
            new AlfaGatewayRequest(
                'subscription-payment-' . $record->id,
                1200000,
                'RUB',
                'CampusFR A1',
                'student@example.com',
                'https://example.test/alfa-return',
                'https://example.test/alfa-fail',
                'commerce:alfa:subscription:1',
                [
                    'commerce_reference' =>
                        'subscription:payment:' . $record->id,

                    'commerce_payment_id' =>
                        '474',

                    'commerce_purchase_uuid' =>
                        '1234567890abcdef1234567890abcdef',

                    'legacy_payment_request_id' =>
                        (int)$record->id,

                    'legacy_payment_request_table' =>
                        LegacyPaymentRequestContext::TABLE_SUBSCRIPTION,

                    'legacy_payment_context' =>
                        LegacyPaymentRequestContext::CONTEXT_SUBSCRIPTION,

                    'legacy_order_number_prefix' =>
                        'sub',

                    'legacy_operation' =>
                        Operation::PURCHASE_NEW,

                    'legacy_language' =>
                        'ru',

                    'legacy_plan_id' =>
                        (int)$record->planid,

                    'userid' =>
                        (int)$record->userid,
                ]
            )
        );

        $this->assertInstanceOf(
            \stdClass::class,
            $capturedrequest
        );

        $this->assertSame(
            (int)$record->id,
            (int)$capturedrequest->id
        );

        $this->assertSame(
            'subscription_payment_request',
            $capturedoptions[
                'payment_request_table'
            ]
        );

        $this->assertSame(
            'sub',
            $capturedoptions[
                'order_number_prefix'
            ]
        );

        $this->assertSame(
            'ru',
            $capturedoptions['language']
        );

        $this->assertSame(
            Operation::PURCHASE_NEW,
            $capturedoptions['operation']
        );

        $this->assertSame(
            AlfaGatewayResponse::STATUS_REGISTERED,
            $response->get_status()
        );

        $this->assertSame(
            'alfa-order-test-123',
            $response->get_order_id()
        );

        $this->assertSame(
            'https://alfa.example.test/payment-form',
            $response->get_form_url()
        );
    }

    public function test_digital_payment_is_delegated():
        void {
        global $DB;

        $this->resetAfterTest();

        $record =
            $this->create_digital_payment_request();

        $capturedrequest = null;
        $capturedoptions = [];

        $bridge =
            new LegacyAlfaPaymentGateway(
                new LegacyPaymentRequestAdapter(
                    $DB
                ),
                $this->create_fake_gateway(
                    $capturedrequest,
                    $capturedoptions
                )
            );

        $response = $bridge->register(
            new AlfaGatewayRequest(
                'digital-payment-' . $record->id,
                150000,
                'RUB',
                'Produit numérique CampusFR',
                'buyer@example.com',
                'https://example.test/digital-return',
                'https://example.test/digital-fail',
                'commerce:alfa:digital:1',
                [
                    'commerce_reference' =>
                        'digital:payment:' . $record->id,

                    'commerce_payment_id' =>
                        '475',

                    'commerce_purchase_uuid' =>
                        'abcdef1234567890abcdef1234567890',

                    'legacy_payment_request_id' =>
                        (int)$record->id,

                    'legacy_payment_request_table' =>
                        LegacyPaymentRequestContext::TABLE_DIGITAL,

                    'legacy_payment_context' =>
                        LegacyPaymentRequestContext::CONTEXT_DIGITAL_PRODUCT,

                    'legacy_order_number_prefix' =>
                        'digital',

                    'legacy_language' =>
                        'ru',

                    'legacy_product_id' =>
                        (int)$record->productid,
                ]
            )
        );

        $this->assertSame(
            LegacyPaymentRequestContext::TABLE_DIGITAL,
            $capturedoptions[
                'payment_request_table'
            ]
        );

        $this->assertSame(
            'digital',
            $capturedoptions[
                'order_number_prefix'
            ]
        );

        $this->assertSame(
            '475',
            $response->get_metadata()[
                'commerce_payment_id'
            ]
        );

        $this->assertSame(
            'abcdef1234567890abcdef1234567890',
            $response->get_metadata()[
                'commerce_purchase_uuid'
            ]
        );

        $this->assertArrayNotHasKey(
            'legacy_payment_context',
            $response->get_metadata()
        );
    }

    public function test_retrieve_is_explicitly_unsupported():
        void {
        global $DB;

        $this->resetAfterTest();

        $capturedrequest = null;
        $capturedoptions = [];

        $bridge =
            new LegacyAlfaPaymentGateway(
                new LegacyPaymentRequestAdapter(
                    $DB
                ),
                $this->create_fake_gateway(
                    $capturedrequest,
                    $capturedoptions
                )
            );

        try {
            $bridge->retrieve(
                'alfa-order-test-123'
            );

            $this->fail(
                'Alfa retrieval should not be supported by the Legacy bridge.'
            );
        } catch (
            CommercePaymentProviderException $exception
        ) {
            $this->assertSame(
                'legacy_alfa_retrieval_not_supported',
                $exception->get_provider_code()
            );
        }
    }

    public function test_cancel_is_explicitly_unsupported():
        void {
        global $DB;

        $this->resetAfterTest();

        $capturedrequest = null;
        $capturedoptions = [];

        $bridge =
            new LegacyAlfaPaymentGateway(
                new LegacyPaymentRequestAdapter(
                    $DB
                ),
                $this->create_fake_gateway(
                    $capturedrequest,
                    $capturedoptions
                )
            );

        try {
            $bridge->cancel(
                'alfa-order-test-123'
            );

            $this->fail(
                'Alfa cancellation should not be supported by the Legacy bridge.'
            );
        } catch (
            CommercePaymentProviderException $exception
        ) {
            $this->assertSame(
                'legacy_alfa_cancellation_not_supported',
                $exception->get_provider_code()
            );
        }
    }

    private function create_fake_gateway(
        ?\stdClass &$capturedrequest,
        array &$capturedoptions
    ): PaymentGatewayInterface {
        return new class(
            $capturedrequest,
            $capturedoptions
        ) implements PaymentGatewayInterface {

            public function __construct(
                private ?\stdClass &$capturedrequest,
                private array &$capturedoptions
            ) {
            }

            public function create_checkout_session(
                \stdClass $payment_request,
                array $options = []
            ): CheckoutInitResult {
                $this->capturedrequest =
                    clone $payment_request;

                $this->capturedoptions =
                    $options;

                if (
                    empty(
                        $options[
                            'payment_request_table'
                        ]
                    )
                    || empty(
                        $options[
                            'order_number_prefix'
                        ]
                    )
                    || empty($options['returnurl'])
                    || empty($options['failurl'])
                ) {
                    throw new \coding_exception(
                        'The bridge did not build the expected Alfa options.'
                    );
                }

                return new CheckoutInitResult(
                    'https://alfa.example.test/payment-form',
                    'alfa-order-test-123'
                );
            }

            public function parse_webhook(
                string $payload,
                array $headers
            ): InternalEvent {
                throw new \coding_exception(
                    'Not used by this test.'
                );
            }

            public function cancel_subscription(
                string $provider_subscription_id,
                array $opts = []
            ): ProviderActionResult {
                return new ProviderActionResult(
                    false,
                    'Not implemented'
                );
            }

            public function resume_subscription(
                string $provider_subscription_id,
                array $opts = []
            ): ProviderActionResult {
                return new ProviderActionResult(
                    false,
                    'Not implemented'
                );
            }

            public function upgrade_subscription(
                string $provider_subscription_id,
                array $opts
            ): ProviderActionResult {
                return new ProviderActionResult(
                    false,
                    'Not implemented'
                );
            }

            public function get_customer_portal_url(
                ?string $provider_customer_id,
                array $opts = []
            ): ?string {
                return null;
            }

            public function capabilities():
                ProviderCapabilities {
                $capabilities =
                    new ProviderCapabilities();

                $capabilities->supports_recurring =
                    false;

                $capabilities->supports_portal =
                    false;

                $capabilities->currencies = [
                    'RUB',
                ];

                return $capabilities;
            }
        };
    }

    private function create_subscription_payment_request():
        \stdClass {
        global $DB;

        $now = time();

        $user =
            $this->getDataGenerator()
                ->create_user([
                    'email' =>
                        'student@example.com',

                    'firstname' =>
                        'Test',

                    'lastname' =>
                        'Student',
                ]);

        $scopeid = (int)$DB->insert_record(
            'subscription_access_scope',
            (object)[
                'name' =>
                    'Alfa bridge scope '
                    . uniqid(),

                'course_ids' =>
                    '[]',

                'creation_date' =>
                    $now,

                'last_update' =>
                    $now,
            ]
        );

        $planid = (int)$DB->insert_record(
            'subscription_plan',
            (object)[
                'name' =>
                    'Alfa bridge plan '
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

        $subscriptionid = (int)$DB->insert_record(
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

        $record = (object)[
            'planid' =>
                $planid,

            'userid' =>
                (int)$user->id,

            'email' =>
                'student@example.com',

            'firstname' =>
                'Test',

            'lastname' =>
                'Student',

            'subscriptionid' =>
                $subscriptionid,

            'currency' =>
                'RUB',

            'price' =>
                12000.00,

            'amount_minor' =>
                1200000,

            'payment_provider' =>
                Provider::ALFA,

            'status' =>
                'pending',

            'operation' =>
                Operation::PURCHASE_NEW,

            'attempts' =>
                0,

            'emailsent' =>
                0,

            'reminder_stage' =>
                0,

            'locked_list_price' =>
                12000.00,

            'locked_discount_percent' =>
                0,

            'locked_discount_amount' =>
                0,

            'locked_final_price' =>
                12000.00,

            'locked_at' =>
                $now,

            'creation_date' =>
                $now,

            'last_update' =>
                $now,
        ];

        $record->id = (int)$DB->insert_record(
            LegacyPaymentRequestContext::TABLE_SUBSCRIPTION,
            $record
        );

        return $record;
    }

    private function create_digital_payment_request():
        \stdClass {
        global $DB;

        $record = (object)[
            'productid' =>
                7,

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

            'attempts' =>
                0,

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

            'creation_date' =>
                time(),

            'last_update' =>
                time(),
        ];

        $record->id = (int)$DB->insert_record(
            LegacyPaymentRequestContext::TABLE_DIGITAL,
            $record
        );

        return $record;
    }
}