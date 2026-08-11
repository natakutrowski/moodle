<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\mail\CommerceMailContext;
use local_subscriptions\commerce\mail\CommerceMailIdempotencyKey;
use local_subscriptions\commerce\mail\CommerceMailRecipient;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailRuntime;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\mail\template\studio\CommerceMailTemplateDefaults;
use local_subscriptions\commerce\mail\template\studio\CommerceMailTemplateRepository;

final class commerce_mail_template_composition_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_every_type_and_language_has_complete_default_content(): void {
        foreach (CommerceMailType::all() as $type) {
            foreach (['fr', 'en', 'ru'] as $language) {
                $template = CommerceMailTemplateDefaults::get($type, $language);
                foreach (['subject', 'preheader', 'heading', 'introhtml', 'outrohtml', 'signaturehtml'] as $field) {
                    $this->assertNotSame('', trim((string)$template[$field]), $type . ':' . $language . ':' . $field);
                }
            }
        }
    }

    public function test_default_tokens_are_replaced_in_rendered_message(): void {
        $message = CommerceMailRuntime::template_registry()
            ->get(CommerceMailType::PURCHASE_RECEIPT)
            ->render($this->request('fr'));

        $this->assertStringContainsString('CFR-2026-000123', $message->get_subject());
        $this->assertStringContainsString('Bonjour Nata', $message->get_html());
        $this->assertStringNotContainsString('{firstname}', $message->get_html());
        $this->assertStringNotContainsString('{order_reference}', $message->get_subject());
    }

    public function test_active_custom_content_overrides_default_and_keeps_technical_block(): void {
        global $DB;
        (new CommerceMailTemplateRepository($DB))->save([
            'mailtype' => CommerceMailType::PURCHASE_RECEIPT,
            'language' => 'fr',
            'enabled' => 1,
            'subject' => 'Merci {firstname} — {order_reference}',
            'preheader' => 'Commande {order_reference}',
            'heading' => 'Bienvenue {firstname}',
            'introhtml' => '<p>Texte personnalisé pour {fullname}.</p>',
            'outrohtml' => '<p>Total : {order_total}</p>',
            'signaturehtml' => '<p>À bientôt !</p>',
            'headerimage' => 0,
        ], 2);

        $message = CommerceMailRuntime::template_registry()
            ->get(CommerceMailType::PURCHASE_RECEIPT)
            ->render($this->request('fr'));

        $this->assertSame('Merci Nata — CFR-2026-000123', $message->get_subject());
        $this->assertStringContainsString('Texte personnalisé pour Nata', $message->get_html());
        $this->assertStringContainsString('Cours de français A1', $message->get_html());
        $this->assertStringContainsString('129,00 €', $message->get_html());
    }

    private function request(string $language): CommerceMailRequest {
        return new CommerceMailRequest(
            CommerceMailType::PURCHASE_RECEIPT,
            new CommerceMailRecipient('nata@example.test', 'Nata CampusFR'),
            new CommerceMailContext([
                'customer' => ['firstname' => 'Nata', 'fullname' => 'Nata CampusFR'],
                'purchase' => ['reference' => 'CFR-2026-000123', 'totalformatted' => '129,00 €'],
                'items' => [[
                    'type' => 'course',
                    'title' => 'Cours de français A1',
                    'totalformatted' => '129,00 €',
                ]],
                'links' => ['order' => 'https://example.test/order'],
            ]),
            $language,
            CommerceMailIdempotencyKey::for_purchase(123, CommerceMailType::PURCHASE_RECEIPT),
            123
        );
    }
}
