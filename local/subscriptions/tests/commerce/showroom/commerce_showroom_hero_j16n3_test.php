<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_hero_j16n3_test extends \advanced_testcase {
    public function test_hero_polish_removes_auxiliary_copy_and_keeps_final_heading_contract(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/';
        $template = file_get_contents($root . 'templates/showroom/third_group_verbs.mustache');
        $defaults = file_get_contents(
            $root . 'classes/commerce/showroom/cms/CommerceShowroomBlockDefaultsCatalog.php'
        );
        $css = file_get_contents($root . 'styles/showroom.css');

        self::assertStringNotContainsString('commerce-showroom-hero__proof', $template);
        self::assertStringNotContainsString('commerce-showroom-hero__summary', $template);
        self::assertStringContainsString("'stagelabel' => 'Тренажёр глаголов 3-й группы'", $defaults);
        self::assertStringContainsString("'stagelabel' => 'ENTRAÎNEUR DE VERBES DU 3e GROUPE'", $defaults);
        self::assertStringContainsString("'stagelabel' => 'THIRD-GROUP VERB TRAINER'", $defaults);

        self::assertStringContainsString('/* J16N5 — authoritative Hero title flow.', $css);
        self::assertStringContainsString('font-size: clamp(2.25rem, 3.45vw, 3.4rem);', $css);
        self::assertStringContainsString('.commerce-showroom-hero__title-accent', $css);
        self::assertStringContainsString('color: var(--showroom-pink) !important;', $css);
    }
}
