<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_customer_j12g_test extends \advanced_testcase {
    public function test_support_categories_do_not_include_refund(): void {
        $categories =
            \local_subscriptions\commerce\support\CommerceSupportRequest::categories();

        $this->assertNotContains('refund', $categories);
        $this->assertContains('technical', $categories);
        $this->assertContains('account', $categories);
    }

    public function test_print_template_uses_typed_placeholder(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/templates/cart/print.mustache'
        );

        $this->assertIsString($template);
        $this->assertStringContainsString(
            '{{placeholdericon}}',
            $template
        );
        $this->assertStringNotContainsString(
            '>CampusFR<',
            $template
        );
        $this->assertStringContainsString('is-discount', $template);
    }

    public function test_currency_selector_is_outside_filters(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/templates/storefront/catalog.mustache'
        );

        $this->assertIsString($template);
        $this->assertStringContainsString(
            'commerce-storefront__currency-form',
            $template
        );
        $this->assertStringContainsString(
            '<input type="hidden" name="currency" value="{{currency}}">',
            $template
        );
        $this->assertStringContainsString(
            'commerce-storefront__toolbar-actions',
            $template
        );
    }
}
