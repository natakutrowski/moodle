<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentContext;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentExecutor;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentHandler;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentHandlerRegistry;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentResult;

/**
 * @covers \local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentContext
 * @covers \local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentExecutor
 * @covers \local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentHandlerRegistry
 * @covers \local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentResult
 */
final class commerce_native_fulfillment_kernel_test extends advanced_testcase {
    public function test_registered_native_handler_completes_grant(): void {
        $executor = new CommerceNativeFulfillmentExecutor(
            new CommerceNativeFulfillmentHandlerRegistry([$this->handler()])
        );

        $result = $executor->execute(
            $this->grant(),
            CommerceNativeFulfillmentContext::runtime('phpunit-runtime', time(), 2, 'phpunit')
        );

        $this->assertTrue($result->is_completed());
        $this->assertSame('course:13:full', $result->get_payload()['resourcekey']);
    }

    public function test_dry_run_is_explicitly_skipped(): void {
        $executor = new CommerceNativeFulfillmentExecutor(
            new CommerceNativeFulfillmentHandlerRegistry([$this->handler()])
        );

        $result = $executor->execute(
            $this->grant(),
            CommerceNativeFulfillmentContext::dry_run('phpunit-dryrun', time(), null, 'phpunit')
        );

        $this->assertTrue($result->is_skipped());
        $this->assertTrue($result->is_successful());
        $this->assertTrue($result->get_payload()['dryrun']);
    }

    public function test_missing_handler_returns_failed_result(): void {
        $executor = new CommerceNativeFulfillmentExecutor(
            new CommerceNativeFulfillmentHandlerRegistry()
        );

        $result = $executor->execute(
            $this->grant(),
            CommerceNativeFulfillmentContext::runtime('phpunit-missing', time(), null, 'phpunit')
        );

        $this->assertTrue($result->is_failed());
        $this->assertStringContainsString('No Native Commerce fulfillment handler', (string) $result->get_message());
    }

    public function test_duplicate_handler_registration_is_rejected(): void {
        $handler = $this->handler();
        $registry = new CommerceNativeFulfillmentHandlerRegistry([$handler]);

        $this->expectException(\coding_exception::class);
        $registry->register($handler);
    }

    public function test_handler_exception_is_normalized_as_failed_result(): void {
        $handler = new class implements CommerceNativeFulfillmentHandler {
            public function get_grant_type(): string {
                return 'course_access';
            }

            public function fulfill(
                CommerceEntitlementGrant $grant,
                CommerceNativeFulfillmentContext $context
            ): CommerceNativeFulfillmentResult {
                throw new \RuntimeException('Native handler failure.');
            }
        };

        $executor = new CommerceNativeFulfillmentExecutor(
            new CommerceNativeFulfillmentHandlerRegistry([$handler])
        );
        $result = $executor->execute(
            $this->grant(),
            CommerceNativeFulfillmentContext::runtime('phpunit-failure', time(), null, 'phpunit')
        );

        $this->assertTrue($result->is_failed());
        $this->assertSame(\RuntimeException::class, $result->get_error_class());
        $this->assertSame('Native handler failure.', $result->get_message());
    }

    private function handler(): CommerceNativeFulfillmentHandler {
        return new class implements CommerceNativeFulfillmentHandler {
            public function get_grant_type(): string {
                return 'course_access';
            }

            public function fulfill(
                CommerceEntitlementGrant $grant,
                CommerceNativeFulfillmentContext $context
            ): CommerceNativeFulfillmentResult {
                if ($context->is_dry_run()) {
                    return CommerceNativeFulfillmentResult::skipped(
                        $grant,
                        'Dry-run: no Moodle mutation executed.',
                        ['dryrun' => true]
                    );
                }

                return CommerceNativeFulfillmentResult::completed(
                    $grant,
                    ['resourcekey' => $grant->get_resource_key()]
                );
            }
        };
    }

    private function grant(): CommerceEntitlementGrant {
        return new CommerceEntitlementGrant(
            'grant-f1',
            'purchase-f1',
            'item-f1',
            'COURSE.A1.FULL',
            'course_access',
            'course:13:full',
            1,
            2,
            'student@example.com',
            time()
        );
    }
}
