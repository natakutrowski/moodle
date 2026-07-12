<?php

namespace local_subscriptions\crm\help;

defined('MOODLE_INTERNAL') || die();

final class HelpArticle {

    public function __construct(
        public readonly string $id,
        public readonly string $categoryid,
        public readonly string $title,
        public readonly string $summary,
        public readonly string $contentfile,
        public readonly array $keywords = [],
        public readonly array $contexts = [],
        public readonly int $priority = 100,
        public readonly bool $developer = false
    ) {
    }

    public function matches_context(string $context): bool {
        return in_array($context, $this->contexts, true);
    }

    public function is_developer_article(): bool {
        return $this->developer;
    }
}