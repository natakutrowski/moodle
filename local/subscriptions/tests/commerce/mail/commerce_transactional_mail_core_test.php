<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\mail\CommerceMailContext;
use local_subscriptions\commerce\mail\CommerceMailDispatcher;
use local_subscriptions\commerce\mail\CommerceMailIdempotencyKey;
use local_subscriptions\commerce\mail\CommerceMailMessage;
use local_subscriptions\commerce\mail\CommerceMailRecipient;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailTemplate;
use local_subscriptions\commerce\mail\CommerceMailTemplateNotFoundException;
use local_subscriptions\commerce\mail\CommerceMailTemplateRegistry;
use local_subscriptions\commerce\mail\CommerceMailTransport;
use local_subscriptions\commerce\mail\CommerceMailType;

final class commerce_transactional_mail_core_test extends advanced_testcase {

    public function test_request_supports_guest_recipient_and_serialisable_context(): void {
        $recipient = new CommerceMailRecipient(
            'guest@example.test',
            'Guest Customer'
        );
        $context = new CommerceMailContext([
            'purchase' => [
                'reference' => 'CFR-2026-0001',
                'items' => 2,
            ],
        ]);

        $request = new CommerceMailRequest(
            CommerceMailType::PURCHASE_ACCESS,
            $recipient,
            $context,
            'FR',
            ' Purchase:42:Purchase_Access ',
            42
        );

        $this->assertTrue($request->get_recipient()->is_guest());
        $this->assertSame('fr', $request->get_language());
        $this->assertSame('purchase:42:purchase_access', $request->get_idempotency_key());
        $this->assertSame('CFR-2026-0001', $request->get_context()->require('purchase')['reference']);
    }

    public function test_idempotency_keys_are_stable(): void {
        $this->assertSame(
            'purchase:84:purchase_receipt',
            CommerceMailIdempotencyKey::for_purchase(
                84,
                CommerceMailType::PURCHASE_RECEIPT
            )
        );

        $this->assertSame(
            'payment-attempt:12:payment_failed',
            CommerceMailIdempotencyKey::for_payment_attempt(
                12,
                CommerceMailType::PAYMENT_FAILED
            )
        );
    }

    public function test_registry_rejects_duplicates_and_missing_templates(): void {
        $template = new commerce_mail_test_template();
        $registry = new CommerceMailTemplateRegistry([$template]);

        $this->assertTrue($registry->has(CommerceMailType::PURCHASE_ACCESS));
        $this->assertSame($template, $registry->get(CommerceMailType::PURCHASE_ACCESS));

        try {
            $registry->register(new commerce_mail_test_template());
            $this->fail('A duplicate Commerce mail template should be rejected.');
        } catch (\coding_exception $exception) {
            $this->assertStringContainsString('already registered', $exception->getMessage());
        }

        $this->expectException(CommerceMailTemplateNotFoundException::class);
        $registry->get(CommerceMailType::PURCHASE_RECEIPT);
    }

    public function test_preview_renders_without_using_transport(): void {
        $transport = new commerce_mail_recording_transport();
        $dispatcher = new CommerceMailDispatcher(
            new CommerceMailTemplateRegistry([
                new commerce_mail_test_template(),
            ]),
            $transport
        );

        $message = $dispatcher->preview($this->request());

        $this->assertSame('Your CampusFR access', $message->get_subject());
        $this->assertSame(0, $transport->get_send_count());
    }

    public function test_dispatch_renders_and_sends_exactly_once(): void {
        $transport = new commerce_mail_recording_transport();
        $dispatcher = new CommerceMailDispatcher(
            new CommerceMailTemplateRegistry([
                new commerce_mail_test_template(),
            ]),
            $transport
        );

        $message = $dispatcher->dispatch($this->request());

        $this->assertSame(1, $transport->get_send_count());
        $this->assertSame($message, $transport->get_last_message());
        $this->assertSame('customer@example.test', $message->get_recipient()->get_email());
    }

    private function request(): CommerceMailRequest {
        return new CommerceMailRequest(
            CommerceMailType::PURCHASE_ACCESS,
            new CommerceMailRecipient(
                'customer@example.test',
                'CampusFR Customer',
                123
            ),
            new CommerceMailContext([
                'purchaseid' => 42,
            ]),
            'fr',
            CommerceMailIdempotencyKey::for_purchase(
                42,
                CommerceMailType::PURCHASE_ACCESS
            ),
            42
        );
    }
}

final class commerce_mail_test_template implements CommerceMailTemplate {

    public function get_type(): string {
        return CommerceMailType::PURCHASE_ACCESS;
    }

    public function render(CommerceMailRequest $request): CommerceMailMessage {
        return new CommerceMailMessage(
            $request->get_recipient(),
            'Your CampusFR access',
            '<p>Your resources are ready.</p>',
            'Your resources are ready.',
            [
                'language' => $request->get_language(),
                'idempotencykey' => $request->get_idempotency_key(),
            ]
        );
    }
}

final class commerce_mail_recording_transport implements CommerceMailTransport {

    private int $sendcount = 0;
    private ?CommerceMailMessage $lastmessage = null;

    public function send(CommerceMailMessage $message): void {
        $this->sendcount++;
        $this->lastmessage = $message;
    }

    public function get_send_count(): int {
        return $this->sendcount;
    }

    public function get_last_message(): ?CommerceMailMessage {
        return $this->lastmessage;
    }
}
