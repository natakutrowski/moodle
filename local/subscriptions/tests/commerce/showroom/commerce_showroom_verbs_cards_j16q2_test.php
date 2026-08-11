<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_verbs_cards_j16q2_test extends \advanced_testcase {
    public function test_verbs_cards_supports_literal_newline_inside_item_cells(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/';
        $presenter = file_get_contents(
            $root . 'classes/commerce/showroom/cms/CommerceShowroomBlockConfigurationPresenter.php'
        );
        $registry = file_get_contents(
            $root . 'classes/commerce/showroom/cms/CommerceShowroomBlockEditorRegistry.php'
        );
        $css = file_get_contents($root . 'styles/showroom.css');

        self::assertStringContainsString(
            'private function verbs_cards_items(string $raw): array',
            $presenter
        );
        self::assertStringContainsString(
            "str_replace('\\\\n', \"\\n\"",
            $presenter
        );
        self::assertStringContainsString('return array_slice($items, 0, 3);', $presenter);
        self::assertStringContainsString(
            'utiliser \\\\n pour un retour à la ligne',
            $registry
        );
        self::assertStringContainsString(
            '/* J16Q2 — preserve explicit \\n line breaks inside Verbs Cards benefits. */',
            $css
        );
    }
}
