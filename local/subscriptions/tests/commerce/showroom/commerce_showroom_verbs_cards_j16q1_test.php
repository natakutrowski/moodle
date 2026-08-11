<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_verbs_cards_j16q1_test extends \advanced_testcase {
    public function test_verbs_cards_is_registered_end_to_end(): void {
        global $CFG;

        $root = $CFG->dirroot . '/local/subscriptions/';
        $types = file_get_contents($root . 'classes/commerce/showroom/cms/CommerceShowroomBlockTypeRegistry.php');
        $runtime = file_get_contents($root . 'classes/commerce/showroom/cms/CommerceShowroomRuntimeBlockSet.php');
        $registry = file_get_contents($root . 'classes/commerce/showroom/cms/CommerceShowroomBlockEditorRegistry.php');
        $presenter = file_get_contents($root . 'classes/commerce/showroom/cms/CommerceShowroomBlockConfigurationPresenter.php');
        $template = file_get_contents($root . 'templates/showroom/third_group_verbs.mustache');
        $css = file_get_contents($root . 'styles/showroom.css');

        self::assertStringContainsString("'verbs_cards' => ['label' => 'Focus cartes de verbes'", $types);
        self::assertStringContainsString("'verbs_cards',", $runtime);
        self::assertStringContainsString("'verbs_cards' => self::definition([", $registry);
        self::assertStringContainsString("apply_verbs_cards", $presenter);
        self::assertStringContainsString('{{#isverbscards}}', $template);
        self::assertStringContainsString('data-showroom-track="verbs_cards_cta"', $template);
        self::assertStringContainsString('/* J16Q1 — Verbs Cards merchandising block. */', $css);
    }
}
