<?php

declare(strict_types=1);
namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\showroom\bridge\CommerceShowroomDevCheckoutTestGate as Gate;

require_once(__DIR__ . '/../../../classes/commerce/showroom/bridge/CommerceShowroomDevCheckoutTestGate.php');

/** Regression: guest pilot #8 must use sandbox and the canonical CMS gate without forcing login. */
final class commerce_798x414_public_pilot_render_gate_test extends advanced_testcase {
    public function test_public_controller_sandbox_gate_before_presenter(): void {
        $root = dirname(__DIR__, 3);
        $page = file_get_contents($root . '/showroom.php');
        self::assertIsString($page);
        $sandbox = strpos($page, 'CommerceShowroomDevCheckoutTestGate::require_sandbox();');
        $present = strpos($page, '(new CommerceShowroomBridgePresenter())->present(');
        self::assertNotFalse($sandbox);
        self::assertStringNotContainsString('require_login();', $page);
        self::assertNotFalse($present);
        self::assertLessThan($present, $sandbox);
        self::assertStringContainsString('CommerceShowroomDevCheckoutTestGate::require_sandbox();', $page);
    }

    public function test_presenter_uses_same_cms_fields_as_publication_verifier(): void {
        $root = dirname(__DIR__, 3);
        $php = file_get_contents($root . '/classes/commerce/showroom/bridge/CommerceShowroomBridgePresenter.php');
        self::assertIsString($php);
        self::assertStringContainsString('(string)$record->showroomkey', $php);
        self::assertStringContainsString('(string)$record->template', $php);
        self::assertStringContainsString('\'slugfr\' => (string)$record->slugfr', $php);
        self::assertStringContainsString('CommerceShowroomDevCheckoutTestGate::eligible_published_pilot(', $php);
        self::assertStringNotContainsString('!isloggedin() || isguestuser()', $php);
        self::assertStringContainsString('CommerceShowroomDevCheckoutTestGate::require_sandbox();', $php);
        self::assertStringContainsString("throw new \moodle_exception('commerce_showroom_not_found'", $php);
    }

    public function test_exact_published_pilot_gate_and_wrong_site_rejection(): void {
        $args = [8, 'published', Gate::KEY, Gate::TEMPLATE,
            Gate::DEV_ROOT, Gate::DESIGN, Gate::DESIGN_VERSION, Gate::PUBLIC_PILOT_SLUGS];
        self::assertTrue(Gate::eligible_published_pilot(...$args));
        $args[4] = 'https://campusfr.fr';
        self::assertFalse(Gate::eligible_published_pilot(...$args));
        $args[4] = Gate::DEV_ROOT;
        $args[1] = 'draft';
        self::assertFalse(Gate::eligible_published_pilot(...$args));
    }
}
