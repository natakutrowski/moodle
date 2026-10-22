<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_foundation_j13b_test extends \advanced_testcase {
    public function test_controller_uses_dedicated_presenter_and_generic_render_engine(): void {
        global $CFG;

        $controller = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/showroom.php'
        );
        $legacytemplate = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/templates/showroom/'
                . 'third_group_verbs.mustache'
        );
        $generictemplate = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/templates/showroom/'
                . 'default.mustache'
        );

        self::assertIsString($controller);
        self::assertIsString($legacytemplate);
        self::assertIsString($generictemplate);
        self::assertStringContainsString('CommerceShowroomProductResolver', $controller);
        self::assertStringContainsString('CommerceShowroomPresenter', $controller);
        self::assertStringContainsString("styles/showroom.css", $controller);
        self::assertStringContainsString("local_subscriptions/showroom", $controller);

        // The historical Verbes template is now only a compatibility wrapper.
        self::assertStringContainsString(
            '{{> local_subscriptions/showroom/default }}',
            $legacytemplate
        );

        // The actual public Showroom contract belongs to the generic render engine.
        self::assertStringContainsString('data-showroom="{{showroomkey}}"', $generictemplate);
    }

    public function test_buy_now_form_is_post_and_keeps_attribution(): void {
        global $CFG;
        $action = file_get_contents($CFG->dirroot . '/local/subscriptions/cart_action.php');
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/offer.mustache');
        self::assertStringContainsString('method="post"', $template);
        self::assertStringContainsString('name="source" value="showroom"', $template);
        self::assertStringContainsString(
            'CommerceShowroomTrackingContext::metadata($showroom, $showroomoffer)',
            $action
        );
        self::assertStringContainsString(
            "optional_param('showroom'",
            $action
        );
        self::assertStringContainsString(
            "optional_param('showroomoffer'",
            $action
        );
    }
}
