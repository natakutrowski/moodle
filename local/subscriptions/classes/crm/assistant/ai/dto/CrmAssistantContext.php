<?php

namespace local_subscriptions\crm\assistant\ai\dto;

defined('MOODLE_INTERNAL') || die();

/**
 * Minimal structured CRM context sent to the AI provider.
 */
final class CrmAssistantContext {

    public function __construct(
        public readonly string $scope,
        public readonly array $summary,
        public readonly array $recommendations,
        public readonly array $workitems,
        public readonly ?array $user = null,
        public readonly array $allowedreferences = []
    ) {
    }

    public function to_array(): array {
        return [
            'scope' => $this->scope,
            'summary' => $this->summary,
            'user' => $this->user,
            'recommendations' =>
                $this->recommendations,
            'workitems' => $this->workitems,
            'allowedreferences' =>
                $this->allowedreferences,
        ];
    }
}