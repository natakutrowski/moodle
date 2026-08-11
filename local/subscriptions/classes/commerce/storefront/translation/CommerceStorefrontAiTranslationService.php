<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\translation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\storefront\localisation\CommerceStorefrontLocaleTransferService;
use local_subscriptions\crm\inbox\ai\providers\openai\OpenAiApiKeyProvider;
use local_subscriptions\crm\inbox\ai\providers\openai\OpenAiInboxConfiguration;
use local_subscriptions\crm\inbox\ai\providers\openai\OpenAiResponsesClient;
use local_subscriptions\crm\inbox\ai\providers\openai\contracts\OpenAiResponsesClientInterface;
use local_subscriptions\crm\inbox\ai\providers\openai\dto\OpenAiResponsesResult;

/**
 * Produces reviewed Storefront locale translations through the configured OpenAI account.
 */
final class CommerceStorefrontAiTranslationService {
    private const MAX_FIELDS = 200;

    public function __construct(
        private readonly OpenAiInboxConfiguration $configuration,
        private readonly OpenAiResponsesClientInterface $client,
        private readonly CommerceStorefrontLocaleTransferService $transfer = new CommerceStorefrontLocaleTransferService(),
        private readonly CommerceStorefrontTranslationFieldMapper $mapper = new CommerceStorefrontTranslationFieldMapper()
    ) {
    }

    public static function create(): self {
        $configuration = new OpenAiInboxConfiguration(new OpenAiApiKeyProvider());
        return new self($configuration, new OpenAiResponsesClient($configuration));
    }

    public function enabled(): bool {
        return (bool)get_config('local_subscriptions', 'storefront_ai_translation_enabled');
    }

    public function available(): bool {
        return $this->enabled()
            && $this->configuration->keys()->has_key()
            && $this->configuration->model() !== '';
    }

    /**
     * @param array<string,mixed> $metadata
     * @return array<string,mixed>
     */
    public function preview(array $metadata, string $source, string $target): array {
        if (!$this->available()) {
            throw new \moodle_exception(
                'commerce_storefront_ai_translation_unavailable',
                'local_subscriptions'
            );
        }

        $source = $this->transfer->normalise_language($source);
        $target = $this->transfer->normalise_language($target);
        $copied = $this->transfer->copy($metadata, $source, $target);

        $storefront = is_array($copied['storefront'] ?? null) ? $copied['storefront'] : [];
        $targetlocale = is_array($storefront['locales'][$target] ?? null)
            ? $storefront['locales'][$target]
            : [];
        $showroom = is_array($copied['showroom'] ?? null) ? $copied['showroom'] : [];
        $showroomlocale = is_array($showroom['locales'][$target] ?? null)
            ? $showroom['locales'][$target]
            : [];

        $entries = $this->mapper->extract_locale($targetlocale);
        $showroomentries = $this->mapper->extract_showroom_locale($showroomlocale);
        $allentries = array_merge($entries, $showroomentries);

        if ($allentries === []) {
            throw new \moodle_exception(
                'commerce_storefront_ai_translation_no_content',
                'local_subscriptions'
            );
        }
        if (count($allentries) > self::MAX_FIELDS) {
            throw new \moodle_exception(
                'commerce_storefront_ai_translation_too_many_fields',
                'local_subscriptions',
                '',
                self::MAX_FIELDS
            );
        }

        $response = $this->client->create($this->payload($allentries, $source, $target));
        $translations = $this->parse_response($response, $allentries);

        $translatedlocale = $this->mapper->apply_locale($targetlocale, $entries, $translations);
        $translatedshowroomlocale = $this->mapper->apply_showroom_locale(
            $showroomlocale,
            $showroomentries,
            $translations
        );

        $changes = [];
        foreach ($allentries as $entry) {
            $translated = (string)($translations[$entry['id']] ?? $entry['text']);
            $changes[] = [
                'id' => $entry['id'],
                'source' => $entry['text'],
                'translated' => $translated,
                'changed' => $translated !== $entry['text'],
            ];
        }

        return [
            'source' => $source,
            'target' => $target,
            'locale' => $translatedlocale,
            'showroomlocale' => $translatedshowroomlocale,
            'changes' => $changes,
            'model' => $response->model,
            'responseid' => $response->responseid,
            'inputtokens' => $response->inputtokens,
            'outputtokens' => $response->outputtokens,
            'totaltokens' => $response->totaltokens,
        ];
    }

