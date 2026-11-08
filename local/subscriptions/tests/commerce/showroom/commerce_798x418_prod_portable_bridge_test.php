<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgePackageRegistry;
use local_subscriptions\commerce\showroom\bridge\CommerceShowroomDevCheckoutTestGate as DevGate;

require_once(__DIR__ . '/../../../classes/commerce/showroom/bridge/CommerceShowroomDevCheckoutTestGate.php');
require_once(__DIR__ . '/../../../classes/commerce/showroom/bridge/CommerceShowroomBridgePackageRegistry.php');

/** 2Q.1A: a trusted Bridge design is portable without relaxing DEV-only checkout test controls. */
final class commerce_798x418_prod_portable_bridge_test extends advanced_testcase {
    public function test_native_legal_footer_uses_compiled_design_not_cms_record_identity(): void {
        $root = dirname(__DIR__, 3);
        $showroom = (string)file_get_contents($root . '/showroom.php');
        self::assertStringContainsString("(string)(\$data['bridgeid'] ?? '') === 'articles_alf_reimport'", $showroom);
        self::assertStringContainsString('if ($isnativelegalbridge) {', $showroom);
        self::assertStringNotContainsString('if ($isnativelegalpilot) {', $showroom);
        self::assertStringContainsString("'fields' => \$finallegalprofilefields", $showroom);
        self::assertStringContainsString('CommerceInvoiceProfileResolver', $showroom);
        self::assertStringContainsString('CommerceShowroomLegalFooterPresenter', $showroom);
        self::assertStringContainsString('CommerceShowroomDevCheckoutTestGate::DEV_ROOT', $showroom);
    }

    public function test_presenter_legal_payload_uses_design_not_dev_id_key(): void {
        $root = dirname(__DIR__, 3);
        $presenter = (string)file_get_contents($root . '/classes/commerce/showroom/bridge/CommerceShowroomBridgePresenter.php');
        self::assertStringContainsString("if (\$id === 'articles_alf_reimport')", $presenter);
        self::assertStringContainsString("'legal' => \$designlegal ?? [", $presenter);
        self::assertStringContainsString("'privacy' => \$designlinks['privacy'] ??", $presenter);
        self::assertStringNotContainsString('(int)$record->id === 8 && $id', $presenter);
        self::assertStringContainsString('CommerceShowroomDevCheckoutTestGate::require_sandbox()', $presenter);
        self::assertStringContainsString('CommerceShowroomDevCheckoutTestGate::DEV_ROOT', $presenter);
    }

    public function test_portable_manifest_is_trusted_and_no_longer_dev_labelled(): void {
        $manifest = CommerceShowroomBridgePackageRegistry::require_manifest('articles_alf_reimport');
        self::assertSame('0.4.11', $manifest['version']);
        self::assertSame('Articles / Alf', $manifest['label']);
        self::assertSame('dist/main.js', $manifest['entry']);
    }

    public function test_administrator_dev_checkout_cannot_unlock_on_prod(): void {
        self::assertFalse(DevGate::eligible(8, 'draft', DevGate::KEY, DevGate::TEMPLATE,
            'https://campusfr.fr', DevGate::DESIGN, DevGate::DESIGN_VERSION));
        self::assertFalse(DevGate::eligible_published_pilot(8, 'published', DevGate::KEY,
            DevGate::TEMPLATE, 'https://campusfr.fr', DevGate::DESIGN,
            DevGate::DESIGN_VERSION, DevGate::PUBLIC_PILOT_SLUGS));
        self::assertTrue(DevGate::eligible_published_pilot(8, 'published', DevGate::KEY,
            DevGate::TEMPLATE, DevGate::DEV_ROOT, DevGate::DESIGN,
            DevGate::DESIGN_VERSION, DevGate::PUBLIC_PILOT_SLUGS));
    }
}
