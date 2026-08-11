<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J6.7B12.1 print page context initialisation order. */
final class commerce_cart_print_context_j67b121_test
        extends \advanced_testcase {

    public function test_page_context_is_set_before_presenter_call(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/cart_print.php'
        );

        $this->assertIsString($source);

        $context = strpos(
            $source,
            '$PAGE->set_context(context_system::instance())'
        );
        $presenter = strpos(
            $source,
            'CommerceCartPresenter::present('
        );

        $this->assertIsInt($context);
        $this->assertIsInt($presenter);
        $this->assertLessThan(
            $presenter,
            $context
        );
    }
}
