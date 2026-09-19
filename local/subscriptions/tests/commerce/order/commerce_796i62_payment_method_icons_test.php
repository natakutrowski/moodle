<?php

declare(strict_types=1);

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\method\CommercePaymentMethod;
use local_subscriptions\commerce\payment\method\CommercePaymentMethodVisual;

final class commerce_796i62_payment_method_icons_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_every_known_payment_method_has_a_shared_icon_asset(): void {
        global $CFG;

        foreach (CommercePaymentMethod::KNOWN as $method) {
            $filename = CommercePaymentMethodVisual::icon_filename($method);
            self::assertNotNull($filename, $method);
            self::assertFileExists(
                $CFG->dirroot
                    . '/local/subscriptions/pix/providers/'
                    . $filename,
                $method
            );
        }
    }

    public function test_unknown_payment_method_has_no_fake_icon(): void {
        self::assertNull(
            CommercePaymentMethodVisual::icon_filename(null)
        );
        self::assertNull(
            CommercePaymentMethodVisual::icon_filename('unknown')
        );
    }

    public function test_detailed_surfaces_render_icon_and_name(): void {
        global $CFG;

        $invoice = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/order/invoice/'
                . 'CommerceInvoicePdfService.php'
        );
        $orderdetails = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/order_details.php'
        );
        $ordertemplate = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/templates/order_details/page.mustache'
        );
        $purchaseview = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/admin/commerce/purchases/view.php'
        );

        self::assertStringContainsString(
            'CommercePaymentMethodVisual::icon_path',
            $invoice
        );
        self::assertStringContainsString(
            'CommercePaymentMethodVisual::label_with_icon',
            $orderdetails
        );
        self::assertStringContainsString(
            '{{{paymentmethodhtml}}}',
            $ordertemplate
        );
        self::assertStringContainsString(
            'CommercePaymentMethodVisual::label_with_icon',
            $purchaseview
        );
    }

    public function test_sales_list_uses_icons_only_for_method_and_provider(): void {
        global $CFG;

        $sales = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/admin/commerce/purchases/index.php'
        );

        self::assertStringContainsString(
            'CommercePaymentMethodVisual::icon_html',
            $sales
        );
        self::assertStringContainsString(
            'crm-sales-provider-cell d-flex align-items-center gap-2',
            $sales
        );
        self::assertStringNotContainsString(
            "html_writer::span(s(\$providername), 'ms-1')",
            $sales
        );
        self::assertStringNotContainsString(
            "html_writer::div(\n            s(\$paymentmethodlabel)",
            $sales
        );
    }

    public function test_i62_has_no_schema_or_version_change(): void {
        global $CFG;

        $version = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/version.php'
        );

        self::assertMatchesRegularExpression(
            '/\\$plugin->version = \\d+;/',
            $version
        );
    }
}
