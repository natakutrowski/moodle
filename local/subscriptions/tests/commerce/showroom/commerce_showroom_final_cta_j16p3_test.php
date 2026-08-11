<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_final_cta_j16p3_test extends \advanced_testcase {
    public function test_split_sticky_legal_footer_and_mobile_background_contract(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/';
        $template = file_get_contents($root . 'templates/showroom/third_group_verbs.mustache');
        $css = file_get_contents($root . 'styles/showroom.css');
        $js = file_get_contents($root . 'amd/src/showroom.js');
        $page = file_get_contents($root . 'showroom.php');

        self::assertStringContainsString('commerce-showroom-desktop-sticky__expedition-glass', $template);
        self::assertStringContainsString('commerce-showroom-desktop-sticky__inner', $template);
        self::assertStringContainsString('commerce-showroom-final__legal', $template);

        self::assertStringContainsString('--showroom-final-mobile-bg-offset-x: 150px;', $css);
        self::assertStringContainsString(
            '.commerce-showroom-desktop-sticky__expedition-glass[hidden]',
            $css
        );

        self::assertStringContainsString('expedition.hidden = !visible;', $js);
        self::assertStringContainsString('$legalurls = Region::policyUrls();', $page);
        self::assertStringContainsString('CommerceInvoiceProfileResolver', $page);
    }
}
