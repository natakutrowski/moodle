<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\xp\CommercePedagogicalPointsPresenter;

/** Read-only display of promotion points and their CampusFR icon. */
final class commerce_798_promotion_points_display_test extends \advanced_testcase {
    public function test_numeric_score_renders_custom_icon_and_accessible_unit(): void {
        $this->resetAfterTest();
        $html = CommercePedagogicalPointsPresenter::promotion(256);
        $this->assertStringContainsString('256', $html);
        $this->assertStringContainsString('pain_au_chocolat.png', $html);
        $this->assertStringContainsString('campusfr-points-choco-icon', $html);
        $this->assertStringContainsString('alt=', $html);
        $this->assertStringNotContainsString('256 XP', $html);
    }

    public function test_signed_score_and_zero_keep_the_unit(): void {
        $this->resetAfterTest();
        $this->assertStringContainsString('+15', CommercePedagogicalPointsPresenter::promotion(15, true));
        $this->assertStringContainsString('pain_au_chocolat.png',
            CommercePedagogicalPointsPresenter::promotion(0));
    }
}
