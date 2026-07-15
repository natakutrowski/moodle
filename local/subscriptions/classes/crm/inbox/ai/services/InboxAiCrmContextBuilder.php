<?php

namespace local_subscriptions\crm\inbox\ai\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\context\InboxAiContextRegistry;
use local_subscriptions\crm\inbox\ai\dto\InboxAiCrmContext;

final class InboxAiCrmContextBuilder {

    public function __construct(
        private readonly InboxAiContextRegistry $registry
    ) {
    }

    public function build(
        int $threadid
    ): InboxAiCrmContext {
        $sections = [];
        $warnings = [];

        foreach (
            $this->registry->providers()
            as $provider
        ) {
            try {
                if (!$provider->supports($threadid)) {
                    continue;
                }

                $data = $provider->provide(
                    $threadid
                );

                if (!$data) {
                    continue;
                }

                $sections[$provider->key()] =
                    $this->sanitize_section($data);
            } catch (\Throwable $exception) {
                $warnings[] = sprintf(
                    '%s: %s',
                    $provider->key(),
                    $exception->getMessage()
                );

                debugging(
                    'CRM Inbox AI context provider failed: ' .
                    $provider->key() .
                    ' - ' .
                    $exception->getMessage(),
                    DEBUG_DEVELOPER
                );
            }
        }

        return new InboxAiCrmContext(
            $threadid,
            $sections,
            $warnings,
            time()
        );
    }

    private function sanitize_section(
        array $data
    ): array {
        $forbiddenkeys = [
            'password',
            'secret',
            'token',
            'credentialkey',
            'provideruid',
            'providerkey',
            'headers',
            'headersjson',
            'authorization',
            'cardnumber',
            'cvv',
        ];

        $result = [];

        foreach ($data as $key => $value) {
            $normalizedkey =
                \core_text::strtolower(
                    trim((string)$key)
                );

            if (
                in_array(
                    $normalizedkey,
                    $forbiddenkeys,
                    true
                )
            ) {
                continue;
            }

            if (is_array($value)) {
                $result[$key] =
                    $this->sanitize_section($value);

                continue;
            }

            if (
                is_scalar($value) ||
                $value === null
            ) {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}