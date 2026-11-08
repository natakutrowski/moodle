<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\showroom\bridge\CommerceShowroomDevCheckoutTestGate as Gate;

require_once(__DIR__ . '/../../../classes/commerce/showroom/bridge/CommerceShowroomDevCheckoutTestGate.php');

/** A published #8 pilot must be isolated from both PROD and standard showrooms. */
final class commerce_798x413_public_pilot_test extends advanced_testcase {
    public function test_published_pilot_allowlist_is_exact(): void {
        $allowed = [8, 'published', Gate::KEY, Gate::TEMPLATE,
            Gate::DEV_ROOT, Gate::DESIGN, Gate::DESIGN_VERSION, Gate::PUBLIC_PILOT_SLUGS];
        self::assertTrue(Gate::eligible_published_pilot(...$allowed));
        self::assertSame([
            'slugfr'=>'articles-francais',
            'slugen'=>'french-articles',
            'slugru'=>'francuzskie-artikli',
        ], Gate::PUBLIC_PILOT_SLUGS);
        foreach ([
            [0, 6], [1, 'draft'], [2, 'articlesalfx0'], [3, 'local_subscriptions/showroom/default'],
            [4, 'https://campusfr.fr'], [5, 'articles_alf'], [6, '0.4.10'],
            [7, ['slugfr'=>'articles-francais-pilote-dev']],
        ] as [$index, $wrong]) {
            $actual = $allowed;
            $actual[$index] = $wrong;
            self::assertFalse(Gate::eligible_published_pilot(...$actual));
        }
        // The old admin-only draft preview remains explicitly distinct.
        self::assertFalse(Gate::eligible(8, 'published', Gate::KEY, Gate::TEMPLATE,
            Gate::DEV_ROOT, Gate::DESIGN, Gate::DESIGN_VERSION));
    }

    public function test_guest_public_route_requires_sandbox_and_never_overrides_legal_checkout(): void {
        $root = dirname(__DIR__, 3);
        $showroom = file_get_contents($root . '/showroom.php');
        $presenter = file_get_contents($root . '/classes/commerce/showroom/bridge/CommerceShowroomBridgePresenter.php');
        self::assertStringNotContainsString('require_login();', $showroom);
        self::assertStringContainsString('subscription_config::guard_public_access();', $showroom);
        self::assertStringContainsString('Cache-Control: private, no-store', $showroom);
        self::assertStringContainsString('public static function require_sandbox()',
            file_get_contents($root . '/classes/commerce/showroom/bridge/CommerceShowroomDevCheckoutTestGate.php'));
        self::assertStringContainsString('CommerceShowroomDevCheckoutTestGate::require_sandbox();', $showroom);
        self::assertStringContainsString('noindex,nofollow,noarchive', $showroom);
        self::assertStringContainsString("'isdevpublishedpilot' => \$publicpilot", $presenter);
        self::assertStringContainsString('CommerceShowroomDevCheckoutTestGate::require_sandbox();', $presenter);
        self::assertStringNotContainsString('dev_checkout_shadow_post.js', $showroom);
        self::assertStringNotContainsString('payment_merchant_override', $showroom);
    }
}
