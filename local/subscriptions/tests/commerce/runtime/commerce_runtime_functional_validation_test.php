<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\runtime\switching\CommerceNativeRuntimeExecutor;
use local_subscriptions\commerce\runtime\switching\CommerceRuntimeConfiguration;
use local_subscriptions\commerce\runtime\switching\CommerceRuntimeDispatcher;
use local_subscriptions\commerce\runtime\switching\CommerceRuntimeMode;
use local_subscriptions\commerce\runtime\switching\CommerceRuntimeValidationMatrix;
use local_subscriptions\payment\dto\InternalEvent;

final class commerce_runtime_functional_validation_test extends \advanced_testcase {
    private function event(): InternalEvent {
        return new InternalEvent('checkout_completed', ['payment_request_id' => '123']);
    }

    private function configuration(string $mode, bool $fallback = false): CommerceRuntimeConfiguration {
        $this->resetAfterTest();
        set_config('commerce_runtime_mode', $mode, 'local_subscriptions');
        set_config('commerce_runtime_native_fallback_enabled', $fallback ? 1 : 0, 'local_subscriptions');
        return new CommerceRuntimeConfiguration();
    }

    public function test_legacy_executes_legacy_once_and_never_native(): void {
        $native = $this->createMock(CommerceNativeRuntimeExecutor::class);
        $native->expects($this->never())->method('execute');
        $legacycalls = 0;

        (new CommerceRuntimeDispatcher($this->configuration(CommerceRuntimeMode::LEGACY), $native))
            ->checkout_completed($this->event(), 'test.legacy', static function () use (&$legacycalls): void {
                $legacycalls++;
            });

        $this->assertSame(1, $legacycalls);
    }

    public function test_native_success_never_executes_legacy(): void {
        $native = $this->createMock(CommerceNativeRuntimeExecutor::class);
        $native->expects($this->once())->method('execute');
        $legacycalls = 0;

        (new CommerceRuntimeDispatcher($this->configuration(CommerceRuntimeMode::NATIVE), $native))
            ->checkout_completed($this->event(), 'test.native', static function () use (&$legacycalls): void {
                $legacycalls++;
            });

        $this->assertSame(0, $legacycalls);
    }

    public function test_native_failure_falls_back_exactly_once_when_enabled(): void {
        $native = $this->createMock(CommerceNativeRuntimeExecutor::class);
        $native->method('execute')->willThrowException(new \RuntimeException('Expected Native failure.'));
        $legacycalls = 0;
        (new CommerceRuntimeDispatcher(
            $this->configuration(CommerceRuntimeMode::NATIVE, true),
            $native,
            static function (): void {}
        ))
            ->checkout_completed($this->event(), 'test.fallback', static function () use (&$legacycalls): void {
                $legacycalls++;
            });

        $this->assertSame(1, $legacycalls);
    }


    public function test_native_callback_bypasses_generic_executor(): void {
        $nativeexecutor = $this->createMock(CommerceNativeRuntimeExecutor::class);
        $nativeexecutor->expects($this->never())->method('execute');
        $legacycalls = 0;
        $nativecalls = 0;

        (new CommerceRuntimeDispatcher($this->configuration(CommerceRuntimeMode::NATIVE), $nativeexecutor))
            ->checkout_completed(
                $this->event(),
                'test.native_callback',
                static function () use (&$legacycalls): void {
                    $legacycalls++;
                },
                static function () use (&$nativecalls): void {
                    $nativecalls++;
                }
            );

        $this->assertSame(0, $legacycalls);
        $this->assertSame(1, $nativecalls);
    }

    public function test_native_callback_failure_falls_back_exactly_once(): void {
        $nativeexecutor = $this->createMock(CommerceNativeRuntimeExecutor::class);
        $nativeexecutor->expects($this->never())->method('execute');
        $legacycalls = 0;

        (new CommerceRuntimeDispatcher(
            $this->configuration(CommerceRuntimeMode::NATIVE, true),
            $nativeexecutor,
            static function (): void {}
        ))
            ->checkout_completed(
                $this->event(),
                'test.native_callback_fallback',
                static function () use (&$legacycalls): void {
                    $legacycalls++;
                },
                static function (): void {
                    throw new \RuntimeException('Expected callback failure.');
                }
            );

        $this->assertSame(1, $legacycalls);
    }

    public function test_native_failure_is_propagated_when_fallback_is_disabled(): void {
        $native = $this->createMock(CommerceNativeRuntimeExecutor::class);
        $native->method('execute')->willThrowException(new \RuntimeException('Native is authoritative.'));
        $legacycalls = 0;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Native is authoritative.');

        try {
            (new CommerceRuntimeDispatcher($this->configuration(CommerceRuntimeMode::NATIVE, false), $native))
                ->checkout_completed($this->event(), 'test.strict', static function () use (&$legacycalls): void {
                    $legacycalls++;
                });
        } finally {
            $this->assertSame(0, $legacycalls);
        }
    }

    public function test_invalid_mode_is_safe_legacy(): void {
        $this->assertSame(CommerceRuntimeMode::LEGACY, CommerceRuntimeMode::normalize('broken-mode'));
        $this->assertSame(CommerceRuntimeMode::LEGACY, CommerceRuntimeMode::normalize(''));
    }

    public function test_validation_matrix_covers_all_required_domains(): void {
        $scenarios = CommerceRuntimeValidationMatrix::scenarios();

        $this->assertArrayHasKey('subscription', $scenarios);
        $this->assertArrayHasKey('digital', $scenarios);
        $this->assertArrayHasKey('resilience', $scenarios);
        $this->assertArrayHasKey('runtime', $scenarios);
        $this->assertGreaterThanOrEqual(20, CommerceRuntimeValidationMatrix::count());
        $this->assertContains('duplicate_webhook', $scenarios['resilience']);
        $this->assertContains('rollback_to_legacy', $scenarios['runtime']);
    }
}