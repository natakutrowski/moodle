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

final class commerce_transactional_mail_templates_test extends advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_runtime_registers_every_supported_native_template(): void {
        $registry = CommerceMailRuntime::template_registry();

        foreach (CommerceMailType::all() as $type) {
            $this->assertTrue($registry->has($type), 'Missing template for ' . $type);
        }
    }

    /**
     * @dataProvider language_provider
     */
    public function test_purchase_access_renders_course_digital_and_bundle_content(
        string $language,
        string $subjectfragment
    ): void {
        $message = CommerceMailRuntime::template_registry()
            ->get(CommerceMailType::PURCHASE_ACCESS)
            ->render($this->request(CommerceMailType::PURCHASE_ACCESS, $language));

        $this->assertStringContainsString($subjectfragment, $message->get_subject());
        $this->assertStringContainsString('Cours de français A1', $message->get_html());
        $this->assertStringContainsString('Guide PDF', $message->get_html());
        $this->assertStringContainsString('Pack verbes', $message->get_html());
        $this->assertStringContainsString('https://example.test/access/mobile', $message->get_html());
        $this->assertStringContainsString('CFR-TEST-106', $message->get_text());
        $this->assertSame($language, $message->get_metadata()['language']);
    }

    public static function language_provider(): array {
        return [
            'French' => ['fr', 'CampusFR'],
            'English' => ['en', 'CampusFR'],
            'Russian' => ['ru', 'CampusFR'],
        ];
    }

    public function test_receipt_renders_payment_and_totals(): void {
        $message = CommerceMailRuntime::template_registry()
            ->get(CommerceMailType::PURCHASE_RECEIPT)
            ->render($this->request(CommerceMailType::PURCHASE_RECEIPT, 'fr'));

        $this->assertStringContainsString('129,00 €', $message->get_html());
        $this->assertStringContainsString('Stripe', $message->get_html());
        $this->assertStringContainsString('pi_test_106', $message->get_html());
        $this->assertStringContainsString('https://example.test/order', $message->get_html());
    }

    /**
     * @dataProvider payment_type_provider
     */
    public function test_payment_status_templates_render_without_transport(string $type): void {
        $message = CommerceMailRuntime::template_registry()
            ->get($type)
            ->render($this->request($type, 'fr'));

        $this->assertNotSame('', $message->get_subject());
        $this->assertStringContainsString('CFR-TEST-106', $message->get_html());
        $this->assertStringContainsString('customer@example.test', $message->get_recipient()->get_email());
    }

    public static function payment_type_provider(): array {
        return [
            [CommerceMailType::PAYMENT_PENDING],
            [CommerceMailType::PAYMENT_FAILED],
            [CommerceMailType::PAYMENT_CANCELLED],
        ];
    }

    private function request(string $type, string $language): CommerceMailRequest {
        return new CommerceMailRequest(
            $type,
            new CommerceMailRecipient('customer@example.test', 'Nata CampusFR'),
            new CommerceMailContext([
                'customer' => [
                    'firstname' => 'Nata',
                ],
                'purchase' => [
                    'reference' => 'CFR-TEST-106',
                    'totalformatted' => '129,00 €',
                ],
                'items' => [
                    [
                        'type' => 'course',
                        'title' => 'Cours de français A1',
                        'totalformatted' => '79,00 €',
                        'producturl' => 'https://example.test/course',
                        'accesses' => [[
                            'kind' => 'course',
                            'label' => 'Accéder au cours',
                            'url' => 'https://example.test/access/course',
                        ]],
                    ],
                    [
                        'type' => 'digital',
                        'title' => 'Guide PDF',
                        'totalformatted' => '20,00 €',
                        'accesses' => [[
                            'kind' => 'download',
                            'label' => 'Version mobile',
                            'url' => 'https://example.test/access/mobile',
                            'filename' => 'guide-mobile.pdf',
                            'filetype' => 'PDF',
                            'filesize' => '3,2 Mo',
                        ]],
                    ],
                    [
                        'type' => 'bundle',
                        'title' => 'Pack verbes',
                        'totalformatted' => '30,00 €',
                    ],
                ],
                'payment' => [
                    'providerlabel' => 'Stripe',
                    'transactionreference' => 'pi_test_106',
                    'statuslabel' => 'Payé',
                    'amountformatted' => '129,00 €',
                ],
                'links' => [
                    'order' => 'https://example.test/order',
                    'purchases' => 'https://example.test/purchases',
                    'resources' => 'https://example.test/resources',
                    'courses' => 'https://example.test/courses',
                ],
            ]),
            $language,
            CommerceMailIdempotencyKey::for_purchase(106, $type),
            106
        );
    }
}
