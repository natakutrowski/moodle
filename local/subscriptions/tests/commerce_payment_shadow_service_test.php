<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\payment\CommercePaymentRequest;
use local_subscriptions\commerce\payment\legacy\LegacyCommercePaymentRequestFactory;
use local_subscriptions\commerce\payment\legacy\LegacyPaymentRequestContext;
use local_subscriptions\commerce\payment\orchestration\CommercePaymentOrchestrator;
use local_subscriptions\commerce\payment\orchestration\CommercePaymentProviderContextFactory;
use local_subscriptions\commerce\payment\provider\CommercePaymentProvider;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderCapabilities;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderContext;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderRegistry;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderValidationResult;
use local_subscriptions\commerce\payment\result\CommercePaymentResult;
use local_subscriptions\commerce\payment\shadow\CommercePaymentShadowService;
use local_subscriptions\payment\Provider;

/**
 * Tests the non-executing Commerce payment shadow service.
 *
 * @covers \local_subscriptions\commerce\payment\shadow\CommercePaymentShadowService
 */
final class commerce_payment_shadow_service_test
    extends advanced_testcase {

    public function test_compatible_request_produces_compatible_report():
        void {
        $initializecalls = 0;

        $service =
            $this->create_service(
                $initializecalls
            );

        $report =
            $service->evaluate(
                $this->create_payment_request(),
                LegacyPaymentRequestContext::TABLE_SUBSCRIPTION,
                [
                    'returnurl' =>
                        'https://example.test/success',

                    'failurl' =>
                        'https://example.test/cancel',
                ],
                false
            );

        $this->assertTrue(
            $report->is_compatible()
        );

        $this->assertSame(
            [],
            $report->get_differences()
        );

        $this->assertSame(
            [],
            $report->get_errors()
        );

        $this->assertNotNull(
            $report->get_simulation()
        );

        $this->assertTrue(
            $report
                ->get_simulation()
                ->is_simulated()
        );

        $this->assertSame(
            0,
            $initializecalls,
            'Shadow evaluation must never initialize a provider payment.'
        );
    }

    public function test_provider_validation_error_makes_report_incompatible():
        void {
        $initializecalls = 0;

        $service =
            $this->create_service(
                $initializecalls,
                false
            );

        $report =
            $service->evaluate(
                $this->create_payment_request(),
                LegacyPaymentRequestContext::TABLE_SUBSCRIPTION,
                [],
                false
            );

        $this->assertFalse(
            $report->is_compatible()
        );

        $this->assertNotNull(
            $report->get_simulation()
        );

        $this->assertFalse(
            $report
                ->get_simulation()
                ->get_validation()
                ->is_valid()
        );

        $this->assertSame(
            [],
            $report->get_errors()
        );

        $this->assertSame(
            0,
            $initializecalls
        );
    }

    public function test_mapping_exception_is_captured_in_report():
        void {
        $initializecalls = 0;

        $service =
            $this->create_service(
                $initializecalls
            );

        $record =
            $this->create_payment_request();

        unset(
            $record->id
        );

        $report =
            $service->evaluate(
                $record,
                LegacyPaymentRequestContext::TABLE_SUBSCRIPTION,
                [],
                false
            );

        $this->assertFalse(
            $report->is_compatible()
        );

        $this->assertNull(
            $report->get_simulation()
        );

        $this->assertCount(
            1,
            $report->get_errors()
        );

        $this->assertStringContainsString(
            'persisted Legacy payment request id',
            $report->get_errors()[0]['message']
        );

        $this->assertSame(
            0,
            $initializecalls
        );
    }

    public function test_unknown_table_is_captured_in_report():
        void {
        $initializecalls = 0;

        $service =
            $this->create_service(
                $initializecalls
            );

        $report =
            $service->evaluate(
                $this->create_payment_request(),
                'forbidden_payment_table',
                [],
                false
            );

        $this->assertFalse(
            $report->is_compatible()
        );

        $this->assertNull(
            $report->get_simulation()
        );

        $this->assertCount(
            1,
            $report->get_errors()
        );

        $this->assertSame(
            0,
            $initializecalls
        );
    }

    public function test_shadow_context_is_marked_as_test_and_shadow():
        void {
        $initializecalls = 0;
        $capturedcontext = null;

        $service =
            $this->create_service(
                $initializecalls,
                true,
                $capturedcontext
            );

        $report =
            $service->evaluate(
                $this->create_payment_request(),
                LegacyPaymentRequestContext::TABLE_SUBSCRIPTION,
                [],
                false
            );

        $this->assertTrue(
            $report->is_compatible()
        );

        /*
         * simulate() does not pass the context to provider->initialize().
         * Therefore a provider cannot capture it. The assertion below checks
         * the context indirectly through the simulation metadata.
         */
        $metadata =
            $report
                ->get_simulation()
                ->get_metadata();

        $this->assertFalse(
            $metadata['live']
        );

        $this->assertStringStartsWith(
            'commerce:',
            $metadata['idempotencykey']
        );

        $this->assertSame(
            0,
            $initializecalls
        );
    }

    private function create_service(
        int &$initializecalls,
        bool $validationvalid = true,
        ?CommercePaymentProviderContext &$capturedcontext = null
    ): CommercePaymentShadowService {
        $provider =
            new class(
                $initializecalls,
                $validationvalid,
                $capturedcontext
            ) implements CommercePaymentProvider {

                public function __construct(
                    private int &$initializecalls,
                    private readonly bool $validationvalid,
                    private ?CommercePaymentProviderContext
                        &$capturedcontext
                ) {
                }

                public function get_key(): string {
                    return Provider::STRIPE;
                }

                public function get_priority(): int {
                    return 100;
                }

                public function is_available(): bool {
                    return true;
                }

                public function get_capabilities():
                    CommercePaymentProviderCapabilities {
                    return new CommercePaymentProviderCapabilities(
                        [
                            'EUR',
                        ],
                        true,
                        false,
                        false,
                        false,
                        true,
                        [
                            'test' =>
                                true,
                        ]
                    );
                }

                public function supports(
                    CommercePaymentRequest $request
                ): bool {
                    return $request->get_currency()
                        === 'EUR';
                }

                public function validate(
                    CommercePaymentRequest $request
                ): CommercePaymentProviderValidationResult {
                    $result =
                        CommercePaymentProviderValidationResult::valid();

                    if (!$this->validationvalid) {
                        $result->add_error(
                            'shadow_test_invalid',
                            'The shadow test provider rejected the request.'
                        );
                    }

                    return $result;
                }

                public function initialize(
                    CommercePaymentRequest $request,
                    CommercePaymentProviderContext $context
                ): CommercePaymentResult {
                    $this->initializecalls++;
                    $this->capturedcontext =
                        $context;

                    return CommercePaymentResult::pending(
                        $request->get_reference(),
                        Provider::STRIPE,
                        'should-never-be-created'
                    );
                }

                public function retrieve(
                    string $providerpaymentid,
                    CommercePaymentProviderContext $context
                ): CommercePaymentResult {
                    throw new \coding_exception(
                        'Not used by this test.'
                    );
                }

                public function cancel(
                    string $providerpaymentid,
                    CommercePaymentProviderContext $context
                ): CommercePaymentResult {
                    throw new \coding_exception(
                        'Not used by this test.'
                    );
                }
            };

        $orchestrator =
            new CommercePaymentOrchestrator(
                new CommercePaymentProviderRegistry([
                    $provider,
                ])
            );

        return new CommercePaymentShadowService(
            new LegacyCommercePaymentRequestFactory(),
            new CommercePaymentProviderContextFactory(),
            $orchestrator
        );
    }

    private function create_payment_request():
        \stdClass {
        return (object)[
            'id' =>
                42,

            'planid' =>
                14,

            'subscriptionid' =>
                27,

            'userid' =>
                96,

            'email' =>
                'student@example.com',

            'firstname' =>
                'Test',

            'lastname' =>
                'Student',

            'currency' =>
                'EUR',

            'price' =>
                120.00,

            'amount_minor' =>
                12000,

            'payment_provider' =>
                Provider::STRIPE,

            'operation' =>
                'purchase_new',
        ];
    }
}
