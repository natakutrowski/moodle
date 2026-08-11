<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\mail\CommerceMailAttachment;
use local_subscriptions\commerce\mail\CommerceMailMessage;
use local_subscriptions\commerce\mail\CommerceMailRecipient;
use local_subscriptions\commerce\order\invoice\CommerceInvoicePdfService;
use local_subscriptions\commerce\order\presentation\CommerceOrderItemPresentation;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentation;

final class commerce_invoice_mail_attachment_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_invoice_service_generates_canonical_pdf_document(): void {
        global $DB;
        $order = new CommerceOrderPresentation(
            42,
            'uuid-test',
            'cmp_test_invoice',
            'course',
            null,
            'nata@example.test',
            'EUR',
            12900,
            'completed',
            'paid',
            'fulfilled',
            'stripe',
            1785529192,
            1785529000,
            [new CommerceOrderItemPresentation(
                'item-1', 'course', 'Cours de français A1', 1, 'EUR', 12900, 12900, 0, 12900
            )],
            []
        );

        $document = (new CommerceInvoicePdfService($DB))->generate($order);

        $this->assertStringStartsWith('facture-cfr-', $document->get_filename());
        $this->assertStringEndsWith('.pdf', $document->get_filename());
        $this->assertSame('application/pdf', $document->get_mimetype());
        $this->assertStringStartsWith('%PDF-', $document->get_content());
    }

    public function test_message_carries_invoice_attachment(): void {
        $attachment = new CommerceMailAttachment('facture-CFR-2026-000042.pdf', 'application/pdf', '%PDF-test');
        $message = new CommerceMailMessage(
            new CommerceMailRecipient('nata@example.test', 'Nata'),
            'Votre facture',
            '<p>Merci</p>',
            'Merci',
            [],
            [$attachment]
        );

        $this->assertCount(1, $message->get_attachments());
        $this->assertSame('facture-CFR-2026-000042.pdf', $message->get_attachments()[0]->get_filename());
    }
}
