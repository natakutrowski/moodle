<?php

declare(strict_types=1);
namespace local_subscriptions;

use advanced_testcase;

/** DEV-only visual layout guard: Commerce owns the legal footer, no overlay. */
final class commerce_798x410_footer_below_donation_card_test extends advanced_testcase {
    public function test_native_footer_is_in_document_flow_with_liquid_glass(): void {
        $root = dirname(__DIR__, 3);
        $css = file_get_contents($root . '/styles/showroom_bridge_legal_footer.css');
        self::assertNotFalse($css);
        self::assertStringContainsString('.campusfr-native-legal-footer {', $css);
        self::assertStringContainsString('position: relative;', $css);
        self::assertStringNotContainsString('position: absolute;', $css);
        self::assertStringContainsString('backdrop-filter: blur(12px)', $css);
        self::assertStringContainsString('background-color: #000;', $css);
        $mustache = file_get_contents($root . '/templates/showroom/bridge.mustache');
        self::assertNotFalse($mustache);
        self::assertLessThan(strpos($mustache, '{{> local_subscriptions/showroom/legal_footer}}'),
            strpos($mustache, '</main>'));
    }

    public function test_pilot_footer_uses_existing_invoice_profile_not_client_checkout(): void {
        $root = dirname(__DIR__, 3);
        $showroom = file_get_contents($root . '/showroom.php');
        self::assertStringContainsString('CommerceInvoiceProfileResolver', $showroom);
        self::assertStringContainsString("'fields' => \$finallegalprofilefields", $showroom);
        self::assertStringContainsString('CommerceShowroomLegalFooterPresenter', $showroom);
        self::assertStringNotContainsString('campusfr-layout-v410', $showroom);
    }
}
