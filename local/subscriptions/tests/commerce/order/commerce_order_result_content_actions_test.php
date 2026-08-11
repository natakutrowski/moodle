<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J6.7B15 post-payment content action UX. */
final class commerce_order_result_content_actions_test
        extends \advanced_testcase {

    public function test_order_result_supports_course_and_two_digital_actions(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/order_result.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            'fa-graduation-cap me-2',
            $source
        );
        $this->assertStringContainsString(
            'digital_download_classic',
            $source
        );
        $this->assertStringContainsString(
            'digital_download_mobile',
            $source
        );
        $this->assertStringContainsString(
            "['version' => 'desktop']",
            $source
        );
        $this->assertStringContainsString(
            "['version' => 'mobile']",
            $source
        );
    }

    public function test_result_content_links_have_icons(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/order_result.php'
        );

        $this->assertStringContainsString(
            'fa-file-arrow-down',
            $source
        );
        $this->assertStringContainsString(
            'fa-bag-shopping',
            $source
        );
    }

    public function test_order_details_course_action_uses_graduation_cap(): void {
        global $CFG;
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/order_details/page.mustache');
        self::assertStringContainsString('fa-graduation-cap', $template);
        self::assertStringContainsString('fa-lock', $template);
        self::assertStringContainsString('data-requires-account-finalisation', $template);


    }

    public function test_item_actions_are_spaced_from_separators(): void {
        global $CFG;

        $styles = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/styles/order_result.css'
        );

        $this->assertStringContainsString(
            '.commerce-order-item-group + .commerce-order-item-group',
            $styles
        );
        $this->assertStringContainsString(
            '.commerce-order-item__actions',
            $styles
        );
    }
}
