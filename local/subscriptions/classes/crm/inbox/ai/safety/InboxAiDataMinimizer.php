<?php

namespace local_subscriptions\crm\inbox\ai\safety;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\dto\InboxAiRequest;
use local_subscriptions\crm\inbox\ai\providers\openai\OpenAiInboxConfiguration;

final class InboxAiDataMinimizer {

    private const FORBIDDEN_CONTEXT_KEYS = [
        'userid',
        'matcheduserid',
        'threadid',
        'messageid',
        'accountid',
        'contactid',
        'teamid',
        'assigneduserid',
        'provideruid',
        'providerkey',
        'uidvalidity',
        'credentialkey',
        'password',
        'secret',
        'token',
        'authorization',
        'headers',
        'headersjson',
    ];

    public function __construct(
        private readonly OpenAiInboxConfiguration $configuration
    ) {
    }

    public function minimize(
        InboxAiRequest $request
    ): InboxAiRequest {
        $context = $request->context;

        if (
            !$this->configuration
                ->include_crm_context()
        ) {
            unset($context['crmcontext']);
        }

        if (
            !$this->configuration
                ->include_contact_email()
        ) {
            unset(
                $context['crmcontext']
                    ['sections']
                    ['contact']
                    ['email']
            );
        }

        if (
            isset($context['crmcontext']) &&
            is_array(
                $context['crmcontext']
            )
        ) {
            $context['crmcontext'] =
                $this->sanitize_context(
                    $context['crmcontext']
                );
        }

        return new InboxAiRequest(
            $request->capability,
            $request->threadid,
            $request->messageid,
            $request->content,
            $request->requestedlanguage,
            $context,
            $request->constraints,
            $request->actorid
        );
    }

    private function sanitize_context(
        array $context
    ): array {
        $result = [];

        foreach ($context as $key => $value) {
            $normalizedkey =
                \core_text::strtolower(
                    trim((string)$key)
                );

            if (
                in_array(
                    $normalizedkey,
                    self::FORBIDDEN_CONTEXT_KEYS,
                    true
                )
            ) {
                continue;
            }

            if (is_array($value)) {
                $result[$key] =
                    $this->sanitize_context(
                        $value
                    );

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