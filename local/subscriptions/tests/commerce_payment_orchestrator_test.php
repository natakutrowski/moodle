<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\payment\CommercePaymentCustomer;
use local_subscriptions\commerce\payment\CommercePaymentLine;
use local_subscriptions\commerce\payment\CommercePaymentRequest;
use local_subscriptions\commerce\payment\orchestration\CommercePaymentOrchestrationException;
use local_subscriptions\commerce\payment\orchestration\CommercePaymentOrchestrator;
use local_subscriptions\commerce\payment\orchestration\CommercePaymentValidationException;
use local_subscriptions\commerce\payment\provider\CommercePaymentProvider;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderCapabilities;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderContext;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderRegistry;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderValidationResult;
use local_subscriptions\commerce\payment\result\CommercePaymentAction;
use local_subscriptions\commerce\payment\result\CommercePaymentResult;

/**
 * Tests Commerce payment orchestration.
 *
 * @covers \local_subscriptions\commerce\payment\orchestration\CommercePaymentOrchestrator
 */
final class commerce_payment_orchestrator_test
    extends advanced_testcase {

    public function test_initialize_resolves_validates_and_executes_provider():
        void {
        $request =
            $this->create_request();

        $calls = 0;

        $provider =
            $this->create_provider(
                $calls
            );

        $orchestrator =
            new CommercePaymentOrchestrator(
                new CommercePaymentProviderRegistry([
                    $provider,
                ])
            );

        $initialization =
            $orchestrator->initialize(
                $request,
                new CommercePaymentProviderContext(
                    'test:payment:1',
                    false
                )
            );

        $this->assertSame(
            1,
            $calls
        );

        $this->assertFalse(
            $initialization->is_simulated()
        );

        $this->assertSame(
            'fake',
            $initialization->get_provider_key()
        );

        $this->assertTrue(
            $initialization
                ->requires_customer_action()
        );
    }

    public function test_simulate_never_executes_provider():
        void {
        $request =
            $this->create_request();

        $calls = 0;

        $orchestrator =
            new CommercePaymentOrchestrator(
                new CommercePaymentProviderRegistry([
                    $this->create_provider(
                        $calls
                    ),
                ])
            );

        $initialization =
            $orchestrator->simulate(
                $request,
                new CommercePaymentProviderContext(
                    'test:payment:shadow',
                    false
                )
            );

        $this->assertSame(
            0,
            $calls
        );

        $this->assertTrue(
            $initialization->is_simulated()
        );

        $this->assertNull(
            $initialization
                ->get_payment_result()
        );
    }

    public function test_invalid_request_is_not_initialized():
        void {
        $request =
            $this->create_request();

        $calls = 0;

        $provider =
            $this->create_provider(
                $calls,
                false
            );

        $orchestrator =
            new CommercePaymentOrchestrator(
                new CommercePaymentProviderRegistry([
                    $provider,
                ])
            );

        $this->expectException(
            CommercePaymentValidationException::class
        );

        try {
            $orchestrator->initialize(
                $request,
                new CommercePaymentProviderContext(
                    'test:payment:invalid',
                    false
                )
            );
        } finally {
            $this->assertSame(
                0,
                $calls
            );
        }
    }

    public function test_inconsistent_provider_result_is_rejected():
        void {
        $request =
            $this->create_request();

        $provider =
            new class implements CommercePaymentProvider {

                public function get_key(): string {
                    return 'fake';
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
                        ['EUR'],
                        true,
                        false,
                        false,
                        false,
                        true
                    );
                }

                public function supports(
                    CommercePaymentRequest $request
                ): bool {
                    return true;
                }

                public function validate(
                    CommercePaymentRequest $request
                ): CommercePaymentProviderValidationResult {
                    return CommercePaymentProviderValidationResult::valid();
                }

                public function initialize(
                    CommercePaymentRequest $request,
                    CommercePaymentProviderContext $context
                ): CommercePaymentResult {
                    return CommercePaymentResult::requires_action(
                        'another-reference',
                        'fake',
                        'provider-id',
                        CommercePaymentAction::redirect(
                            'https://payment.example.test'
                        )
                    );
                }

                public function retrieve(
                    string $providerpaymentid,
                    CommercePaymentProviderContext $context
                ): CommercePaymentResult {
                    throw new \coding_exception(
                        'Not used.'
                    );
                }

                public function cancel(
                    string $providerpaymentid,
                    CommercePaymentProviderContext $context
                ): CommercePaymentResult {
                    throw new \coding_exception(
                        'Not used.'
                    );
                }
            };

        $orchestrator =
            new CommercePaymentOrchestrator(
                new CommercePaymentProviderRegistry([
                    $provider,
                ])
            );

        $this->expectException(
            CommercePaymentOrchestrationException::class
        );

        $orchestrator->initialize(
            $request,
            new CommercePaymentProviderContext(
                'test:payment:mismatch',
                false
            )
        );
    }

    private function create_request():
        CommercePaymentRequest {
        return new CommercePaymentRequest(
            'payment:test:1',
            new CommercePaymentCustomer(
                96,
                'student@example.com',
                'Test',
                'Student'
            ),
            [
                new CommercePaymentLine(
                    'payment:test:line',
                    'Test product',
                    1,
                    12000,
                    'EUR'
                ),
            ],
            'EUR',
            12000,
            'fake',
            'https://example.test/success',
            'https://example.test/cancel'
        );
    }

    private function create_provider(
        int &$calls,
        bool $valid = true
    ): CommercePaymentProvider {
        return new class(
            $calls,
            $valid
        ) implements CommercePaymentProvider {

            public function __construct(
                private int &$calls,
                private readonly bool $valid
            ) {
            }

            public function get_key(): string {
                return 'fake';
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
                    ['EUR'],
                    true,
                    false,
                    false,
                    false,
                    true
                );
            }

            public function supports(
                CommercePaymentRequest $request
            ): bool {
                return true;
            }

            public function validate(
                CommercePaymentRequest $request
            ): CommercePaymentProviderValidationResult {
                $validation =
                    CommercePaymentProviderValidationResult::valid();

                if (!$this->valid) {
                    $validation->add_error(
                        'fake_invalid',
                        'The fake provider rejected the request.'
                    );
                }

                return $validation;
            }

            public function initialize(
                CommercePaymentRequest $request,
                CommercePaymentProviderContext $context
            ): CommercePaymentResult {
                $this->calls++;

                return CommercePaymentResult::requires_action(
                    $request->get_reference(),
                    'fake',
                    'fake-payment-id',
                    CommercePaymentAction::redirect(
                        'https://payment.example.test'
                    )
                );
            }

            public function retrieve(
                string $providerpaymentid,
                CommercePaymentProviderContext $context
            ): CommercePaymentResult {
                throw new \coding_exception(
                    'Not used.'
                );
            }

            public function cancel(
                string $providerpaymentid,
                CommercePaymentProviderContext $context
            ): CommercePaymentResult {
                throw new \coding_exception(
                    'Not used.'
                );
            }
        };
    }
}