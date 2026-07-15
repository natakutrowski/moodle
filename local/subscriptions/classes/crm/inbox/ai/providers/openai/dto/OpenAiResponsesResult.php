<?php

namespace local_subscriptions\crm\inbox\ai\providers\openai\dto;

defined('MOODLE_INTERNAL') || die();

final class OpenAiResponsesResult {

    public function __construct(
        public readonly string $responseid,
        public readonly ?string $requestid,
        public readonly string $model,
        public readonly string $status,
        public readonly array $output,
        public readonly int $inputtokens,
        public readonly int $outputtokens,
        public readonly int $totaltokens,
        public readonly ?string $incompletereason,
        public readonly array $rawmetadata = []
    ) {
    }
}