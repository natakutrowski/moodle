<?php
// This file is part of Moodle - https://moodle.org/

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

/**
 * Architecture guards for the targeted repair wheel.
 *
 * @coversNothing
 */
final class commerce_targeted_native_repair_service_test extends advanced_testcase {
    public function test_cli_is_single_target_and_requires_confirmation(): void {
        $path = __DIR__ . '/../../../cli/commerce/migration/repair_native_purchase.php';
        $content = file_get_contents($path);
        $this->assertIsString($content);
        $this->assertStringContainsString("'legacy-id' => 0", $content);
        $this->assertStringContainsString("'confirm-targeted-repair' => ''", $content);
        $this->assertStringNotContainsString("'all'", $content);
    }

    public function test_service_refuses_identity_updates_and_limits_mutable_fields(): void {
        $path = __DIR__ . '/../../../classes/commerce/migration/CommerceTargetedNativeRepairService.php';
        $content = file_get_contents($path);
        $this->assertIsString($content);
        $this->assertStringContainsString('private const IDENTITY_FIELDS', $content);
        $this->assertStringContainsString('private const MUTABLE_FIELDS', $content);
        $this->assertStringContainsString('Expected exactly one Native purchase', $content);
        $this->assertStringContainsString('unsupported field differs', $content);
    }

    public function test_cli_verifies_before_commit_and_has_rollback_path(): void {
        $path = __DIR__ . '/../../../cli/commerce/migration/repair_native_purchase.php';
        $content = file_get_contents($path);
        $this->assertIsString($content);
        $verifypos = strpos($content, '$service->inspect($family, $legacyid)');
        $commitpos = strpos($content, '$transaction->allow_commit()');
        $this->assertNotFalse($verifypos);
        $this->assertNotFalse($commitpos);
        $this->assertLessThan($commitpos, strrpos($content, '$service->inspect($family, $legacyid)'));
        $this->assertStringContainsString('$transaction->rollback', $content);
    }
}
