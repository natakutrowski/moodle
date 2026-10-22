<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_offers_comparison_j13f3_test extends \advanced_testcase {
    public function test_showroom_exposes_a_reactive_offer_comparison(): void {
        global $CFG;

        $root = $CFG->dirroot . '/local/subscriptions';
        $template = file_get_contents($root . '/templates/showroom/default.mustache');
        $profile = file_get_contents($root . '/classes/commerce/showroom/CommerceShowroomThirdGroupVerbsPresentationProfile.php');
        $javascript = file_get_contents($root . '/amd/src/showroom.js');
        $css = file_get_contents($root . '/styles/showroom.css');

        self::assertIsString($template);
        self::assertIsString($profile);
        self::assertIsString($javascript);
        self::assertIsString($css);
        self::assertStringContainsString('data-showroom-comparison', $template);
        self::assertStringContainsString('comparison_rows()', $profile);
        self::assertStringContainsString('bindOfferComparison', $javascript);
        self::assertStringContainsString('.commerce-showroom-comparison__table', $css);
        self::assertStringContainsString('data-comparison-role="{{role}}"', $template);
        self::assertStringContainsString("'featured' => \$role === 'bundle'", $profile);
    }
}
