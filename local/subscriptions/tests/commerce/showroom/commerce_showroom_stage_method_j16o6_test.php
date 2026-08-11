<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_stage_method_j16o6_test extends \advanced_testcase {
    public function test_journey_current_editorial_contract(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/';
        $registry = file_get_contents(
            $root . 'classes/commerce/showroom/cms/CommerceShowroomBlockEditorRegistry.php'
        );
        $presenter = file_get_contents(
            $root . 'classes/commerce/showroom/cms/CommerceShowroomBlockConfigurationPresenter.php'
        );
        $template = file_get_contents($root . 'templates/showroom/third_group_verbs.mustache');
        $css = file_get_contents($root . 'styles/showroom.css');

        self::assertStringContainsString(
            "self::text('titlehighlight', 'Partie du titre en rose')",
            $registry
        );
        self::assertStringContainsString('journeytitlehighlight', $presenter);
        self::assertStringContainsString('hasjourneytitlehighlight', $presenter);
        self::assertStringContainsString("'isreststop'", $presenter);

        self::assertStringContainsString(
            'commerce-showroom-stage-method__title-highlight',
            $template
        );
        self::assertStringContainsString(
            'commerce-showroom-journey__step--rest',
            $template
        );
        self::assertStringContainsString('fa-solid fa-mug-hot', $template);

        self::assertStringContainsString('object-fit: cover;', $css);
        self::assertStringContainsString('object-position: right center;', $css);
        self::assertStringContainsString('width: 70%;', $css);
    }
}
