<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_articles_audience_slide7_798c313_test extends \advanced_testcase {
    public function test_slide_seven_audience_styles_remain_available_for_dedicated_block(): void {
        global $CFG;

        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        $registry = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/cms/CommerceShowroomBlockEditorRegistry.php');

        self::assertStringContainsString('.commerce-showroom-content-highlights--audience_profiles', $css);
        self::assertStringContainsString("'audience_profiles' => self::definition([", $registry);
        self::assertStringNotContainsString("'audience_profiles' => 'Audience — 4 profils illustrés'", $registry);
    }
}
