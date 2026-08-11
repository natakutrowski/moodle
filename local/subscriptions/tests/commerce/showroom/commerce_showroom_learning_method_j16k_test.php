<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_learning_method_j16k_test extends \advanced_testcase {
    public function test_learning_method_premium_contract(): void {
        global $CFG;

        $root = $CFG->dirroot . '/local/subscriptions/';
        $registry = file_get_contents(
            $root . 'classes/commerce/showroom/cms/CommerceShowroomBlockEditorRegistry.php'
        );
        $presenter = file_get_contents(
            $root . 'classes/commerce/showroom/cms/CommerceShowroomBlockConfigurationPresenter.php'
        );
        $defaults = file_get_contents(
            $root . 'classes/commerce/showroom/cms/CommerceShowroomBlockDefaultsCatalog.php'
        );
        $template = file_get_contents(
            $root . 'templates/showroom/third_group_verbs.mustache'
        );
        $css = file_get_contents($root . 'styles/showroom.css');

        self::assertStringContainsString("self::text('eyebrow', 'Badge')", $registry);
        self::assertStringContainsString("self::textarea('stage1items'", $registry);
        self::assertStringContainsString('methodsummaryitems', $presenter);
        self::assertStringContainsString('ПОЧЕМУ РАБОТАЕТ ТРЕНАЖЁР?', $defaults);
        self::assertStringContainsString('commerce-showroom-driving-method__wheel', $template);
        self::assertStringContainsString('commerce-showroom-driving-method__route', $template);
        self::assertStringContainsString('commerce-showroom-driving-method__summary', $template);
        self::assertStringContainsString('.commerce-showroom-driving-method__thought:nth-child(5)', $css);
    }
}
