<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\showroom\bridge\CommerceShowroomDevCheckoutTestGate as Gate;

// Explicit require until Moodle refreshes its class discovery cache.
require_once(__DIR__ . '/../../../classes/commerce/showroom/bridge/CommerceShowroomDevCheckoutTestGate.php');

/** Administrative DEV checkout gate: no public publication, no live PSP. */
final class commerce_798x411_dev_checkout_pilot_test extends advanced_testcase {
    public function test_opt_in_gate_is_narrowly_scoped(): void {
        $ok = [8, 'draft', 'articlesalfbridgepilot',
            'local_subscriptions/showroom/bridge', 'https://moodle-v2.campusfr.fr',
            'articles_alf_reimport', Gate::DESIGN_VERSION];
        self::assertTrue(Gate::eligible(...$ok));
        foreach ([
            [0, 7], [1, 'published'], [2, 'articles_alf'], [3, 'local_subscriptions/showroom/default'],
            [4, 'https://campusfr.fr'], [5, 'articles_alf'], [6, '0.4.9'],
        ] as [$index, $wrong]) {
            $args = $ok;
            $args[$index] = $wrong;
            self::assertFalse(Gate::eligible(...$args));
        }
    }

    public function test_preview_unlock_exists_only_with_explicit_admin_permission(): void {
        $root = dirname(__DIR__, 3);
        $preview = (string)file_get_contents($root.'/admin/commerce/showrooms/preview.php');
        $showroom = (string)file_get_contents($root.'/showroom.php');
        $presenter = (string)file_get_contents(
            $root.'/classes/commerce/showroom/bridge/CommerceShowroomBridgePresenter.php');
        self::assertStringContainsString("optional_param('commerce_test', 0, PARAM_INT)", $preview);
        self::assertStringContainsString("require_capability('moodle/site:config'", $preview);
        self::assertStringContainsString("'checkouttest' => \$commercetest === 1", $preview);
        self::assertStringContainsString('CommerceShowroomDevCheckoutTestGate::require_sandbox()', $preview);
        self::assertStringContainsString("'commerce_test'", $preview);
        self::assertStringContainsString('$checkouttest = $isadminpreview &&', $showroom);
        self::assertStringContainsString("'ispreview' => \$previewid !== null && !\$checkouttest", $presenter);
        self::assertStringContainsString('CommerceShowroomDevCheckoutTestGate::require_sandbox()', $presenter);
    }

    public function test_black_glass_footer_below_scene(): void {
        $root = dirname(__DIR__, 3);
        $css = (string)file_get_contents($root.'/styles/showroom_bridge_legal_footer.css');
        self::assertStringContainsString('background-color: #000;', $css);
        self::assertStringContainsString('backdrop-filter: blur(12px)', $css);
        self::assertStringContainsString('flex-direction: column;', $css);
        self::assertStringNotContainsString('position: absolute;', $css);
    }
}
