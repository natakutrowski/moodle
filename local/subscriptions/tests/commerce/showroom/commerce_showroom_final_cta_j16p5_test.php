<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_final_cta_j16p5_test extends \advanced_testcase {
    public function test_mobile_final_cta_uses_structured_legal_profile_and_inline_action(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/';
        $page = file_get_contents($root . 'showroom.php');
        $template = file_get_contents($root . 'templates/showroom/third_group_verbs.mustache');
        $css = file_get_contents($root . 'styles/showroom.css');

        self::assertStringContainsString(
            '$finallegalprofilefields = [',
            $page
        );
        foreach ([
            "'name' =>",
            "'address' =>",
            "'legal' =>",
            "'email' =>",
            "'phone' =>",
            "'website' =>",
            "'taxnotice' =>",
            "'footer' =>",
        ] as $fieldcontract) {
            self::assertStringContainsString($fieldcontract, $page);
        }
        self::assertStringContainsString(
            "\$data['finallegal' . \$field] = \$value;",
            $page
        );
        self::assertStringContainsString(
            "\$data['hasfinallegal' . \$field] = \$value !== '';",
            $page
        );

        self::assertStringContainsString('commerce-showroom-final__company-name', $template);
        self::assertStringContainsString('data-showroom-final-inline-mobile-cta', $template);
        self::assertStringContainsString(
            '/* J16P8 — mobile CTA becomes an inline block inside Final CTA. */',
            $css
        );
        self::assertStringNotContainsString(
            '--showroom-final-mobile-sticky-bottom: 10.5rem;',
            $css
        );
    }
}
