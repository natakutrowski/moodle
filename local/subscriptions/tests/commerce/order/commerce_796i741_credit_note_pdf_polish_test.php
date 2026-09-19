<?php

declare(strict_types=1);

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\refund\CommerceRefundReasonPresenter;

final class commerce_796i741_credit_note_pdf_polish_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_known_refund_reasons_are_localised(): void {
        $presenter = new CommerceRefundReasonPresenter();

        self::assertNotSame(
            'requested_by_customer',
            $presenter->label('requested_by_customer')
        );
        self::assertNotSame(
            'duplicate',
            $presenter->label('duplicate')
        );
        self::assertSame(
            'custom_reason',
            $presenter->label('custom_reason')
        );
    }

    public function test_credit_note_snapshot_contains_original_purchase_items(): void {
        global $CFG;

        $issuer = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/order/creditnote/CommerceCreditNoteIssuer.php'
        );

        self::assertStringContainsString(
            "'original_purchase' => [",
            $issuer
        );
        self::assertStringContainsString(
            "'items' => array_map(",
            $issuer
        );
        self::assertStringContainsString(
            "'net_minor' => \$item->netminor",
            $issuer
        );
    }

    public function test_credit_note_pdf_shows_original_total_items_and_provider_icon(): void {
        global $CFG;

        $pdf = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/order/creditnote/CommerceCreditNotePdfService.php'
        );

        self::assertStringContainsString(
            'commerce_credit_note_original_purchase_total',
            $pdf
        );
        self::assertStringContainsString(
            '$originalitems',
            $pdf
        );
        self::assertStringContainsString(
            "'/pix/providers/' . \$provider . '.svg'",
            $pdf
        );
        self::assertStringContainsString(
            'CommerceRefundReasonPresenter',
            $pdf
        );
    }

    public function test_i741_requires_no_version_bump(): void {
        global $CFG;

        $version = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/version.php'
        );

        self::assertSame(
            1,
            preg_match(
                '/\\$plugin->version\\s*=\\s*(\\d+);/',
                $version,
                $pluginversionmatch
            )
        );
        self::assertGreaterThanOrEqual(
            2026090801,
            (int)$pluginversionmatch[1]
        );
    }
}
