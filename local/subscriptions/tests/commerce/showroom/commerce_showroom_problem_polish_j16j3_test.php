<?php
declare(strict_types=1);
namespace local_subscriptions\tests\commerce\showroom;
defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_problem_polish_j16j3_test extends \advanced_testcase {
    public function test_problem_polish_hooks_are_present(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/';
        $template = file_get_contents($root . 'templates/showroom/third_group_verbs.mustache');
        $presenter = file_get_contents($root . 'classes/commerce/showroom/cms/CommerceShowroomBlockConfigurationPresenter.php');
        $css = file_get_contents($root . 'styles/showroom.css');

        self::assertStringContainsString('problemdescriptionlines', $presenter);
        self::assertStringContainsString('problemsolutiontextaccent2', $presenter);
        self::assertStringContainsString('commerce-showroom-problem__solution-accent', $template);
        self::assertStringContainsString('commerce-showroom-problem__solution-accent', $css);
        self::assertStringContainsString('font-weight: 800;', $css);
    }
}
