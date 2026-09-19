<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h435_checkout_reassurance_visual_polish_test extends \advanced_testcase {

    public function test_presenter_exposes_card_flag_for_current_action_card_presentation(): void {
        global $CFG;

        $presenter = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/checkout/unified/presentation/CommerceCheckoutPaymentMethodPresenter.php'
        );
        self::assertIsString($presenter);

        self::assertStringContainsString("'iscard' =>", $presenter);
        self::assertStringContainsString('CommercePaymentMethod::CARD', $presenter);
    }

}
