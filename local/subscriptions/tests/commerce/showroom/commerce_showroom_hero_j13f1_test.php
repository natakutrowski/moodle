<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** Structural certification for the premium J13F1 Showroom Hero. */
final class commerce_showroom_hero_j13f1_test extends \advanced_testcase {
    public function test_hero_contains_expediton_stats_devices_and_video_dialog(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/showroom/third_group_verbs.mustache'
        );

        self::assertIsString($template);
        self::assertStringContainsString('commerce-showroom-hero__expedition', $template);
        self::assertStringContainsString('commerce-showroom-hero__stats', $template);
        self::assertStringContainsString('commerce-showroom-device--desktop', $template);
        self::assertStringContainsString('commerce-showroom-device--mobile', $template);
        self::assertStringContainsString('data-showroom-video-open', $template);
        self::assertStringContainsString('data-showroom-video-dialog', $template);
        self::assertStringContainsString('{{heroactionurl}}', $template);
    }

    public function test_presenter_personalises_the_primary_action(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/showroom/'
                . 'CommerceShowroomPresenter.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString('private function hero_action(array $offers): array', $source);
        self::assertStringContainsString("'owned-bundle'", $source);
        self::assertStringContainsString("'owned-pdf'", $source);
        self::assertStringContainsString("'owned-course'", $source);
        self::assertStringContainsString("'prospect'", $source);
    }

    public function test_hero_javascript_uses_native_dialog_lifecycle(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/showroom.js'
        );

        self::assertIsString($source);
        self::assertStringContainsString('bindVideoDialog', $source);
        self::assertStringContainsString('dialog.showModal()', $source);
        self::assertStringContainsString('dialog.close()', $source);
    }
}
