<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentContext;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentResult;
use local_subscriptions\commerce\fulfillment\native\batch\CommerceNativeFulfillmentBatchResult;
use local_subscriptions\commerce\fulfillment\native\postaction\CommerceNativePostFulfillmentAction;
use local_subscriptions\commerce\fulfillment\native\postaction\CommerceNativePostFulfillmentActionResult;
use local_subscriptions\commerce\fulfillment\native\postaction\CommerceNativePostFulfillmentCoordinator;

final class commerce_native_fulfillment_orchestration_test extends advanced_testcase {
    public function test_batch_counts_results(): void {
        $grant = $this->grant('g1');
        $context = CommerceNativeFulfillmentContext::dry_run('exec-1', time());
        $batch = new CommerceNativeFulfillmentBatchResult('purchase-1', $context, [
            CommerceNativeFulfillmentResult::completed($grant),
            CommerceNativeFulfillmentResult::skipped($grant, 'already done'),
            CommerceNativeFulfillmentResult::failed($grant, 'failed'),
        ]);
        $this->assertSame(3, $batch->count());
        $this->assertSame(1, $batch->completed_count());
        $this->assertSame(1, $batch->skipped_count());
        $this->assertSame(1, $batch->failed_count());
        $this->assertFalse($batch->is_successful());
    }

    public function test_post_actions_only_run_for_completed_results(): void {
        $grant = $this->grant('g2');
        $context = CommerceNativeFulfillmentContext::dry_run('exec-2', time());
        $action = new class implements CommerceNativePostFulfillmentAction {
            public int $calls = 0;
            public function get_key(): string { return 'test'; }
            public function supports(CommerceNativeFulfillmentResult $result): bool { return true; }
            public function execute(
                CommerceNativeFulfillmentResult $result,
                CommerceNativeFulfillmentContext $context
            ): CommerceNativePostFulfillmentActionResult {
                $this->calls++;
                return new CommerceNativePostFulfillmentActionResult(
                    'test',
                    CommerceNativePostFulfillmentActionResult::STATUS_COMPLETED
                );
            }
        };
        $batch = new CommerceNativeFulfillmentBatchResult('purchase-2', $context, [
            CommerceNativeFulfillmentResult::completed($grant),
            CommerceNativeFulfillmentResult::skipped($grant, 'skip'),
            CommerceNativeFulfillmentResult::failed($grant, 'fail'),
        ]);
        $report = (new CommerceNativePostFulfillmentCoordinator([$action]))->execute($batch);
        $this->assertSame(1, $action->calls);
        $this->assertCount(1, $report->get_results());
        $this->assertFalse($report->has_failures());
    }

    public function test_post_action_failure_does_not_change_fulfillment(): void {
        $grant = $this->grant('g3');
        $context = CommerceNativeFulfillmentContext::dry_run('exec-3', time());
        $action = new class implements CommerceNativePostFulfillmentAction {
            public function get_key(): string { return 'throwing'; }
            public function supports(CommerceNativeFulfillmentResult $result): bool { return true; }
            public function execute(
                CommerceNativeFulfillmentResult $result,
                CommerceNativeFulfillmentContext $context
            ): CommerceNativePostFulfillmentActionResult {
                throw new \RuntimeException('notification failed');
            }
        };
        $batch = new CommerceNativeFulfillmentBatchResult(
            'purchase-3',
            $context,
            [CommerceNativeFulfillmentResult::completed($grant)]
        );
        $report = (new CommerceNativePostFulfillmentCoordinator([$action]))->execute($batch);
        $this->assertTrue($batch->is_successful());
        $this->assertTrue($report->has_failures());
    }

    private function grant(string $reference): CommerceEntitlementGrant {
        return new CommerceEntitlementGrant(
            $reference,
            'purchase-1',
            'item-1',
            'COURSE.A1',
            'course_access',
            'course:13:full',
            1,
            2,
            'student@example.com',
            time()
        );
    }
}
