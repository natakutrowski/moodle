<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();


final class commerce_showroom_normalized_blocks_j16g2_test extends \advanced_testcase {
    public function test_showroom_template_renders_normalised_ordered_blocks(): void {
        global $CFG;
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/third_group_verbs.mustache');
        self::assertIsString($template);
        self::assertStringContainsString('{{#showroomorderedblocks}}', $template);
        foreach (['isproblem', 'islearningmethod', 'isascent', 'isexerciseexplorer', 'isoffers', 'isfaq', 'issupport', 'isfinalcta'] as $guard) {
            self::assertStringContainsString('{{#' . $guard . '}}', $template);
        }
    }
}
