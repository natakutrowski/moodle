<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_exercise_mobile_presenter_j16c4_test extends \advanced_testcase {
    public function test_presenter_exposes_exercise_navigation_data(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/CommerceShowroomPresenter.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString(
            '\'exercises\' => $this->exercise_cards()',
            $source
        );
        self::assertStringContainsString("'exercisepreviewlabel'", $source);
        self::assertStringContainsString('private function exercise_cards()', $source);
    }
}