    /**
     * @param array<string,mixed> $metadata
     * @param array<string,mixed> $preview
     * @return array<string,mixed>
     */
    public function apply_preview(array $metadata, array $preview): array {
        $target = $this->transfer->normalise_language((string)($preview['target'] ?? ''));
        if (!is_array($preview['locale'] ?? null)) {
            throw new \invalid_parameter_exception('Invalid Storefront translation preview.');
        }

        $storefront = $metadata['storefront'] ?? [];
        if (is_string($storefront)) {
            $decoded = json_decode($storefront, true);
            $storefront = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($storefront)) {
            $storefront = [];
        }
        $storefront['locales'] = is_array($storefront['locales'] ?? null)
            ? $storefront['locales']
            : [];
        $storefront['locales'][$target] = $preview['locale'];
        if ($target === 'fr') {
            $storefront['sections'] = (array)($preview['locale']['sections'] ?? []);
        }
        $metadata['storefront'] = $storefront;

        if (is_array($preview['showroomlocale'] ?? null)) {
            $showroom = is_array($metadata['showroom'] ?? null) ? $metadata['showroom'] : [];
            $showroom['locales'] = is_array($showroom['locales'] ?? null)
                ? $showroom['locales']
                : [];
            $showroom['locales'][$target] = $preview['showroomlocale'];
            $metadata['showroom'] = $showroom;
        }

        return $metadata;
    }

    /**
     * @param array<int,array{id:string,text:string,html:bool,path:array<int|string>}> $entries
     * @return array<string,mixed>
     */
    private function payload(array $entries, string $source, string $target): array {
        $language = static fn(string $code): string => match ($code) {
            'fr' => 'French',
            'en' => 'English',
            'ru' => 'Russian',
            default => $code,
        };

        $inputentries = array_map(
            static fn(array $entry): array => [
                'id' => $entry['id'],
                'text' => $entry['text'],
                'html' => $entry['html'],
            ],
            $entries
        );

        $instructions = implode("\n", [
            'You are the CampusFR Storefront translation engine.',
            'Translate commercial and educational copy faithfully from ' . $language($source) . ' to ' . $language($target) . '.',
            'Preserve the meaning, friendly premium CampusFR tone, punctuation, emojis, HTML structure and existing URLs exactly.',
            'Never translate or alter CampusFR, product SKUs, placeholders, file names, URLs, HTML attributes, or French grammar terms such as participe passé and futur simple when they are intentionally written in French.',
            'Do not add claims, facts, pricing, guarantees or content that is not present in the source.',
            'For HTML fields, translate text nodes only and preserve tags and attributes.',
            'Return exactly one translation for every supplied id and never change the ids.',
        ]);

        return [
            'model' => $this->configuration->model(),
            'store' => false,
            'max_output_tokens' => 12000,
            'instructions' => $instructions,
            'input' => [[
                'role' => 'user',
                'content' => [[
                    'type' => 'input_text',
                    'text' => json_encode(
                        ['entries' => $inputentries],
                        JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    ),
                ]],
            ]],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'campusfr_storefront_translations',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'translations' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'id' => ['type' => 'string'],
                                        'text' => ['type' => 'string'],
                                    ],
                                    'required' => ['id', 'text'],
                                    'additionalProperties' => false,
                                ],
                            ],
                        ],
                        'required' => ['translations'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<int,array{id:string,text:string,html:bool,path:array<int|string>}> $entries
     * @return array<string,string>
     */
    private function parse_response(OpenAiResponsesResult $result, array $entries): array {
        $decoded = null;
        foreach ($result->output as $outputitem) {
            if (!is_array($outputitem) || !is_array($outputitem['content'] ?? null)) {
                continue;
            }
            foreach ($outputitem['content'] as $content) {
                if (!is_array($content) || !is_string($content['text'] ?? null)) {
                    continue;
                }
                $candidate = json_decode($content['text'], true);
                if (is_array($candidate)) {
                    $decoded = $candidate;
                    break 2;
                }
            }
        }
        if (!is_array($decoded) || !is_array($decoded['translations'] ?? null)) {
            throw new \UnexpectedValueException('OpenAI Storefront translation response did not contain structured JSON.');
        }

        $expected = [];
        foreach ($entries as $entry) {
            $expected[$entry['id']] = true;
        }
        $translations = [];
        foreach ($decoded['translations'] as $translation) {
            if (!is_array($translation)) {
                continue;
            }
            $id = (string)($translation['id'] ?? '');
            $text = $translation['text'] ?? null;
            if (!isset($expected[$id]) || !is_string($text)) {
                continue;
            }
            $translations[$id] = $text;
        }
        if (count($translations) !== count($expected)) {
            throw new \UnexpectedValueException('OpenAI Storefront translation response omitted one or more fields.');
        }
        return $translations;
    }
}
