<?php

namespace local_subscriptions\crm\inbox\ai\providers\openai;

defined('MOODLE_INTERNAL') || die();

final class OpenAiInboxConfiguration {

    private const DEFAULT_ENDPOINT =
        'https://api.openai.com/v1/responses';

    private const DEFAULT_MODEL =
        'gpt-5.6-luna';

    public function __construct(
        private readonly OpenAiApiKeyProvider $keys
    ) {
    }

    public function enabled(): bool {
        global $CFG;

        if (
            property_exists(
                $CFG,
                'local_subscriptions_openai_enabled'
            )
        ) {
            return (bool)
                $CFG->local_subscriptions_openai_enabled;
        }

        $stored = get_config(
            'local_subscriptions',
            'inbox_ai_openai_enabled'
        );

        if ($stored !== false) {
            return (bool)$stored;
        }

        /*
         * When credentials are deliberately supplied from config.php and no
         * Moodle setting has ever been stored, treat the provider as enabled.
         * This keeps secrets/configuration deployable without requiring a
         * second hidden database switch.
         */
        return $this->keys->has_key();
    }

    public function available(): bool {
        return
            $this->enabled() &&
            $this->keys->has_key() &&
            $this->model() !== '';
    }

    public function model(): string {
        global $CFG;

        $configured = trim(
            (string)(
                $CFG->local_subscriptions_openai_model
                ?? ''
            )
        );

        if ($configured !== '') {
            return $configured;
        }

        $stored = trim(
            (string)get_config(
                'local_subscriptions',
                'inbox_ai_openai_model'
            )
        );

        if ($stored !== '') {
            return $stored;
        }

        return self::DEFAULT_MODEL;
    }

    public function endpoint(): string {
        global $CFG;

        $endpoint = trim(
            (string)(
                $CFG->local_subscriptions_openai_endpoint
                ?? ''
            )
        );

        if ($endpoint === '') {
            $endpoint = trim(
                (string)get_config(
                    'local_subscriptions',
                    'inbox_ai_openai_endpoint'
                )
            );
        }

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