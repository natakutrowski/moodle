<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\storefront\localisation\CommerceStorefrontLocaleTransferService;
use local_subscriptions\commerce\storefront\translation\CommerceStorefrontAiTranslationService;
use local_subscriptions\crm\inbox\ai\providers\openai\OpenAiApiKeyProvider;
use local_subscriptions\crm\inbox\ai\providers\openai\OpenAiInboxConfiguration;
use local_subscriptions\crm\inbox\ai\providers\openai\contracts\OpenAiResponsesClientInterface;
use local_subscriptions\crm\inbox\ai\providers\openai\dto\OpenAiResponsesResult;

final class commerce_storefront_locale_translation_test extends advanced_testcase {
    public function test_locale_copy_preserves_global_configuration_and_media_references(): void {
        $metadata = [
            'storefront' => [
                'template' => 'editorial',
                'routing' => ['slugs' => ['fr' => 'cartes', 'ru' => 'kartochki']],
                'locales' => [
                    'ru' => [
                        'seo' => ['title' => 'Карточки', 'description' => 'Описание'],
                        'sections' => [[
                            'id' => 'hero',
                            'type' => 'hero',
                            'title' => 'Карточки',
                            'mediaitemid' => 12345,
                        ]],
                        'experience' => [
                            'quickfacts' => [['value' => '178', 'label' => 'карточек']],
                        ],
                    ],
                ],
            ],
            'showroom' => [
                'key' => 'verbs',
                'locales' => ['ru' => ['alt' => 'Карточки на горе']],
            ],
        ];

        $service = new CommerceStorefrontLocaleTransferService();
        $result = $service->copy($metadata, 'ru', 'fr');

        $this->assertSame('editorial', $result['storefront']['template']);
        $this->assertSame('cartes', $result['storefront']['routing']['slugs']['fr']);
        $this->assertSame('Карточки', $result['storefront']['locales']['fr']['seo']['title']);
        $this->assertSame(12345, $result['storefront']['locales']['fr']['sections'][0]['mediaitemid']);
        $this->assertSame(
            $result['storefront']['locales']['fr']['sections'],
            $result['storefront']['sections']
        );
        $this->assertSame('Карточки на горе', $result['showroom']['locales']['fr']['alt']);
    }

    public function test_ai_preview_translates_text_only_and_apply_keeps_technical_values(): void {
        $this->resetAfterTest();
        global $CFG;

        set_config('storefront_ai_translation_enabled', 1, 'local_subscriptions');
        set_config('inbox_ai_openai_model', 'test-model', 'local_subscriptions');
        $CFG->local_subscriptions_openai_api_key = 'test-key';

        $metadata = [
            'storefront' => [
                'template' => 'editorial',
                'routing' => ['slugs' => ['fr' => 'cartes', 'ru' => 'kartochki']],
                'locales' => [
                    'ru' => [
                        'seo' => ['title' => 'Карточки', 'description' => 'Описание'],
                        'sections' => [[
                            'id' => 'hero',
                            'type' => 'hero',
                            'title' => 'Карточки',
                            'content' => '<p>Все формы <strong>глагола</strong>.</p>',
                            'url' => 'https://example.test/keep-me',
                            'mediaitemid' => 777,
                        ], [
                            'id' => 'faq',
                            'type' => 'faq',
                            'items' => [[
                                'question' => 'Есть ли озвучка?',
                                'answer' => '<p>Да.</p>',
                            ]],
                        ]],
                    ],
                ],
            ],
        ];

        $configuration = new OpenAiInboxConfiguration(new OpenAiApiKeyProvider());
        $client = new class implements OpenAiResponsesClientInterface {
            public function create(array $payload): OpenAiResponsesResult {
                $this->assert_payload_does_not_expose_technical_values($payload);
                $translations = [
                    ['id' => 'seo.title', 'text' => 'Cartes'],
                    ['id' => 'seo.description', 'text' => 'Description'],
                    ['id' => 'sections.0.title', 'text' => 'Cartes'],
                    ['id' => 'sections.0.content', 'text' => '<p>Toutes les formes du <strong>verbe</strong>.</p>'],
                    ['id' => 'sections.1.items.0.question', 'text' => 'Y a-t-il un audio ?'],
                    ['id' => 'sections.1.items.0.answer', 'text' => '<p>Oui.</p>'],
                ];
                return new OpenAiResponsesResult(
                    'resp_test',
                    null,
                    'test-model',
                    'completed',
                    [[
                        'type' => 'message',
                        'content' => [[
                            'type' => 'output_text',
                            'text' => json_encode(['translations' => $translations], JSON_UNESCAPED_UNICODE),
                        ]],
                    ]],
                    100,
                    50,
                    150,
                    null
                );
            }

            private function assert_payload_does_not_expose_technical_values(array $payload): void {
                $encoded = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                if (str_contains((string)$encoded, 'https://example.test/keep-me') || str_contains((string)$encoded, '777')) {
                    throw new \RuntimeException('Technical Storefront values leaked into the translation payload.');
                }
            }
        };

        $service = new CommerceStorefrontAiTranslationService($configuration, $client);
        $preview = $service->preview($metadata, 'ru', 'fr');
        $result = $service->apply_preview($metadata, $preview);

        $this->assertSame('Cartes', $result['storefront']['locales']['fr']['seo']['title']);
        $this->assertSame(
            '<p>Toutes les formes du <strong>verbe</strong>.</p>',
            $result['storefront']['locales']['fr']['sections'][0]['content']
        );
        $this->assertSame(
            'https://example.test/keep-me',
            $result['storefront']['locales']['fr']['sections'][0]['url']
        );
        $this->assertSame(777, $result['storefront']['locales']['fr']['sections'][0]['mediaitemid']);
        $this->assertSame('Y a-t-il un audio ?', $result['storefront']['locales']['fr']['sections'][1]['items'][0]['question']);
        $this->assertSame('cartes', $result['storefront']['routing']['slugs']['fr']);
        $this->assertSame($result['storefront']['locales']['fr']['sections'], $result['storefront']['sections']);
    }

    public function test_admin_builder_exposes_copy_translation_preview_and_apply_actions(): void {
        $source = file_get_contents(__DIR__ . '/../../../admin/commerce/products/storefront_builder.php');
        $this->assertStringContainsString("'locale_action', 'value' => 'copy'", str_replace(["\n", "\r"], '', $source));
        $this->assertStringContainsString("'locale_action', 'value' => 'translate_preview'", str_replace(["\n", "\r"], '', $source));
        $this->assertStringContainsString("'translate_apply'", $source);
        $this->assertStringContainsString('translation_preview', $source);
    }
}
