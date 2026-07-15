<?php

namespace local_subscriptions\crm\inbox\ai\providers\openai\contracts;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\providers\openai\dto\OpenAiResponsesResult;

interface OpenAiResponsesClientInterface {

    public function create(
        array $payload
    ): OpenAiResponsesResult;
}