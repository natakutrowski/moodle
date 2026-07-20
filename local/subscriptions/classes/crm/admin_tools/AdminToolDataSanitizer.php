<?php

namespace local_subscriptions\crm\admin_tools;

defined('MOODLE_INTERNAL') || die();

/**
 * Removes secrets and oversized values before execution data is stored.
 */
final class AdminToolDataSanitizer {

    /**
     * Any normalised key containing one of these fragments is redacted.
     */
    private const SENSITIVE_KEY_FRAGMENTS = [
        'password',
        'passwd',
        'secret',
        'token',
        'apikey',
        'authorization',
        'credential',
        'cookie',
        'sessionid',
        'sesskey',
        'privatekey',
        'clientsecret',
        'accesstoken',
        'refreshtoken',
        'htmlbody',
        'rawmessage',
        'messagebody',
    ];

    /**
     * Long textual contents that must never be stored in history.
     */
    private const REDACTED_CONTENT_KEYS = [
        'body',
        'content',
        'html',
        'raw',
        'payload',
    ];

    private const MAX_STRING_LENGTH = 2000;

    private const MAX_DEPTH = 12;

    private const MAX_ARRAY_ITEMS = 250;

    /**
     * Sanitises a value before persistence.
     *
     * @param array $data
     * @return array
     */
    public function sanitize(
        array $data
    ): array {
        return $this->sanitize_array(
            $data,
            0
        );
    }

    /**
     * Sanitises an exception message.
     *
     * Exception messages may contain URLs, provider responses,
     * credentials or complete remote payloads.
     *
     * @param string $message
     * @return string
     */
    public function sanitize_message(
        string $message
    ): string {
        $message = trim($message);

        if ($message === '') {
            return '';
        }

        $message = $this->redact_common_secrets(
            $message
        );

        return $this->truncate($message);
    }

    /**
     * @param array $data
     * @param int $depth
     * @return array
     */
    private function sanitize_array(
        array $data,
        int $depth
    ): array {
        if ($depth >= self::MAX_DEPTH) {
            return [
                '_truncated' =>
                    '[maximum nesting depth reached]',
            ];
        }

        $sanitized = [];
        $processed = 0;

        foreach ($data as $key => $value) {
            if (
                $processed >=
                self::MAX_ARRAY_ITEMS
            ) {
                $sanitized['_truncated'] =
                    '[maximum item count reached]';

                break;
            }

            $processed++;

            $normalizedkey =
                $this->normalize_key(
                    (string)$key
                );

            if (
                $this->is_sensitive_key(
                    $normalizedkey
                )
            ) {
                $sanitized[$key] =
                    '[redacted]';

                continue;
            }

            if (
                in_array(
                    $normalizedkey,
                    self::REDACTED_CONTENT_KEYS,
                    true
                )
            ) {
                $sanitized[$key] =
                    '[content omitted]';

                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] =
                    $this->sanitize_array(
                        $value,
                        $depth + 1
                    );

                continue;
            }

            if (is_object($value)) {
                $sanitized[$key] =
                    $this->sanitize_array(
                        get_object_vars($value),
                        $depth + 1
                    );

                continue;
            }

            if (is_string($value)) {
                $sanitized[$key] =
                    $this->sanitize_message(
                        $value
                    );

                continue;
            }

            if (
                is_scalar($value) ||
                $value === null
            ) {
                $sanitized[$key] = $value;

                continue;
            }

            $sanitized[$key] =
                '[unsupported value]';
        }

        return $sanitized;
    }

    private function normalize_key(
        string $key
    ): string {
        $key = \core_text::strtolower(
            trim($key)
        );

        return preg_replace(
            '/[^a-z0-9]+/',
            '',
            $key
        ) ?? '';
    }

    private function is_sensitive_key(
        string $normalizedkey
    ): bool {
        foreach (
            self::SENSITIVE_KEY_FRAGMENTS
            as $fragment
        ) {
            if (
                str_contains(
                    $normalizedkey,
                    $fragment
                )
            ) {
                return true;
            }
        }

        return false;
    }

    private function redact_common_secrets(
        string $value
    ): string {
        $patterns = [
            '/Bearer\s+[A-Za-z0-9._~+\/=-]+/i',
            '/Basic\s+[A-Za-z0-9+\/=]+/i',

            '/([?&](?:token|access_token|refresh_token|apikey|api_key|secret|password)=)[^&\s]+/i',

            '/("(?:token|access_token|refresh_token|apikey|api_key|secret|password)"\s*:\s*")[^"]*"/i',

            '/((?:token|access_token|refresh_token|apikey|api_key|secret|password)\s*[=:]\s*)[^\s,;]+/i',
        ];

        $replacements = [
            'Bearer [redacted]',
            'Basic [redacted]',
            '$1[redacted]',
            '$1[redacted]"',
            '$1[redacted]',
        ];

        $sanitized = preg_replace(
            $patterns,
            $replacements,
            $value
        );

        return $sanitized ?? $value;
    }

    private function truncate(
        string $value
    ): string {
        if (
            \core_text::strlen($value) <=
            self::MAX_STRING_LENGTH
        ) {
            return $value;
        }

        return
            \core_text::substr(
                $value,
                0,
                self::MAX_STRING_LENGTH
            ) .
            '…';
    }
}