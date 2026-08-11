<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentContext;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentHandler;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentHandlerRegistry;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentResult;
use local_subscriptions\commerce\fulfillment\native\persistence\CommerceNativeFulfillmentPersistenceRepository;
use local_subscriptions\commerce\fulfillment\native\persistence\CommercePersistentNativeFulfillmentExecutor;

/** @covers \local_subscriptions\commerce\fulfillment\native\persistence\CommercePersistentNativeFulfillmentExecutor */
final class commerce_native_fulfillment_persistence_test extends advanced_testcase {
    public function test_completed_state_prevents_duplicate_runtime_execution(): void {
        $handler = $this->handler();
        $repository = $this->repository((object) [
            'status' => 'completed', 'attempts' => 2, 'lastexecutionreference' => 'old-run',
        ]);
        $executor = new CommercePersistentNativeFulfillmentExecutor(
            new CommerceNativeFulfillmentHandlerRegistry([$handler]),
            $repository
        );

        $result = $executor->execute($this->grant(), CommerceNativeFulfillmentContext::runtime('new-run', time()));

        self::assertTrue($result->is_skipped());
        self::assertTrue($result->get_payload()['idempotent']);
        self::assertSame(0, $repository->begins);
        self::assertSame(1, $repository->activations);
    }

    public function test_attempt_is_started_and_completed(): void {
        $repository = $this->repository(null);
        $executor = new CommercePersistentNativeFulfillmentExecutor(
            new CommerceNativeFulfillmentHandlerRegistry([$this->handler()]),
            $repository
        );

        $result = $executor->execute($this->grant(), CommerceNativeFulfillmentContext::runtime('run-f5', time()));

        self::assertTrue($result->is_completed());
        self::assertSame(1, $repository->begins);
        self::assertSame(1, $repository->completions);
        self::assertSame(1, $repository->activations);
    }

    public function test_failed_handler_is_persisted_as_failed_result(): void {
        $handler = new class implements CommerceNativeFulfillmentHandler {
            public function get_grant_type(): string { return 'digital_download'; }
            public function fulfill(CommerceEntitlementGrant $grant, CommerceNativeFulfillmentContext $context): CommerceNativeFulfillmentResult {
                throw new \RuntimeException('boom');
            }
        };
        $repository = $this->repository(null);
        $executor = new CommercePersistentNativeFulfillmentExecutor(
            new CommerceNativeFulfillmentHandlerRegistry([$handler]),
            $repository
        );

        $result = $executor->execute($this->grant(), CommerceNativeFulfillmentContext::runtime('run-failed', time()));

        self::assertTrue($result->is_failed());
        self::assertSame('boom', $result->get_message());
        self::assertSame('failed', $repository->laststatus);
        self::assertSame(0, $repository->activations);
    }

    private function handler(): CommerceNativeFulfillmentHandler {
        return new class implements CommerceNativeFulfillmentHandler {
            public function get_grant_type(): string { return 'digital_download'; }
            public function fulfill(CommerceEntitlementGrant $grant, CommerceNativeFulfillmentContext $context): CommerceNativeFulfillmentResult {
                return CommerceNativeFulfillmentResult::completed($grant, ['ok' => true]);
            }
        };
    }

    private function repository(?\stdClass $state): CommerceNativeFulfillmentPersistenceRepository {
        return new class($state) implements CommerceNativeFulfillmentPersistenceRepository {
            public int $begins = 0;
            public int $completions = 0;
            public int $activations = 0;
            public ?string $laststatus = null;
            public function __construct(private ?\stdClass $state) {}
            public function find_state(string $grantreference): ?\stdClass { return $this->state; }
            public function activate_grant_if_planned(CommerceEntitlementGrant $grant, ?int $now = null): bool {
                $this->activations++;
                return true;
            }
            public function begin_attempt(CommerceEntitlementGrant $grant, CommerceNativeFulfillmentContext $context, string $handlerclass): int {
                $this->begins++;
                return 17;
            }
            public function complete_attempt(int $attemptid, CommerceEntitlementGrant $grant, CommerceNativeFulfillmentContext $context, string $handlerclass, CommerceNativeFulfillmentResult $result): void {
                $this->completions++;
                $this->laststatus = $result->get_status();
            }
        };
    }

    private function grant(): CommerceEntitlementGrant {
        return new CommerceEntitlementGrant(
            'grant-f5', 'purchase-f5', 'item-f5', 'DIGITAL.VERBES', 'digital_download',
            'digital-product:2', 1, 2, 'student@example.com', time()
        );
    }
}
