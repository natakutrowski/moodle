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


    public function test_cmname_exact_and_contains_rules_use_real_names(): void {
        $rules = [
            (object)['type' => 'cm_completion', 'points' => 3, 'filter' => 'cmname',
                'filterint1' => 0, 'filterchar1' => 'Jour 1 · Grammaire'],
            (object)['type' => 'cm_completion', 'points' => 9, 'filter' => 'cmname',
                'filterint1' => 1, 'filterchar1' => 'АУДИРОВАНИЕ'],
        ];
        $this->assertSame(3, Badges::nominal_points($rules, 7, 'jour 1 · grammaire'));
        $this->assertNull(Badges::nominal_points($rules, 7, 'jour 1 · grammaire extra'));
        $this->assertSame(9, Badges::nominal_points($rules, 8, 'День 1 · Аудирование'));
        $this->assertNull(Badges::nominal_points($rules, 8, 'День 1 · Грамматика'));
        $this->assertNull(Badges::nominal_points($rules, 8));
    }

    public function test_cmname_priority_ambiguity_and_missing_filter_are_safe(): void {
        $name = 'Jour 1 · Texte et exercices';
        $rules = [
            (object)['type' => 'cm_completion', 'points' => 2, 'filter' => 'anycm'],
            (object)['type' => 'cm_completion', 'points' => 9, 'filter' => 'cmname',
                'filterint1' => 1, 'filterchar1' => 'Texte'],
        ];
        $this->assertSame(9, Badges::nominal_points($rules, 70, $name));
        $rules[] = (object)['type' => 'cm_completion', 'points' => 12, 'filter' => 'cm',
            'filtercmid' => 70];
        $this->assertSame(12, Badges::nominal_points($rules, 70, $name));
        $this->assertSame(9, Badges::nominal_points($rules, 71, $name));
        $rules[] = (object)['type' => 'cm_completion', 'points' => 7, 'filter' => 'cmname',
            'filterint1' => 1, 'filterchar1' => 'exercices'];
        $this->assertNull(Badges::nominal_points($rules, 71, $name));
        $this->assertSame(12, Badges::nominal_points($rules, 70, $name));
        $this->assertSame(2, Badges::nominal_points($rules, 71, 'Activité sans mots clés'));
        $this->assertNull(Badges::nominal_points([
            (object)['type' => 'cm_completion', 'points' => 50, 'filter' => 'cmname',
                'filterint1' => 1, 'filterchar1' => ''],
        ], 70, $name));
        $this->assertNull(Badges::nominal_points([
            (object)['type' => 'cm_completion', 'points' => 50, 'filter' => 'cmname',
                'filterchar1' => 'Texte'],
        ], 70, $name));
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
