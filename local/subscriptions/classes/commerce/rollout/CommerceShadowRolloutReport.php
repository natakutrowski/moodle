<?php

declare(strict_types=1);
namespace local_subscriptions\commerce\rollout;
defined('MOODLE_INTERNAL') || die();
final class CommerceShadowRolloutReport {
    public function __construct(private readonly array $results) {}
    public function get_results(): array { return $this->results; }
    public function get_issue_count(): int { return array_sum(array_column($this->results, 'issues')); }
    public function is_equal(): bool { return $this->get_issue_count() === 0; }
}
