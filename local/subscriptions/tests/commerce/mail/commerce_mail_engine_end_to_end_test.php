<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\mail\CommerceMailContext;
use local_subscriptions\commerce\mail\CommerceMailDispatcher;
use local_subscriptions\commerce\mail\CommerceMailIdempotencyKey;
use local_subscriptions\commerce\mail\CommerceMailMessage;
use local_subscriptions\commerce\mail\CommerceMailQueueProcessor;
use local_subscriptions\commerce\mail\CommerceMailQueueRepository;
use local_subscriptions\commerce\mail\CommerceMailQueueService;
use local_subscriptions\commerce\mail\CommerceMailRecipient;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailRetryPolicy;
use local_subscriptions\commerce\mail\CommerceMailRuntime;
use local_subscriptions\commerce\mail\CommerceMailStatus;
use local_subscriptions\commerce\mail\CommerceMailTransport;
use local_subscriptions\commerce\mail\CommerceMailType;

/**
 * End-to-end certification of the transactional outbox with the real templates.
 *
 * The SMTP transport is replaced by a capturing test double, so this suite never
 * sends an external message.
 */
final class commerce_mail_engine_end_to_end_test extends advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @dataProvider purchase_scenario_provider
     */
    public function test_guest_purchase_scenarios_are_queued_rendered_and_sent_once(
        string $scenario,
        array $items,
        array $expectedfragments
    ): void {
        $repository = new CommerceMailQueueRepository();
        $queue = new CommerceMailQueueService($repository);
        $transport = new class implements CommerceMailTransport {
            /** @var CommerceMailMessage[] */
            public array $messages = [];

            public function send(CommerceMailMessage $message): void {
                $this->messages[] = $message;
            }
        };
        $templates = CommerceMailRuntime::template_registry();
        $processor = new CommerceMailQueueProcessor(
            $repository,
            $templates,
            new CommerceMailDispatcher($templates, $transport),
            new CommerceMailRetryPolicy()
        );

        $purchaseid = 700 + count($items);
        $request = new CommerceMailRequest(
            CommerceMailType::PURCHASE_ACCESS,
            new CommerceMailRecipient('guest@example.test', 'Guest CampusFR'),
            new CommerceMailContext($this->context($scenario, $items)),
            'fr',
            CommerceMailIdempotencyKey::for_purchase($purchaseid, CommerceMailType::PURCHASE_ACCESS),
            $purchaseid
        );

        $firstrecord = $queue->queue($request);
        $duplicaterecord = $queue->queue($request);
        $result = $processor->process_ids([(int)$firstrecord->id]);
        $secondresult = $processor->process_ids([(int)$firstrecord->id]);

        $this->assertSame((int)$firstrecord->id, (int)$duplicaterecord->id);
        $this->assertSame(1, $result['sent']);
        $this->assertSame(1, $secondresult['skipped']);
        $this->assertCount(1, $transport->messages);

        $message = $transport->messages[0];
        $this->assertSame('guest@example.test', $message->get_recipient()->get_email());
        $this->assertNull($message->get_recipient()->get_user_id());
        $this->assertStringContainsString('CFR-2026-' . strtoupper($scenario), $message->get_html());
        $this->assertStringNotContainsString('cmp_', strip_tags($message->get_html()));
        $this->assertNotSame('', trim($message->get_text()));

        foreach ($expectedfragments as $fragment) {
            $this->assertStringContainsString($fragment, $message->get_html());
        }

        $persisted = $repository->find_by_id((int)$firstrecord->id);
        $this->assertNotNull($persisted);
        $this->assertSame(CommerceMailStatus::SENT, $persisted->status);
        $this->assertSame(1, (int)$persisted->attemptcount);
        $this->assertNotNull($persisted->timesent);
    }

    public static function purchase_scenario_provider(): array {
        return [
            'course' => [
                'course',
                [[
                    'type' => 'course',
                    'title' => 'Cours de français A1',
                    'totalformatted' => '79,00 €',
                    'accesses' => [[
                        'kind' => 'course',
                        'label' => 'Accéder au cours',
                        'url' => 'https://example.test/course/access',
                    ]],
                ]],
                ['Cours de français A1', 'Accéder au cours'],
            ],
            'digital' => [
                'digital',
                [[
                    'type' => 'digital',
                    'title' => 'Guide des verbes',
                    'totalformatted' => '20,00 €',
                    'accesses' => [[
                        'kind' => 'download',
                        'label' => 'Version classique',
                        'url' => 'https://example.test/digital/download',
                        'filename' => 'guide-verbes.pdf',
                        'filetype' => 'PDF',
                        'filesize' => '4,8 Mo',
                    ]],
                ]],
                ['Guide des verbes', 'https://example.test/digital/download'],
            ],
            'bundle' => [
                'bundle',
                [[
                    'type' => 'bundle',
                    'title' => 'Pack français essentiel',
                    'totalformatted' => '99,00 €',
                    'accesses' => [[
                        'kind' => 'course',
                        'label' => 'Accéder au cours A1',
                        'url' => 'https://example.test/bundle/course',
                    ], [
                        'kind' => 'download',
                        'label' => 'Télécharger le guide',
                        'url' => 'https://example.test/bundle/digital',
                        'filename' => 'guide-bundle.pdf',
                        'filetype' => 'PDF',
                        'filesize' => '6,1 Mo',
                    ]],
                ]],
                ['Pack français essentiel', 'https://example.test/bundle/course'],
            ],
            'mixed' => [
                'mixed',
                [[
                    'type' => 'course',
                    'title' => 'Cours A2',
                    'totalformatted' => '89,00 €',
                    'accesses' => [[
                        'kind' => 'course',
                        'label' => 'Accéder au cours A2',
                        'url' => 'https://example.test/mixed/course',
                    ]],
                ], [
                    'type' => 'digital',
                    'title' => 'Cahier A2',
                    'totalformatted' => '15,00 €',
                    'accesses' => [[
                        'kind' => 'download',
                        'label' => 'Télécharger le cahier',
                        'url' => 'https://example.test/mixed/digital',
                        'filename' => 'cahier-a2.pdf',
                        'filetype' => 'PDF',
                        'filesize' => '3,4 Mo',
                    ]],
                ]],
                ['Cours A2', 'Cahier A2', 'https://example.test/mixed/digital'],
            ],
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $items
     * @return array<string,mixed>
     */
    private function context(string $scenario, array $items): array {
        $reference = 'CFR-2026-' . strtoupper($scenario);

        return [
            'customer' => [
                'firstname' => 'Nata',
                'fullname' => 'Nata CampusFR',
            ],
            'purchase' => [
                'reference' => $reference,
                'totalformatted' => '104,00 €',
            ],
            'items' => $items,
            'payment' => [
                'providerlabel' => 'Stripe',
                'transactionreference' => 'pi_' . $scenario,
                'statuslabel' => 'Payé',
                'amountformatted' => '104,00 €',
            ],
            'links' => [
                'order' => 'https://example.test/order/' . $scenario,
                'purchases' => 'https://example.test/purchases',
                'resources' => 'https://example.test/resources',
                'courses' => 'https://example.test/courses',
            ],
        ];
    }
}
