<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** Public rendering coverage for uploaded Showroom videos. */
final class commerce_showroom_video_public_render_j15e5_test extends \advanced_testcase {
    public function test_public_template_renders_uploaded_video_source(): void {
        $template = file_get_contents(__DIR__ . '/../../../templates/showroom/third_group_verbs.mustache');

        self::assertIsString($template);
        self::assertStringContainsString('{{#hasvideourl}}', $template);
        self::assertStringContainsString('<source src="{{videourl}}">', $template);
        self::assertStringContainsString('commerce-showroom-video-dialog__player', $template);
        self::assertStringContainsString('{{videoplaylabel}}', $template);
    }
}
