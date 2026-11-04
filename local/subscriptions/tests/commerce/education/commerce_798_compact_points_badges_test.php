<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\education\xp\CommercePedagogicalCompactPointsBadgeService as Badges;

/** @covers \local_subscriptions\commerce\education\xp\CommercePedagogicalCompactPointsBadgeService */
final class commerce_798_compact_points_badges_test extends \advanced_testcase {
    public function test_exact_direct_rule_has_priority_over_fallback(): void {
        $rules = [
            (object)['type' => 'cm_completion', 'points' => 10, 'filter' => 'anycm', 'filtercmid' => null],
            (object)['type' => 'cm_completion', 'points' => 12, 'filter' => 'cm', 'filtercmid' => 71],
        ];
        $this->assertSame(12, Badges::nominal_points($rules, 71));
        $this->assertSame(10, Badges::nominal_points($rules, 72));
    }

    public function test_never_guess_points_for_unsupported_or_ambiguous_rules(): void {
        $this->assertNull(Badges::nominal_points([], 71));
        $this->assertNull(Badges::nominal_points([
            (object)['type' => 'cm_completion', 'points' => 15, 'filter' => 'cmname', 'filtercmid' => null],
        ], 71));
        $this->assertNull(Badges::nominal_points([
            (object)['type' => 'cm_completion', 'points' => 10, 'filter' => 'anycm', 'filtercmid' => null],
            (object)['type' => 'cm_completion', 'points' => 20, 'filter' => 'anycm', 'filtercmid' => null],
        ], 71));
    }

    public function test_badge_states_and_kind_stay_distinct(): void {
        $personal = Badges::badge('personal', 12, 'earned', 0);
        $this->assertSame('personal', $personal['kind']);
        $this->assertSame(12, $personal['points']);
        $this->assertSame('earned', $personal['state']);
        $promo = Badges::badge('promotion', 12, 'locked', time() + 86400);
        $this->assertSame('promotion', $promo['kind']);
        $this->assertSame('locked', $promo['state']);
        $this->assertStringContainsString('pain_au_chocolat.png', $promo['iconurl']);
    }

    public function test_invalid_or_sitewide_request_does_not_return_rewards(): void {
        $service = Badges::create();
        $this->assertSame([], $service->for_user_course(0, 50));
        $this->assertSame([], $service->for_user_course(2, (int)SITEID));
    }
}
