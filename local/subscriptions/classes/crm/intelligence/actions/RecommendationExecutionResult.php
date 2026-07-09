<?php

namespace local_subscriptions\crm\intelligence\actions;

defined('MOODLE_INTERNAL') || die();

final class RecommendationExecutionResult {

    public function __construct(
        public readonly bool $success,
        public readonly string $message = '',
        public readonly array $payload = []
    ) {
    }
}