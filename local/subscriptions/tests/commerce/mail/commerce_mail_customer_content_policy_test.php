<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\mail\CommerceMailAttachment;
use local_subscriptions\commerce\mail\CommerceMailCustomerContentPolicy;
use local_subscriptions\commerce\mail\CommerceMailMessage;
use local_subscriptions\commerce\mail\CommerceMailRecipient;

final class commerce_mail_customer_content_policy_test extends advanced_testcase {

    public function test_public_reference_is_allowed_and_internal_reference_in_url_is_ignored(): void {
        $message = new CommerceMailMessage(
            new CommerceMailRecipient('customer@example.test'),
            'Commande CFR-2026-A1B2C3',
            '<p>Commande CFR-2026-A1B2C3</p><a href="https://example.test/order?reference=cmp_hidden123">Voir</a>',
            "Commande CFR-2026-A1B2C3\nhttps://example.test/order?reference=cmp_hidden123"
        );

        (new CommerceMailCustomerContentPolicy())->assert_safe($message);
        $this->addToAssertionCount(1);
    }

    public function test_internal_reference_in_visible_content_is_rejected(): void {
        $message = new CommerceMailMessage(
            new CommerceMailRecipient('customer@example.test'),
            'Commande cmp_secret123',
            '<p>Commande cmp_secret123</p>',
            'Commande cmp_secret123'
        );

        $this->expectException(\coding_exception::class);
        (new CommerceMailCustomerContentPolicy())->assert_safe($message);
    }

    public function test_internal_reference_in_attachment_filename_is_rejected(): void {
        $message = new CommerceMailMessage(
            new CommerceMailRecipient('customer@example.test'),
            'Commande CFR-2026-A1B2C3',
            '<p>Commande CFR-2026-A1B2C3</p>',
            'Commande CFR-2026-A1B2C3',
            [],
            [new CommerceMailAttachment('facture-cmp_secret123.pdf', 'application/pdf', '%PDF-test')]
        );

        $this->expectException(\coding_exception::class);
        (new CommerceMailCustomerContentPolicy())->assert_safe($message);
    }
}
