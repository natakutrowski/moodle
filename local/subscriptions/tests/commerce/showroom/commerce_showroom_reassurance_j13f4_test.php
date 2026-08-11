<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_reassurance_j13f4_test extends \advanced_testcase {
    public function test_presenter_uses_local_close_string_and_conversion_sections(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions';
        $presenter = file_get_contents($root . '/classes/commerce/showroom/CommerceShowroomPresenter.php');
        $template = file_get_contents($root . '/templates/showroom/third_group_verbs.mustache');
        $javascript = file_get_contents($root . '/amd/src/showroom.js');
        self::assertIsString($presenter);
        self::assertIsString($template);
        self::assertIsString($javascript);
        self::assertStringContainsString("get_string('commerce_showroom_video_close', 'local_subscriptions')", $presenter);
        self::assertStringNotContainsString("get_string('close')", $presenter);
        self::assertStringContainsString('commerce-showroom-why', $template);
        self::assertStringContainsString('commerce-showroom-trust', $template);
        self::assertStringContainsString('commerce-showroom-support', $template);
        self::assertStringContainsString('data-showroom-desktop-sticky', $template);
        self::assertStringContainsString('observeDesktopSticky', $javascript);
        self::assertStringContainsString('UrlFactory::support', $presenter);
    }

    public function test_testimonials_are_not_fabricated(): void {
        global $CFG;
        $presenter = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/CommerceShowroomPresenter.php');
        self::assertIsString($presenter);
        self::assertStringContainsString("'testimonials' => []", $presenter);
        self::assertStringContainsString("'hastestimonials' => false", $presenter);
    }
}
