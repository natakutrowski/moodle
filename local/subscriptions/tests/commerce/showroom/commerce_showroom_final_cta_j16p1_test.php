<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_final_cta_j16p1_test extends \advanced_testcase {
    public function test_final_cta_builder_and_public_template_support_background(): void {
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
            "self::media('backgroundurl', 'Image de fond', 'image')",
            $registry
        );
        self::assertStringContainsString("'backgroundopacity'", $registry);
        self::assertStringContainsString("'backgroundblur'", $registry);

        self::assertStringContainsString("hasfinalbackground", $presenter);
        self::assertStringContainsString("finalbackgroundopacity", $presenter);
        self::assertStringContainsString("finalbackgroundblur", $presenter);

        self::assertStringContainsString('commerce-showroom-final__background', $template);
        self::assertStringContainsString('fa-solid fa-circle-question', $template);
        self::assertStringContainsString('commerce-showroom-final__badge', $template);

        self::assertStringContainsString(
            '/* J16P1 — Final CTA editorial refresh + Builder-controlled photo background. */',
            $css
        );
        self::assertStringContainsString('margin: 3.25rem auto 0;', $css);
    }
}
