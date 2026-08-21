<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n116c1_resend_access_policy_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_delivered_fulfillment_is_eligible_for_access_resend(): void {
        $policy = $this->file(
            'classes/commerce/purchase/action/CommercePurchaseActionPolicy.php'
        );

        self::assertStringContainsString(
            "'delivered'",
            $policy
        );
        self::assertStringContainsString(
            '$purchase->commercialstatus === \'fulfilled\'',
            $policy
        );
        self::assertStringContainsString(
            'can_resend_access_summary',
            $policy
        );
    }

    public function test_user360_uses_consolidated_commercial_status_badges(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360CommerceAccessRenderer.php'
        );

        self::assertStringContainsString(
            'CommercePurchasePresentation::',
            $renderer
        );
        self::assertStringContainsString(
            'commercial_status_badge(',
            $renderer
        );
        self::assertStringNotContainsString(
            's(ucfirst($commercialstatus))',
            $renderer
        );
    }

    public function test_user360_exposes_resend_access_endpoint(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360CommerceAccessRenderer.php'
        );

        self::assertStringContainsString(
            "'/local/subscriptions/admin/commerce/purchases/resend_access.php'",
            $renderer
        );
        self::assertStringContainsString(
            'can_resend_access_summary($summary)',
            $renderer
        );
    }

}
