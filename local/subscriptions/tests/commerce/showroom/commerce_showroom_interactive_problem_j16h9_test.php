<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_interactive_problem_j16h9_test extends \advanced_testcase {
    public function test_interactive_problem_has_centered_heading_and_solution_accents(): void {
        global $CFG;

        $root = $CFG->dirroot . '/local/subscriptions';
        $defaults = file_get_contents(
            $root . '/classes/commerce/showroom/cms/CommerceShowroomBlockDefaultsCatalog.php'
        );
        $presenter = file_get_contents(
            $root . '/classes/commerce/showroom/cms/CommerceShowroomBlockConfigurationPresenter.php'
        );
        $template = file_get_contents(
            $root . '/templates/showroom/third_group_verbs.mustache'
        );
        $css = file_get_contents($root . '/styles/showroom.css');

        self::assertStringContainsString(
            'нужная форма глагола третьей группы',
            $defaults
        );
        self::assertStringContainsString('solutiontitleaccent', $defaults);
        self::assertStringContainsString('solutiontextaccent', $defaults);
        self::assertStringContainsString(
            'interactiveproblemsolutiontitleaccent',
            $presenter
        );
        self::assertStringContainsString(
            'commerce-showroom-interactive-problem__solution-accent',
            $template
        );
        self::assertStringContainsString(
            '.commerce-showroom-interactive-problem__heading {',
            $css
        );
        self::assertStringContainsString('border-color: #48b77c;', $css);
        self::assertStringContainsString('background: #fff;', $css);
    }
}
