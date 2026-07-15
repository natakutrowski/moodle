<?php

namespace local_subscriptions\crm\inbox\ai\safety;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\providers\openai\exceptions\OpenAiConfigurationException;
use local_subscriptions\crm\inbox\ai\providers\openai\exceptions\OpenAiResponseException;
use local_subscriptions\crm\inbox\ai\providers\openai\exceptions\OpenAiTransportException;

final class InboxAiErrorSanitizer {

    public function public_message(
        \Throwable $exception
    ): string {
        if (
            $exception instanceof
            OpenAiConfigurationException
        ) {
            return 'AI provider configuration is incomplete.';
        }

        if (
            $exception instanceof
            OpenAiTransportException
        ) {
            return 'AI provider transport failed.';
        }

        if (
            $exception instanceof
            OpenAiResponseException
        ) {
            return 'AI provider returned an invalid response.';
        }

        return 'Inbox AI analysis failed.';
    }

    public function diagnostic_metadata(
        \Throwable $exception
    ): array {
        return [
            'exceptionclass' =>
                get_class($exception),

            'errorcode' =>
                (int)$exception->getCode(),
        ];
    }
}