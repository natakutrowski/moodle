<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\payment\legacy\LegacyPaymentRequestAdapter;
use local_subscriptions\commerce\payment\legacy\LegacyPaymentRequestContext;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderException;
use local_subscriptions\commerce\payment\provider\stripe\LegacyStripePaymentGateway;
use local_subscriptions\commerce\payment\provider\stripe\StripeGatewayRequest;
use local_subscriptions\commerce\payment\provider\stripe\StripeGatewayResponse;
use local_subscriptions\payment\PaymentGatewayInterface;
use local_subscriptions\payment\Provider;
use local_subscriptions\payment\dto\CheckoutInitResult;
use local_subscriptions\payment\dto\InternalEvent;
use local_subscriptions\payment\dto\ProviderActionResult;
use local_subscriptions\payment\dto\ProviderCapabilities;

/**
 * Tests the transitional bridge to the Legacy Stripe gateway.
 *
 * @covers \local_subscriptions\commerce\payment\provider\stripe\LegacyStripePaymentGateway
 */
final class legacy_stripe_payment_gateway_test
    extends advanced_testcase {

    public function test_subscription_checkout_is_delegated():
        void {
        global $DB;

        $this->resetAfterTest();

        $record =
            $this->create_subscription_payment_request();

        $fakegateway =
            $this->create_fake_gateway();

        $bridge =
            new LegacyStripePaymentGateway(
                new LegacyPaymentRequestAdapter(
                    $DB
                ),
                $fakegateway
            );

        $response =
            $bridge->create_checkout_session(
                new StripeGatewayRequest(
                    'subscription:payment:'
                        . $record->id,

                    12000,
                    'EUR',
                    'student@example.com',

                    [
                        [
                            'reference' =>
                                'subscription-plan:14',

                            'description' =>
                                'CampusFR A1',

                            'quantity' =>
                                1,

                            'unitamountminor' =>
                                12000,

                            'currency' =>
                                'EUR',
                        ],
                    ],

                    'https://example.test/success',
                    'https://example.test/cancel',
                    'commerce:stripe:test:1',

                    [
                        'legacy_payment_request_id' =>
                            (int)$record->id,

                        'legacy_payment_request_table' =>
                            LegacyPaymentRequestContext::TABLE_SUBSCRIPTION,

                        'legacy_payment_context' =>
                            LegacyPaymentRequestContext::CONTEXT_SUBSCRIPTION,

                        'legacy_order_number_prefix' =>
                            'sub',

                        'legacy_mode' =>
                            'payment',

                        'legacy_plan_id' =>
                            (int)$record->planid,

                        'legacy_operation' =>
                            'purchase_new',

                        'userid' =>
                            (int)$record->userid,
                    ]
                )
            );

        $this->assertSame(
            StripeGatewayResponse::STATUS_OPEN,
            $response->get_status()
        );

        $this->assertSame(
            'cs_test_commerce_123',
            $response->get_payment_id()
        );

        $this->assertSame(
            'https://checkout.stripe.test/session',
            $response->get_checkout_url()
        );

        $this->assertSame(
            (int)$record->id,
            $response->get_metadata()[
                'legacy_payment_request_id'
            ]
        );
    }

    public function test_digital_checkout_is_delegated():
        void {
        global $DB;

        $this->resetAfterTest();

        $record =
            $this->create_digital_payment_request();

        $bridge =
            new LegacyStripePaymentGateway(
                new LegacyPaymentRequestAdapter(
                    $DB
                ),
                $this->create_fake_gateway()
            );

        $response =
            $bridge->create_checkout_session(
                new StripeGatewayRequest(
                    'digital:payment:'
                        . $record->id,

                    1900,
                    'EUR',
                    'buyer@example.com',

                    [
                        [
                            'reference' =>
                                'digital-product:7',

                            'description' =>
                                'PDF CampusFR',

                            'quantity' =>
                                1,

                            'unitamountminor' =>
                                1900,

                            'currency' =>
                                'EUR',
                        ],
                    ],

                    'https://example.test/digital-success',
                    'https://example.test/digital-cancel',
                    'commerce:stripe:digital:1',

                    [
                        'legacy_payment_request_id' =>
                            (int)$record->id,

                        'legacy_payment_request_table' =>
                            LegacyPaymentRequestContext::TABLE_DIGITAL,

                        'legacy_payment_context' =>
                            LegacyPaymentRequestContext::CONTEXT_DIGITAL_PRODUCT,

                        'legacy_order_number_prefix' =>
                            'digital',

                        'legacy_mode' =>
                            'payment',

                        'legacy_product_id' =>
                            7,

                        'legacy_slug' =>
                            'pdf-campusfr',
                    ]
                )
            );

        $this->assertSame(
            StripeGatewayResponse::STATUS_OPEN,
            $response->get_status()
        );

        $this->assertSame(
            'digital_product',
            $response->get_metadata()[
                'legacy_payment_context'
            ]
        );
    }

    public function test_retrieve_is_explicitly_unsupported():
        void {
        global $DB;

        $this->resetAfterTest();

        $bridge =
            new LegacyStripePaymentGateway(
                new LegacyPaymentRequestAdapter(
                    $DB
                ),
                $this->create_fake_gateway()
            );

        try {
            $bridge->retrieve(
                'cs_test_123'
            );

            $this->fail(
                'Stripe retrieval should not be supported by the Legacy bridge.'
            );
        } catch (
            CommercePaymentProviderException $exception
        ) {
            $this->assertSame(
                'legacy_stripe_retrieval_not_supported',
                $exception->get_provider_code()
            );
        }
    }

    public function test_cancel_is_explicitly_unsupported():
        void {
        global $DB;

        $this->resetAfterTest();

        $bridge =
            new LegacyStripePaymentGateway(
                new LegacyPaymentRequestAdapter(
                    $DB
                ),
                $this->create_fake_gateway()
            );

        try {
            $bridge->cancel(
                'cs_test_123'
            );

            $this->fail(
                'Stripe cancellation should not be supported by the Legacy bridge.'
            );
        } catch (
            CommercePaymentProviderException $exception
        ) {
            $this->assertSame(
                'legacy_stripe_cancellation_not_supported',
                $exception->get_provider_code()
            );
        }
    }

    private function create_fake_gateway():
        PaymentGatewayInterface {
        return new class implements PaymentGatewayInterface {

            public function create_checkout_session(
                \stdClass $payment_request,
                array $options = []
            ): CheckoutInitResult {
                if (
                    empty($payment_request->id)
                    || empty($options['success_url'])
                    || empty($options['cancel_url'])
                    || empty($options['use_locked_amount'])
                ) {
                    throw new \coding_exception(
                        'The bridge did not construct the expected Stripe options.'
                    );
                }

                if (
                    (
                        $options['metadata'][
                            'commerce_reference'
                        ] ?? ''
                    ) === ''
                ) {
                    throw new \coding_exception(
                        'The Commerce reference was not forwarded to Stripe metadata.'
                    );
                }

                return new CheckoutInitResult(
                    'https://checkout.stripe.test/session',
                    'cs_test_commerce_123'
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
                throw new \coding_exception(
                    'Not used by this test.'
                );
            }

            public function resume_subscription(
                string $provider_subscription_id,
                array $opts = []
            ): ProviderActionResult {
                throw new \coding_exception(
                    'Not used by this test.'
                );
            }

            public function upgrade_subscription(
                string $provider_subscription_id,
                array $opts
            ): ProviderActionResult {
                throw new \coding_exception(
                    'Not used by this test.'
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
                    true;

                $capabilities->supports_portal =
                    true;

                $capabilities->currencies = [
                    'EUR',
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
                    'Stripe bridge scope '
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
                    'Stripe bridge plan '
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
                'EUR',

            'price' =>
                120.00,

            'amount_minor' =>
                12000,

            'payment_provider' =>
                Provider::STRIPE,

            'status' =>
                'pending',

            'operation' =>
                'purchase_new',

            'attempts' =>
                0,

            'emailsent' =>
                0,

            'reminder_stage' =>
                0,

            'locked_list_price' =>
                120.00,

            'locked_discount_percent' =>
                0,

            'locked_discount_amount' =>
                0,

            'locked_final_price' =>
                120.00,

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
                'EUR',

            'price' =>
                19.00,

            'amount_minor' =>
                1900,

            'payment_provider' =>
                Provider::STRIPE,

            'status' =>
                'pending',

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