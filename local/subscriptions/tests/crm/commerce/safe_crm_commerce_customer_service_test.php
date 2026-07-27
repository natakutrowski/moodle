<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\crm\commerce\CrmCommerceCustomerService;
use local_subscriptions\crm\commerce\CrmCommerceCustomerSnapshot;
use local_subscriptions\crm\commerce\CrmCommerceSnapshotSource;
use local_subscriptions\crm\commerce\LegacyCrmCommerceCustomerService;
use local_subscriptions\crm\commerce\SafeCrmCommerceCustomerService;

/**
 * Tests for the safe CRM Commerce bridge.
 *
 * @covers \local_subscriptions\crm\commerce\SafeCrmCommerceCustomerService
 * @covers \local_subscriptions\crm\commerce\CrmCommerceSnapshotSource
 */
final class safe_crm_commerce_customer_service_test
    extends advanced_testcase {

    public function test_commerce_snapshot_is_used_when_available(): void {
        $snapshot = new CrmCommerceCustomerSnapshot(
            96,
            [],
            2,
            1,
            [
                'EUR' => 13900,
            ],
            [
                'stripe' => 3,
            ],
            [
                'completed' => 3,
            ],
            1700000000,
            1700000100
        );

        $commerceservice = $this->getMockBuilder(
            CrmCommerceCustomerService::class
        )
            ->disableOriginalConstructor()
            ->onlyMethods([
                'build_snapshot',
            ])
            ->getMock();

        $commerceservice
            ->expects($this->once())
            ->method('build_snapshot')
            ->with(
                96,
                'student@example.com'
            )
            ->willReturn($snapshot);

        $legacyservice = $this->getMockBuilder(
            LegacyCrmCommerceCustomerService::class
        )
            ->onlyMethods([
                'build_snapshot',
            ])
            ->getMock();

        $legacyservice
            ->expects($this->never())
            ->method('build_snapshot');

        $service =
            new SafeCrmCommerceCustomerService(
                $commerceservice,
                $legacyservice
            );

        $result = $service->build_snapshot(
            96,
            'student@example.com'
        );

        $this->assertSame(
            $snapshot,
            $result
        );

        $this->assertFalse(
            $result->uses_legacy_fallback()
        );
    }

    public function test_legacy_fallback_is_used_after_exception(): void {
        $fallbacksnapshot =
            new CrmCommerceCustomerSnapshot(
                96,
                [],
                2,
                1,
                [
                    'EUR' => 13900,
                ],
                [
                    'stripe' => 3,
                ],
                [
                    'completed' => 3,
                ],
                1700000000,
                1700000100,
                CrmCommerceSnapshotSource::LEGACY_FALLBACK
            );

        $commerceservice = $this->getMockBuilder(
            CrmCommerceCustomerService::class
        )
            ->disableOriginalConstructor()
            ->onlyMethods([
                'build_snapshot',
            ])
            ->getMock();

        $commerceservice
            ->expects($this->once())
            ->method('build_snapshot')
            ->willThrowException(
                new \RuntimeException(
                    'Simulated Commerce failure'
                )
            );

        $legacyservice = $this->getMockBuilder(
            LegacyCrmCommerceCustomerService::class
        )
            ->onlyMethods([
                'build_snapshot',
            ])
            ->getMock();

        $legacyservice
            ->expects($this->once())
            ->method('build_snapshot')
            ->with(
                96,
                'student@example.com'
            )
            ->willReturn(
                $fallbacksnapshot
            );

        $service =
            new SafeCrmCommerceCustomerService(
                $commerceservice,
                $legacyservice
            );

        $result = $service->build_snapshot(
            96,
            'student@example.com'
        );

        $this->assertDebuggingCalled(
            '[Commerce safe fallback] Commerce failed for user 96: RuntimeException: Simulated Commerce failure'
        );

        $this->assertSame(
            $fallbacksnapshot,
            $result
        );

        $this->assertTrue(
            $result->uses_legacy_fallback()
        );
    }
}