<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\crm\commerce\CrmCommerceCustomerService;
use local_subscriptions\crm\commerce\CrmCommerceCustomerSnapshot;
use local_subscriptions\crm\commerce\CrmCommerceSnapshotSource;
use local_subscriptions\crm\commerce\LegacyCrmCommerceCustomerService;
use local_subscriptions\crm\commerce\shadow\CrmCommerceShadowService;

/**
 * Tests for CRM Commerce shadow mode.
 *
 * @covers \local_subscriptions\crm\commerce\shadow\CrmCommerceShadowResult
 * @covers \local_subscriptions\crm\commerce\shadow\CrmCommerceShadowService
 */
final class crm_commerce_shadow_service_test
    extends advanced_testcase {

    public function test_equivalent_commerce_snapshot_is_returned(): void {
        $commerce = $this->create_snapshot(
            CrmCommerceSnapshotSource::COMMERCE_DOMAIN
        );

        $legacy = $this->create_snapshot(
            CrmCommerceSnapshotSource::LEGACY_FALLBACK
        );

        $commerceservice = $this->createMock(
            CrmCommerceCustomerService::class
        );

        $commerceservice
            ->method('build_snapshot')
            ->willReturn($commerce);

        $legacyservice = $this->createMock(
            LegacyCrmCommerceCustomerService::class
        );

        $legacyservice
            ->method('build_snapshot')
            ->willReturn($legacy);

        $service = new CrmCommerceShadowService(
            $commerceservice,
            $legacyservice
        );

        $result = $service->execute(
            96,
            'student@example.com'
        );

        $this->assertSame(
            $commerce,
            $result->get_snapshot()
        );

        $this->assertFalse(
            $result->was_fallback_used()
        );

        $this->assertTrue(
            $result->is_equivalent()
        );
    }

    public function test_legacy_snapshot_is_used_when_commerce_fails(): void {
        $legacy = $this->create_snapshot(
            CrmCommerceSnapshotSource::LEGACY_FALLBACK
        );

        $commerceservice = $this->createMock(
            CrmCommerceCustomerService::class
        );

        $commerceservice
            ->method('build_snapshot')
            ->willThrowException(
                new \RuntimeException(
                    'Simulated Commerce failure'
                )
            );

        $legacyservice = $this->createMock(
            LegacyCrmCommerceCustomerService::class
        );

        $legacyservice
            ->method('build_snapshot')
            ->willReturn($legacy);

        $service = new CrmCommerceShadowService(
            $commerceservice,
            $legacyservice
        );

        $result = $service->execute(
            96,
            'student@example.com'
        );

        $this->assertSame(
            $legacy,
            $result->get_snapshot()
        );

        $this->assertTrue(
            $result->was_fallback_used()
        );

        $this->assertNotNull(
            $result->get_commerce_error()
        );
    }

    private function create_snapshot(
        string $source
    ): CrmCommerceCustomerSnapshot {
        return new CrmCommerceCustomerSnapshot(
            96,
            [],
            1,
            1,
            [
                'EUR' => 13900,
            ],
            [
                'stripe' => 2,
            ],
            [
                'active' => 1,
                'completed' => 1,
            ],
            1700000000,
            1700000100,
            $source
        );
    }
}