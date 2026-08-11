<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_ascent_j16m2_test extends \advanced_testcase {
    public function test_multiline_fifth_card_and_responsive_ascent_contract(): void {
        global $CFG;

        $root = $CFG->dirroot . '/local/subscriptions/';
        $presenter = file_get_contents(
            $root . 'classes/commerce/showroom/cms/CommerceShowroomBlockConfigurationPresenter.php'
        );
        $template = file_get_contents(
            $root . 'templates/showroom/third_group_verbs.mustache'
        );
        $css = file_get_contents($root . 'styles/showroom.css');

        self::assertStringContainsString(
            'A continuation line belongs to the previous card description.',
            $presenter
        );
        self::assertStringContainsString(
            'showroom-ascent-route-gradient',
            $template
        );
        self::assertStringContainsString(
            'commerce-showroom-ascent__marker--5',
            $template
        );
        self::assertStringContainsString(
            'stroke: url(#showroom-ascent-route-gradient);',
            $css
        );
        self::assertStringContainsString(
            '.commerce-showroom-ascent__cards::after',
            $css
        );
        self::assertStringContainsString(
            'grid-template-columns: 1fr;',
            $css
        );
    }
}
