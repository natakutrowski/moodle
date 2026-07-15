<?php

namespace local_subscriptions\crm\inbox\ai\dto;

defined('MOODLE_INTERNAL') || die();

final class InboxAiCrmContext {

    public function __construct(
        public readonly int $threadid,
        public readonly array $sections,
        public readonly array $warnings = [],
        public readonly ?int $generatedat = null
    ) {
    }

    public function to_array(): array {
        return [
            'threadid' => $this->threadid,
            'sections' => $this->sections,
            'warnings' => $this->warnings,
            'generatedat' =>
                $this->generatedat ?? time(),
        ];
    }

    public function section(
        string $key
    ): array {
        $section = $this->sections[$key] ?? [];

        return is_array($section)
            ? $section
            : [];
    }

    public function is_empty(): bool {
        return empty($this->sections);
    }
}