<?php

namespace local_subscriptions\crm\inbox\ai\dto;

defined('MOODLE_INTERNAL') || die();

final class InboxAiRequest {

    public function __construct(
        public readonly string $capability,
        public readonly int $threadid,
        public readonly ?int $messageid,
        public readonly string $content,
        public readonly string $requestedlanguage,
        public readonly array $context = [],
        public readonly array $constraints = [],
        public readonly ?int $actorid = null
    ) {
    }

    public function input_hash(
        string $promptversion
    ): string {
        return hash(
            'sha256',
            json_encode(
                [
                    'capability' =>
                        $this->capability,
                    'threadid' =>
                        $this->threadid,
                    'messageid' =>
                        $this->messageid,
                    'content' =>
                        $this->content,
                    'requestedlanguage' =>
                        $this->requestedlanguage,
                    'context' =>
                        $this->context,
                    'constraints' =>
                        $this->constraints,
                    'promptversion' =>
                        $promptversion,
                ],
                JSON_THROW_ON_ERROR |
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            )
        );
    }
}