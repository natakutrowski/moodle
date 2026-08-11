<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_cinematic_j13f6_test extends \advanced_testcase {
    public function test_cinematic_markup_styles_and_behaviour_are_present(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions';
        $template = file_get_contents($root . '/templates/showroom/third_group_verbs.mustache');
        $runtime = file_get_contents($root . '/amd/src/showroom.js');
        self::assertStringContainsString('commerce-showroom-video', $template);
        self::assertStringContainsString('data-showroom-video', $template);
        self::assertStringContainsString('bindInlineVideos', $runtime);

    }
}
