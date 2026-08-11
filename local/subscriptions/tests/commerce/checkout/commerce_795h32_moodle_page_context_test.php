<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\checkout;

defined('MOODLE_INTERNAL') || die();

/** H3.2 certification for the Moodle page integration of Unified Checkout. */
final class commerce_795h32_moodle_page_context_test extends \advanced_testcase {
    public function test_page_context_is_initialized_before_checkout_services_are_used(): void {
        global $CFG;

        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/commerce_checkout.php');
        self::assertIsString($source);

        $setcontext = strpos($source, '$PAGE->set_context(context_system::instance());');
        $prepare = strpos($source, 'CommerceCheckoutRuntimeFactory::create()->prepare(');

        self::assertNotFalse($setcontext);
        self::assertNotFalse($prepare);
        self::assertLessThan($prepare, $setcontext);
    }

    public function test_page_metadata_is_initialized_before_checkout_preparation(): void {
        global $CFG;

        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/commerce_checkout.php');
        self::assertIsString($source);

        $prepare = strpos($source, 'CommerceCheckoutRuntimeFactory::create()->prepare(');

        foreach ([
            '$PAGE->set_pagelayout(\'standard\');',
            '$PAGE->set_title(get_string(\'commerce_checkout_title\', \'local_subscriptions\'));',
            '$PAGE->set_heading(get_string(\'commerce_checkout_title\', \'local_subscriptions\'));',
        ] as $statement) {
            $position = strpos($source, $statement);
            self::assertNotFalse($position);
            self::assertLessThan($prepare, $position);
        }
    }
}
