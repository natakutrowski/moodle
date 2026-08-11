<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_hero_j16n2_test extends \advanced_testcase {
    public function test_hero_and_stats_builder_are_separated_and_images_fill_frames(): void {
        global $CFG;

        $root = $CFG->dirroot . '/local/subscriptions/';
        $registry = file_get_contents(
            $root . 'classes/commerce/showroom/cms/CommerceShowroomBlockEditorRegistry.php'
        );
        $css = file_get_contents($root . 'styles/showroom.css');

        self::assertStringNotContainsString(
            "self::checkbox('showstats', 'Afficher les chiffres clés')",
            $registry
        );
        self::assertStringContainsString(
            "'4 chiffres sous le Hero (une ligne : valeur|libellé|icône)'",
            $registry
        );
        self::assertStringContainsString(
            "'Animer les chiffres au scroll'",
            $registry
        );

        self::assertStringContainsString(
            '.commerce-showroom-device--desktop .commerce-showroom-device__cms-image--desktop',
            $css
        );
        self::assertStringContainsString(
            'object-fit: cover !important;',
            $css
        );
        self::assertStringContainsString(
            '.commerce-showroom-device--mobile.has-cms-image',
            $css
        );
        self::assertStringContainsString(
            'font-size: clamp(2.8rem, 5vw, 4.85rem);',
            $css
        );
    }
}
