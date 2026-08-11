<?php
declare(strict_types=1);
namespace local_subscriptions;
defined('MOODLE_INTERNAL') || die();
final class commerce_showroom_comparison_mobile_j16d2_test extends \advanced_testcase {
    public function test_mobile_comparison_contract(): void {
        global $CFG;
        $t=file_get_contents($CFG->dirroot.'/local/subscriptions/templates/showroom/third_group_verbs.mustache');
        $c=file_get_contents($CFG->dirroot.'/local/subscriptions/styles/showroom.css');
        self::assertStringContainsString('data-showroom-comparison-mobile',$t);
        self::assertStringContainsString('data-comparison-rail',$t);
        self::assertStringContainsString('grid-template-columns:42% 58%',$c);
        self::assertStringContainsString('scroll-snap-type:x mandatory',$c);
    }
}
