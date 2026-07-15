<?php

namespace local_subscriptions\crm\inbox\ai\providers\openai;

defined('MOODLE_INTERNAL') || die();

final class OpenAiInboxConfiguration {

    private const DEFAULT_ENDPOINT =
        'https://api.openai.com/v1/responses';

    public function __construct(
        private readonly OpenAiApiKeyProvider $keys
    ) {
    }

    public function enabled(): bool {
        return (bool)get_config(
            'local_subscriptions',
            'inbox_ai_openai_enabled'
        );
    }

    public function available(): bool {
        return
            $this->enabled() &&
            $this->keys->has_key() &&
            $this->model() !== '';
    }

    public function model(): string {
        return trim(
            (string)get_config(
                'local_subscriptions',
                'inbox_ai_openai_model'
            )
        );
    }

    public function endpoint(): string {
        $endpoint = trim(
            (string)get_config(
                'local_subscriptions',
                'inbox_ai_openai_endpoint'
            )
        );

        if ($endpoint === '') {
            return self::DEFAULT_ENDPOINT;
        }

        $endpoint = clean_param(
            $endpoint,
            PARAM_URL
        );

        $parts = parse_url($endpoint);

        if (
            !is_array($parts) ||
            \core_text::strtolower(
                (string)($parts['scheme'] ?? '')
            ) !== 'https' ||
            empty($parts['host'])
        ) {
            return self::DEFAULT_ENDPOINT;
        }

        return $endpoint;
    }

    public function timeout(): int {
        return max(
            5,
            min(
                120,
                (int)get_config(
                    'local_subscriptions',
                    'inbox_ai_openai_timeout'
                ) ?: 45
            )
        );
    }

    public function max_output_tokens(): int {
        return max(
            64,
            min(
                16000,
                (int)get_config(
                    'local_subscriptions',
                    'inbox_ai_openai_max_output_tokens'
                ) ?: 1500
            )
        );
    }

    public function store(): bool {
        return (bool)get_config(
            'local_subscriptions',
            'inbox_ai_openai_store'
        );
    }

    public function include_crm_context(): bool {
        return (bool)get_config(
            'local_subscriptions',
            'inbox_ai_include_crm_context'
        );
    }

    public function include_contact_email(): bool {
        return (bool)get_config(
            'local_subscriptions',
            'inbox_ai_include_contact_email'
        );
    }

    public function keys(): OpenAiApiKeyProvider {
        return $this->keys;
    }
}